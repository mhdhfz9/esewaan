<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithTablePartial;
use App\Http\Requests\ApproveHqApplicationRequest;
use App\Http\Requests\ApprovePuuReviewRequest;
use App\Http\Requests\AutosaveHqDraftAcknowledgementsRequest;
use App\Http\Requests\AutosaveHqJrpChecklistRequest;
use App\Http\Requests\AutosaveNegeriDraftAcknowledgementsRequest;
use App\Http\Requests\AutosaveNegeriMatiSetemAcknowledgementsRequest;
use App\Http\Requests\CompleteDraftAgreementRequest;
use App\Http\Requests\CompleteMatiSetemRequest;
use App\Http\Requests\DeleteRentalApplicationRequest;
use App\Http\Requests\RejectPuuReviewRequest;
use App\Http\Requests\RequestWithdrawalApplicationRequest;
use App\Http\Requests\ResolveWithdrawalApplicationRequest;
use App\Http\Requests\ReturnDraftToHqRequest;
use App\Http\Requests\SignAgreementRequest;
use App\Http\Requests\StoreDraftAgreementRequest;
use App\Mail\ApplicationApprovedByHqNotification;
use App\Mail\ApplicationSubmittedToAdminNegeriNotification;
use App\Mail\ApplicationSubmittedToHqNotification;
use App\Models\ContractDocument;
use App\Models\RentalContract;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\SidebarNotificationService;
use App\Support\AdminProceedSteps;
use App\Support\ApplicationCategories;
use App\Support\HqDraftAcknowledgements;
use App\Support\HqJrpChecklist;
use App\Support\NegeriDraftAcknowledgements;
use App\Support\NegeriMatiSetemAcknowledgements;
use App\Support\RentalContractListSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class StatusPermohonanController extends Controller
{
    use RespondsWithTablePartial;

    /**
     * View status permohonan. Negeri sees their negeri; Admin sees only submitted applications.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->input('search', ''));
        $tab = $request->input('tab', 'active');

        if ($user->isAdminHq()) {
            $tab = in_array($tab, ['active', 'history'], true) ? $tab : 'active';
        } else {
            $tab = 'active';
        }

        $contracts = $tab === 'history'
            ? $this->historyQuery($user, $search)->paginate(15)->withQueryString()
            : $this->contractsQuery($user, $search)->paginate(15)->withQueryString();

        $payload = [
            'contracts' => $contracts,
            'search' => $search,
            'tab' => $tab,
        ];

        if ($this->wantsTablePartial($request)) {
            return view(
                $tab === 'history' ? 'status-permohonan.partials.history-table' : 'status-permohonan.partials.table',
                $payload,
            );
        }

        return view('status-permohonan.index', $payload);
    }

    public function sync(Request $request): JsonResponse
    {
        $user = $request->user();
        $search = trim((string) $request->input('search', ''));
        $tab = $request->input('tab', 'active');

        if ($user->isAdminHq()) {
            $tab = in_array($tab, ['active', 'history'], true) ? $tab : 'active';
        } else {
            $tab = 'active';
        }

        return response()->json([
            'fingerprint' => $this->listFingerprint($user, $search, $tab),
        ]);
    }

    public function review(Request $request, RentalContract $contract): View
    {
        $this->ensureCanReview($request, $contract);

        $contract->loadMissing(['premise', 'submittedBy', 'adminNegeriUser', 'parentContract.premise', 'withdrawalRequestedBy', 'documents.user']);
        app(SidebarNotificationService::class)->markContractAsViewed($request->user(), $contract);
        $proceedData = AdminProceedSteps::viewData($contract, 1);

        return view('status-permohonan.review', array_merge($proceedData, [
            'contract' => $contract,
            'stepPanels' => AdminProceedSteps::stepPanelsFor($contract),
            'totalActiveSteps' => AdminProceedSteps::totalActiveSteps($contract),
        ]));
    }

    public function approve(ApproveHqApplicationRequest $request, RentalContract $contract): RedirectResponse
    {
        $contract->loadMissing(['premise', 'submittedBy', 'adminNegeriUser', 'parentContract']);
        $hqUser = $request->user();

        $nextWorkflow = RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN;

        $contract->update([
            'workflow_tahap' => $nextWorkflow,
            'hq_approved_at' => now(),
            'hq_jrp_checklist' => HqJrpChecklist::normalizeInput(
                $request->validated('jrp_checklist') ?? [],
                $contract,
            ),
        ]);

        $this->supersedeParentContract($contract, $hqUser);

        ActivityLogger::log(
            $hqUser,
            'application_approved_by_hq',
            'Permohonan premis '.($contract->premise?->nama_ptj ?? '–').' diteruskan ke penyediaan draf perjanjian oleh Ibu Pejabat.',
            ['contract_id' => $contract->id, 'next_workflow' => $nextWorkflow],
        );

        $this->notifyAdminNegeriOfApprovedApplication($contract->fresh(['premise', 'submittedBy', 'adminNegeriUser']), $hqUser);
        $this->notifyAdminHqOfApprovedApplication($contract->fresh(['premise', 'submittedBy', 'adminNegeriUser']), $hqUser);

        return redirect()
            ->route('status-permohonan.index')
            ->with('success', 'Permohonan diteruskan ke penyediaan draf perjanjian.');
    }

    /**
     * Negeri uploads a draft agreement PDF. Each upload advances the application
     * to PUU review and increments the semakan round (Semakan 1, 2, ...).
     */
    public function uploadDraftAgreement(StoreDraftAgreementRequest $request, RentalContract $contract): RedirectResponse
    {
        $contract->loadMissing(['premise', 'submittedBy', 'adminNegeriUser']);
        $user = $request->user();

        $file = $request->file('document');
        $path = $file->store('contract-documents/'.$contract->id, 'public');
        $safeName = $this->sanitizeFilename($file->getClientOriginalName());
        $nextCount = (int) $contract->semakan_count + 1;

        ContractDocument::query()->create([
            'contract_id' => $contract->id,
            'nama_fail' => $safeName,
            'path' => $path,
            'jenis' => ContractDocument::JENIS_DRAF_PERJANJIAN,
            'semakan_round' => $nextCount,
            'semakan_status' => ContractDocument::SEMAKAN_MENUNGGU,
            'user_id' => $user->id,
        ]);

        $contract->update([
            'workflow_tahap' => RentalContract::WORKFLOW_SEMAKAN_PUU,
            'semakan_count' => $nextCount,
        ]);

        ActivityLogger::log(
            $user,
            'draft_agreement_uploaded',
            'Draf perjanjian premis '.($contract->premise?->nama_ptj ?? '–').' dimuat naik (Semakan '.$nextCount.').',
            ['contract_id' => $contract->id, 'semakan_count' => $nextCount],
        );

        return redirect()
            ->route('status-permohonan.review', $contract)
            ->with('success', 'Draf perjanjian berjaya dimuat naik dan dihantar untuk Semakan '.$nextCount.'.');
    }

    /**
     * HQ approves the current PUU review round and advances to Draf Lulus (Selesai).
     */
    public function approvePuuReview(ApprovePuuReviewRequest $request, RentalContract $contract): RedirectResponse
    {
        $contract->loadMissing(['premise', 'submittedBy', 'adminNegeriUser']);
        $hqUser = $request->user();

        ContractDocument::markSemakanOutcome($contract, ContractDocument::SEMAKAN_DILULUSKAN);

        $contract->update([
            'workflow_tahap' => RentalContract::WORKFLOW_DRAF_PERJANJIAN_LULUS,
        ]);

        ActivityLogger::log(
            $hqUser,
            'puu_review_approved',
            'Semakan PUU premis '.($contract->premise?->nama_ptj ?? '–').' diluluskan ('.$contract->semakanLabel().').',
            ['contract_id' => $contract->id, 'semakan_count' => $contract->semakan_count],
        );

        return redirect()
            ->route('status-permohonan.index')
            ->with('success', 'Semakan PUU diluluskan. Status permohonan dikemaskini kepada Dokumen Perjanjian dikembalikan ke Cawangan Pembangunan AADK.');
    }

    /**
     * HQ rejects the current PUU review round and returns the application to
     * Negeri for a revised draft upload.
     */
    public function rejectPuuReview(RejectPuuReviewRequest $request, RentalContract $contract): RedirectResponse
    {
        $contract->loadMissing(['premise', 'submittedBy', 'adminNegeriUser']);
        $hqUser = $request->user();

        ContractDocument::markSemakanOutcome($contract, ContractDocument::SEMAKAN_DIBATALKAN);

        $contract->update([
            'workflow_tahap' => RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN,
        ]);

        ActivityLogger::log(
            $hqUser,
            'puu_review_rejected',
            'Semakan PUU premis '.($contract->premise?->nama_ptj ?? '–').' dibatalkan ('.$contract->semakanLabel().'). Draf dikembalikan kepada Negeri.',
            ['contract_id' => $contract->id, 'semakan_count' => $contract->semakan_count],
        );

        return redirect()
            ->route('status-permohonan.index')
            ->with('success', 'Semakan PUU dibatalkan. Negeri perlu muat naik draf semula.');
    }

    /**
     * HQ marks pindaan as complete and advances to Draf Lulus for Negeri.
     */
    public function complete(CompleteDraftAgreementRequest $request, RentalContract $contract): RedirectResponse
    {
        $contract->loadMissing(['premise', 'submittedBy', 'adminNegeriUser']);
        $hqUser = $request->user();

        $contract->update([
            'workflow_tahap' => RentalContract::WORKFLOW_DRAF_PERJANJIAN_LULUS,
        ]);

        ActivityLogger::log(
            $hqUser,
            'draft_agreement_completed',
            'Draf perjanjian premis '.($contract->premise?->nama_ptj ?? '–').' diluluskan tanpa pindaan dan dikembalikan kepada Negeri.',
            ['contract_id' => $contract->id],
        );

        return redirect()
            ->route('status-permohonan.index')
            ->with('success', 'Draf perjanjian diluluskan tanpa pindaan dan dikembalikan kepada Negeri.');
    }

    /**
     * Negeri acknowledges receipt of the final draft and returns the application
     * to HQ (Cawangan Pembangunan AADK).
     */
    public function returnDraftToHq(ReturnDraftToHqRequest $request, RentalContract $contract): RedirectResponse
    {
        $contract->loadMissing(['premise', 'submittedBy', 'adminNegeriUser']);
        $user = $request->user();

        $contract->update([
            'workflow_tahap' => RentalContract::WORKFLOW_DRAF_DIKEMBALIKAN_HQ,
            'negeri_draft_acknowledgements' => NegeriDraftAcknowledgements::normalizeInput($request->validated()),
        ]);

        ActivityLogger::log(
            $user,
            'draft_agreement_returned_to_hq',
            'Draf akhir premis '.($contract->premise?->nama_ptj ?? '–').' dikembalikan kepada Cawangan Pembangunan AADK oleh Negeri.',
            ['contract_id' => $contract->id],
        );

        return redirect()
            ->route('status-permohonan.index')
            ->with('success', 'Draf akhir telah dikembalikan kepada Ibu Pejabat (Cawangan Pembangunan AADK).');
    }

    /**
     * HQ confirms receipt, TKPP signing, and the ProMIS record, then returns
     * the application to Negeri for stamp duty.
     */
    public function finalize(SignAgreementRequest $request, RentalContract $contract): RedirectResponse
    {
        $contract->loadMissing(['premise', 'submittedBy', 'adminNegeriUser']);
        $hqUser = $request->user();

        $contract->update([
            'workflow_tahap' => RentalContract::WORKFLOW_MATI_SETEM,
            'hq_draft_acknowledgements' => HqDraftAcknowledgements::normalizeInput($request->validated()),
        ]);

        ActivityLogger::log(
            $hqUser,
            'agreement_sent_for_stamp_duty',
            'Perjanjian premis '.($contract->premise?->nama_ptj ?? '–').' dihantar kepada Negeri untuk mati setem.',
            ['contract_id' => $contract->id],
        );

        return redirect()
            ->route('status-permohonan.index')
            ->with('success', 'Dokumen telah dihantar kepada Negeri untuk Mati Setem.');
    }

    /**
     * Negeri confirms stamp duty is complete and the application enters the
     * rental contract list.
     */
    public function completeMatiSetem(CompleteMatiSetemRequest $request, RentalContract $contract): RedirectResponse
    {
        $contract->loadMissing(['premise', 'submittedBy', 'adminNegeriUser']);
        $user = $request->user();

        $contract->update([
            'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI,
            'status_aktif' => 'aktif',
            'hq_approved_at' => $contract->hq_approved_at ?? now(),
            'negeri_mati_setem_acknowledgements' => NegeriMatiSetemAcknowledgements::normalizeInput($request->validated()),
        ]);

        ActivityLogger::log(
            $user,
            'agreement_finalized',
            'Perjanjian premis '.($contract->premise?->nama_ptj ?? '–').' selesai selepas mati setem dan dimasukkan ke dalam Senarai Kontrak Sewaan.',
            ['contract_id' => $contract->id],
        );

        return redirect()
            ->route('kontrak-sewaan.index')
            ->with('success', 'Permohonan telah selesai dan dimasukkan ke dalam Senarai Kontrak Sewaan.');
    }

    public function autosaveChecklist(AutosaveHqJrpChecklistRequest $request, RentalContract $contract): JsonResponse
    {
        $contract->update([
            'hq_jrp_checklist' => HqJrpChecklist::normalizeInput(
                $request->validated('jrp_checklist') ?? [],
                $contract,
            ),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Checklist disimpan.',
            'checklist' => $contract->fresh()->hq_jrp_checklist,
        ]);
    }

    public function autosaveNegeriDraftAcknowledgements(AutosaveNegeriDraftAcknowledgementsRequest $request, RentalContract $contract): JsonResponse
    {
        $contract->update([
            'negeri_draft_acknowledgements' => NegeriDraftAcknowledgements::normalizeInput(
                $request->validated('acknowledgements') ?? [],
            ),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data disimpan',
            'acknowledgements' => $contract->fresh()->negeri_draft_acknowledgements,
        ]);
    }

    public function autosaveHqDraftAcknowledgements(AutosaveHqDraftAcknowledgementsRequest $request, RentalContract $contract): JsonResponse
    {
        $contract->update([
            'hq_draft_acknowledgements' => HqDraftAcknowledgements::normalizeInput(
                $request->validated('acknowledgements') ?? [],
            ),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data disimpan',
            'acknowledgements' => $contract->fresh()->hq_draft_acknowledgements,
        ]);
    }

    public function autosaveNegeriMatiSetemAcknowledgements(AutosaveNegeriMatiSetemAcknowledgementsRequest $request, RentalContract $contract): JsonResponse
    {
        $contract->update([
            'negeri_mati_setem_acknowledgements' => NegeriMatiSetemAcknowledgements::normalizeInput(
                $request->validated('acknowledgements') ?? [],
            ),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data disimpan',
            'acknowledgements' => $contract->fresh()->negeri_mati_setem_acknowledgements,
        ]);
    }

    /**
     * When a pindah/lanjutan is approved, retire the contract it replaces so the
     * Kontrak Sewaan list shows the new contract only (no duplicated data).
     */
    private function supersedeParentContract(RentalContract $contract, User $hqUser): void
    {
        $parent = $contract->parentContract;

        if (! $parent || $parent->isSuperseded()) {
            return;
        }

        $parent->update([
            'superseded_at' => now(),
            'superseded_by_contract_id' => $contract->id,
            'status_aktif' => 'tamat_tempoh',
        ]);

        ActivityLogger::log(
            $hqUser,
            'contract_superseded',
            'Kontrak sedia ada digantikan oleh permohonan '.ApplicationCategories::label($contract->kategori_permohonan).' yang diluluskan.',
            ['contract_id' => $parent->id, 'superseded_by_contract_id' => $contract->id],
        );
    }

    public function destroy(DeleteRentalApplicationRequest $request, RentalContract $contract): RedirectResponse
    {
        $contract->loadMissing(['premise', 'submittedBy']);
        $premiseName = $contract->premise?->nama_ptj ?? '–';
        $deleteReason = $request->validated('delete_reason');

        $contract->update([
            'delete_reason' => $deleteReason,
            'deleted_by_user_id' => $request->user()->id,
        ]);

        $contract->delete();

        ActivityLogger::log(
            $request->user(),
            'application_deleted',
            'Permohonan premis '.$premiseName.' dipadam. Alasan: '.$deleteReason,
            [
                'contract_id' => $contract->id,
                'premise_id' => $contract->premise?->id,
                'delete_reason' => $deleteReason,
            ],
        );

        return redirect()
            ->route('status-permohonan.index')
            ->with('success', 'Permohonan telah dipadam dan direkodkan dalam sejarah Ibu Pejabat.');
    }

    public function requestWithdrawal(RequestWithdrawalApplicationRequest $request, RentalContract $contract): RedirectResponse
    {
        $contract->loadMissing(['premise', 'submittedBy']);

        $contract->update([
            'withdrawal_status' => RentalContract::WITHDRAWAL_PENDING,
            'withdrawal_reason' => $request->validated('withdrawal_reason'),
            'withdrawal_requested_at' => now(),
            'withdrawal_requested_by_user_id' => $request->user()->id,
            'withdrawal_resolved_at' => null,
            'withdrawal_resolved_by_user_id' => null,
            'withdrawal_resolution_note' => null,
        ]);

        ActivityLogger::log(
            $request->user(),
            'application_withdrawal_requested',
            'Permohonan premis '.($contract->premise?->nama_ptj ?? '–').' dimohon untuk ditarik semula daripada Ibu Pejabat.',
            [
                'contract_id' => $contract->id,
                'withdrawal_reason' => $contract->withdrawal_reason,
            ],
        );

        return redirect()
            ->route('status-permohonan.index')
            ->with('success', 'Permohonan tarik semula telah dihantar kepada Ibu Pejabat untuk kelulusan.');
    }

    public function resolveWithdrawal(ResolveWithdrawalApplicationRequest $request, RentalContract $contract): RedirectResponse
    {
        $contract->loadMissing(['premise', 'submittedBy', 'withdrawalRequestedBy']);
        $decision = $request->validated('decision');
        $hqUser = $request->user();

        if ($decision === 'approve') {
            $contract->update([
                'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI,
                'withdrawal_status' => RentalContract::WITHDRAWAL_APPROVED,
                'withdrawal_resolved_at' => now(),
                'withdrawal_resolved_by_user_id' => $hqUser->id,
                'withdrawal_resolution_note' => $request->validated('withdrawal_resolution_note'),
            ]);

            ActivityLogger::log(
                $hqUser,
                'application_withdrawal_approved',
                'Permohonan tarik semula premis '.($contract->premise?->nama_ptj ?? '–').' diluluskan oleh Ibu Pejabat.',
                ['contract_id' => $contract->id],
            );

            return redirect()
                ->route('status-permohonan.index')
                ->with('success', 'Permohonan tarik semula telah diluluskan. Pentadbir negeri boleh mengemaskini semula permohonan.');
        }

        $contract->update([
            'withdrawal_status' => RentalContract::WITHDRAWAL_REJECTED,
            'withdrawal_resolved_at' => now(),
            'withdrawal_resolved_by_user_id' => $hqUser->id,
            'withdrawal_resolution_note' => $request->validated('withdrawal_resolution_note'),
        ]);

        ActivityLogger::log(
            $hqUser,
            'application_withdrawal_rejected',
            'Permohonan tarik semula premis '.($contract->premise?->nama_ptj ?? '–').' ditolak oleh Ibu Pejabat.',
            ['contract_id' => $contract->id],
        );

        return redirect()
            ->route('status-permohonan.index')
            ->with('success', 'Permohonan tarik semula telah ditolak.');
    }

    public function submitToHq(Request $request, RentalContract $contract): RedirectResponse
    {
        $user = $request->user();

        if (! $user?->isAdminNegeri() || ! filled($user->negeri)) {
            abort(403, 'Akses ditolak.');
        }

        $contract->loadMissing('premise');

        if ($contract->premise?->negeri !== $user->negeri) {
            abort(403, 'Akses ditolak. Permohonan ini berada di luar negeri anda.');
        }

        if (! $contract->isPendingProceed() || ! $contract->isProceedComplete()) {
            return redirect()
                ->route('status-permohonan.index')
                ->with('error', 'Permohonan ini belum lengkap untuk dihantar kepada Ibu Pejabat.');
        }

        if (! $contract->hasRequiredFollowUpRemark()) {
            return redirect()
                ->route('application.edit', $contract)
                ->with('error', 'Sila isi Remark sebelum menghantar permohonan ke Ibu Pejabat.');
        }

        $contract->update([
            'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ,
            'admin_negeri_user_id' => $user->id,
        ]);

        ActivityLogger::log(
            $user,
            'application_sent_to_hq',
            'Permohonan premis '.$contract->premise?->nama_ptj.' dihantar kepada Ibu Pejabat untuk semakan.',
            ['contract_id' => $contract->id],
        );

        $this->notifyAdminHqOfSubmittedApplication($contract, $user);
        $this->notifyAdminNegeriOfSubmittedApplication($contract, $user);

        return redirect()
            ->route('status-permohonan.index')
            ->with('success', 'Permohonan telah dihantar kepada Ibu Pejabat untuk semakan.');
    }

    private function notifyAdminHqOfSubmittedApplication(RentalContract $contract, User $submittedBy): void
    {
        $contract->loadMissing('premise');

        foreach ($this->adminHqNotificationRecipients() as $email) {
            Mail::to($email)->send(new ApplicationSubmittedToHqNotification($contract, $submittedBy));
        }
    }

    private function notifyAdminNegeriOfSubmittedApplication(RentalContract $contract, User $submittedBy): void
    {
        $contract->loadMissing(['premise', 'adminNegeriUser', 'submittedBy']);

        foreach ($this->adminNegeriNotificationRecipients($contract) as $email) {
            Mail::to($email)->send(new ApplicationSubmittedToAdminNegeriNotification($contract, $submittedBy));
        }
    }

    private function notifyAdminNegeriOfApprovedApplication(RentalContract $contract, User $approvedBy): void
    {
        $contract->loadMissing(['premise', 'adminNegeriUser', 'submittedBy']);

        foreach ($this->adminNegeriNotificationRecipients($contract) as $email) {
            Mail::to($email)->send(new ApplicationApprovedByHqNotification($contract, $approvedBy));
        }
    }

    private function notifyAdminHqOfApprovedApplication(RentalContract $contract, User $approvedBy): void
    {
        $contract->loadMissing(['premise', 'adminNegeriUser', 'submittedBy']);

        foreach ($this->adminHqNotificationRecipients() as $email) {
            Mail::to($email)->send(new ApplicationApprovedByHqNotification($contract, $approvedBy));
        }
    }

    /**
     * @return list<string>
     */
    private function adminHqNotificationRecipients(): array
    {
        $testRecipient = config('mail.test_recipient');

        if (filled($testRecipient)) {
            return [$testRecipient];
        }

        return User::query()
            ->where('role', 'admin_hq')
            ->where('is_active', true)
            ->whereNotNull('email')
            ->pluck('email')
            ->all();
    }

    /**
     * @return list<string>
     */
    private function adminNegeriNotificationRecipients(RentalContract $contract): array
    {
        $testRecipient = config('mail.test_recipient');

        if (filled($testRecipient)) {
            return [$testRecipient];
        }

        $contract->loadMissing(['adminNegeriUser', 'premise']);

        if (
            filled($contract->adminNegeriUser?->email)
            && $contract->adminNegeriUser->is_active
            && $contract->adminNegeriUser->isAdminNegeri()
        ) {
            return [$contract->adminNegeriUser->email];
        }

        $negeri = $contract->premise?->negeri;

        if (! filled($negeri)) {
            return [];
        }

        return User::query()
            ->where('role', 'admin_negeri')
            ->where('negeri', $negeri)
            ->where('is_active', true)
            ->whereNotNull('email')
            ->pluck('email')
            ->all();
    }

    private function listFingerprint(User $user, string $search, string $tab): string
    {
        $query = $tab === 'history'
            ? $this->historyQuery($user, $search)
            : $this->contractsQuery($user, $search);

        $contracts = (clone $query)
            ->select([
                'rental_contracts.id',
                'rental_contracts.updated_at',
                'rental_contracts.withdrawal_status',
                'rental_contracts.workflow_tahap',
                'rental_contracts.deleted_at',
            ])
            ->orderBy('rental_contracts.id')
            ->get();

        if ($contracts->isEmpty()) {
            return hash('xxh128', 'empty');
        }

        $signature = $contracts
            ->map(fn (RentalContract $contract) => implode(':', [
                $contract->id,
                $contract->updated_at?->getTimestamp() ?? 0,
                $contract->withdrawal_status ?? '',
                $contract->workflow_tahap ?? '',
                $contract->deleted_at?->getTimestamp() ?? 0,
            ]))
            ->implode('|');

        return hash('xxh128', $signature);
    }

    /**
     * @return Builder<RentalContract>
     */
    private function contractsQuery(User $user, string $search): Builder
    {
        return RentalContract::query()
            ->with([
                'premise',
                'submittedBy',
                'adminNegeriUser',
                'withdrawalRequestedBy',
                'notificationViews' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->when($user->isAdminNegeri() && $user->negeri, fn ($q) => $q->whereHas('premise', fn ($q2) => $q2->where('negeri', $user->negeri)))
            ->when($user->isAdminNegeri(), fn ($q) => $q->whereNotIn('workflow_tahap', RentalContract::hqApprovedWorkflows()))
            ->when($user->isAdminHq(), fn ($q) => $q->whereIn('workflow_tahap', array_merge(
                [RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ],
                RentalContract::draftAgreementWorkflows(),
            )))
            ->tap(fn (Builder $query) => RentalContractListSearch::apply($query, $search, [
                'include_peringkat_proses' => true,
            ]))
            ->orderByDesc('rental_contracts.created_at')
            ->orderByDesc('rental_contracts.id');
    }

    /**
     * @return Builder<RentalContract>
     */
    private function historyQuery(User $user, string $search): Builder
    {
        if (! $user->isAdminHq()) {
            abort(403, 'Akses ditolak.');
        }

        return RentalContract::query()
            ->onlyTrashed()
            ->with(['premise', 'submittedBy', 'deletedBy'])
            ->tap(fn (Builder $query) => RentalContractListSearch::apply($query, $search, [
                'include_deleted_by' => true,
                'include_delete_reason' => true,
            ]))
            ->latest('deleted_at');
    }

    private function ensureCanManage(Request $request, RentalContract $contract): void
    {
        $user = $request->user();

        if (! $user?->isAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        if ($user->isAdminHq()) {
            abort(403, 'Akses ditolak.');
        }

        $contract->loadMissing('premise');

        if ($user->isAdminNegeri() && $contract->premise?->negeri !== $user->negeri) {
            abort(403, 'Akses ditolak. Permohonan ini berada di luar negeri anda.');
        }
    }

    private function ensureCanReview(Request $request, RentalContract $contract): void
    {
        $user = $request->user();

        if ($user?->isAdminHq()) {
            if (! $contract->isPendingHqReview() && ! $contract->isInDraftAgreementStage()) {
                abort(403, 'Permohonan ini tidak tersedia untuk semakan Ibu Pejabat.');
            }

            return;
        }

        if ($user?->isAdminNegeri()) {
            $contract->loadMissing('premise');

            if (! $contract->isInDraftAgreementStage() || $contract->premise?->negeri !== $user->negeri) {
                abort(403, 'Akses ditolak.');
            }

            return;
        }

        abort(403, 'Akses ditolak.');
    }

    private function sanitizeFilename(string $name): string
    {
        $name = basename(str_replace(["\0", '..'], '', $name));
        $name = preg_replace('/[^\pL\pN._-]/u', '_', $name) ?? $name;

        return mb_substr($name, 0, 255);
    }
}
