<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithTablePartial;
use App\Http\Requests\ApproveHqApplicationRequest;
use App\Http\Requests\AutosaveHqJrpChecklistRequest;
use App\Http\Requests\DeleteRentalApplicationRequest;
use App\Http\Requests\RequestWithdrawalApplicationRequest;
use App\Http\Requests\ResolveWithdrawalApplicationRequest;
use App\Mail\ApplicationApprovedByHqNotification;
use App\Mail\ApplicationSubmittedToAdminNegeriNotification;
use App\Mail\ApplicationSubmittedToHqNotification;
use App\Models\RentalContract;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\SidebarNotificationService;
use App\Support\AdminProceedSteps;
use App\Support\ApplicationCategories;
use App\Support\HqJrpChecklist;
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

    public function review(Request $request, RentalContract $contract): View
    {
        $this->ensureCanReviewHq($request, $contract);

        $contract->loadMissing(['premise', 'submittedBy', 'adminNegeriUser', 'parentContract.premise', 'withdrawalRequestedBy']);
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

        $nextWorkflow = RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI;

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
            'Permohonan premis '.($contract->premise?->nama_ptj ?? '–').' telah disahkan oleh HQ.',
            ['contract_id' => $contract->id, 'next_workflow' => $nextWorkflow],
        );

        $this->notifyAdminNegeriOfApprovedApplication($contract->fresh(['premise', 'submittedBy', 'adminNegeriUser']), $hqUser);
        $this->notifyAdminHqOfApprovedApplication($contract->fresh(['premise', 'submittedBy', 'adminNegeriUser']), $hqUser);

        return redirect()
            ->route('status-permohonan.index')
            ->with('success', 'Permohonan telah disahkan.');
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
            ->with('success', 'Permohonan telah dipadam dan direkodkan dalam sejarah HQ.');
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
            'Permohonan premis '.($contract->premise?->nama_ptj ?? '–').' dimohon untuk ditarik semula daripada HQ.',
            [
                'contract_id' => $contract->id,
                'withdrawal_reason' => $contract->withdrawal_reason,
            ],
        );

        return redirect()
            ->route('status-permohonan.index')
            ->with('success', 'Permohonan tarik semula telah dihantar kepada HQ untuk kelulusan.');
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
                'Permohonan tarik semula premis '.($contract->premise?->nama_ptj ?? '–').' diluluskan oleh HQ.',
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
            'Permohonan tarik semula premis '.($contract->premise?->nama_ptj ?? '–').' ditolak oleh HQ.',
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
                ->with('error', 'Permohonan ini belum lengkap untuk dihantar kepada Admin.');
        }

        if (! $contract->hasRequiredFollowUpRemark()) {
            return redirect()
                ->route('application.edit', $contract)
                ->with('error', 'Sila isi Remark sebelum menghantar permohonan ke Admin.');
        }

        $contract->update([
            'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ,
            'admin_negeri_user_id' => $user->id,
        ]);

        ActivityLogger::log(
            $user,
            'application_sent_to_hq',
            'Permohonan premis '.$contract->premise?->nama_ptj.' dihantar kepada Admin untuk semakan.',
            ['contract_id' => $contract->id],
        );

        $this->notifyAdminHqOfSubmittedApplication($contract, $user);
        $this->notifyAdminNegeriOfSubmittedApplication($contract, $user);

        return redirect()
            ->route('status-permohonan.index')
            ->with('success', 'Permohonan telah dihantar kepada Admin untuk semakan.');
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
            ->when($user->isAdminHq(), fn ($q) => $q->where('workflow_tahap', RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ))
            ->tap(fn (Builder $query) => RentalContractListSearch::apply($query, $search, [
                'include_peringkat_proses' => true,
            ]))
            ->orderForUserList($user, 'updated_at');
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

    private function ensureCanReviewHq(Request $request, RentalContract $contract): void
    {
        $user = $request->user();

        if (! $user?->isAdminHq()) {
            abort(403, 'Akses ditolak.');
        }

        if (! $contract->isPendingHqReview()) {
            abort(403, 'Permohonan ini tidak tersedia untuk semakan HQ.');
        }
    }
}
