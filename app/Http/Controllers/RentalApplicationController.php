<?php

namespace App\Http\Controllers;

use App\Http\Requests\AutosaveRentalApplicationRequest;
use App\Http\Requests\StoreRentalApplicationRequest;
use App\Http\Requests\UpdateRentalApplicationRequest;
use App\Models\Premise;
use App\Models\RentalContract;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\SidebarNotificationService;
use App\Support\AdminProceedSteps;
use App\Support\ApplicationCategories;
use App\Support\BuildingTypes;
use App\Support\MalaysianStates;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class RentalApplicationController extends Controller
{
    public function create(): View
    {
        return view('application.form', $this->formViewData());
    }

    public function edit(Request $request, RentalContract $contract): View
    {
        $this->ensureEditable($contract);

        $contract->loadMissing(['premise', 'submittedBy', 'parentContract.premise']);
        app(SidebarNotificationService::class)->markContractAsViewed($request->user(), $contract);
        $proceedData = AdminProceedSteps::viewData($contract, (int) $request->query('step', 0));

        return view('application.form', array_merge(
            $this->formViewData($contract),
            $proceedData,
            [
                'parentContract' => $contract->parentContract,
                'stepPanels' => AdminProceedSteps::stepPanelsFor($contract),
                'initialStep' => $proceedData['step'],
            ],
        ));
    }

    public function store(StoreRentalApplicationRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $admin = $request->user();

        $premise = Premise::query()->create([
            'nama_ptj' => $validated['nama_ptj'],
            'negeri' => $validated['negeri'],
            'daerah' => $validated['negeri'],
            'alamat_penuh' => $validated['alamat_penuh'],
            'jenis_bangunan' => $validated['jenis_bangunan'],
            'nama_pemilik' => $validated['nama_pemilik'],
            'kadar_sewa' => $validated['kadar_sewa'],
        ]);

        $contract = RentalContract::query()->create([
            'premise_id' => $premise->id,
            'submitted_by_user_id' => null,
            'admin_negeri_user_id' => $admin->id,
            'tarikh_mula' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
            'tarikh_tamat' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
            'kadar_sewa_bulanan' => $validated['kadar_sewa'],
            'keluasan_mp' => $validated['keluasan_mp'],
            'status_aktif' => 'dalam_proses',
            'peringkat_proses' => null,
            'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI,
            'kategori_permohonan' => $validated['kategori_permohonan'],
            'tarikh_mula_tawaran' => $validated['tarikh_mula_tawaran'],
            'sah_sehingga' => $validated['sah_sehingga'],
        ]);

        ActivityLogger::log(
            $admin,
            'application_drafted',
            'Permohonan '.ApplicationCategories::label($validated['kategori_permohonan']).' untuk premis '.$premise->nama_ptj.' dicipta. Sila lengkapkan langkah tindakan sebelum dihantar kepada Ibu Pejabat.',
            ['contract_id' => $contract->id, 'premise_id' => $premise->id],
        );

        Log::info('Rental application created by admin negeri, awaiting proceed steps', [
            'admin_id' => $admin->id,
            'premise_id' => $premise->id,
            'contract_id' => $contract->id,
            'kategori_permohonan' => $validated['kategori_permohonan'],
        ]);

        return redirect()
            ->route('admin-proceed.show', $contract)
            ->with('success', 'Permohonan '.ApplicationCategories::label($validated['kategori_permohonan']).' disimpan. Sila lengkapkan langkah tindakan.');
    }

    public function update(UpdateRentalApplicationRequest $request, RentalContract $contract): RedirectResponse|JsonResponse
    {
        $contract = $this->persistApplication($request->validated(), $contract, $request->user());

        $message = 'Maklumat permohonan telah disimpan.';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return redirect()
            ->route('application.edit', $contract)
            ->with('success', $message);
    }

    public function autosave(AutosaveRentalApplicationRequest $request, RentalContract $contract): JsonResponse
    {
        $contract = $this->persistApplication($request->validated(), $contract, $request->user(), partial: true);

        return $this->jsonSaveResponse($contract, 'Draf disimpan.');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function persistApplication(array $validated, RentalContract $contract, User $admin, bool $partial = false): RentalContract
    {
        $contract->loadMissing('premise');
        $premise = $contract->premise;

        if (! $premise) {
            abort(404);
        }

        if (! $partial || array_key_exists('nama_ptj', $validated)) {
            $premise->update([
                'nama_ptj' => $validated['nama_ptj'] ?? $premise->nama_ptj,
                'negeri' => $validated['negeri'] ?? $premise->negeri,
                'daerah' => $validated['negeri'] ?? $premise->daerah,
                'alamat_penuh' => $validated['alamat_penuh'] ?? $premise->alamat_penuh,
                'jenis_bangunan' => array_key_exists('jenis_bangunan', $validated)
                    ? $validated['jenis_bangunan']
                    : $premise->jenis_bangunan,
                'nama_pemilik' => $validated['nama_pemilik'] ?? $premise->nama_pemilik,
                'kadar_sewa' => array_key_exists('kadar_sewa', $validated)
                    ? $validated['kadar_sewa']
                    : $premise->kadar_sewa,
            ]);
        }

        if (! $partial || array_key_exists('kadar_sewa', $validated)) {
            $contract->update([
                'kadar_sewa_bulanan' => array_key_exists('kadar_sewa', $validated)
                    ? ($validated['kadar_sewa'] ?? 0)
                    : $contract->kadar_sewa_bulanan,
            ]);
        }

        if (! $partial || array_key_exists('keluasan_mp', $validated)) {
            $contract->update([
                'keluasan_mp' => array_key_exists('keluasan_mp', $validated)
                    ? $validated['keluasan_mp']
                    : $contract->keluasan_mp,
            ]);
        }

        if (! $partial || array_key_exists('tarikh_mula_tawaran', $validated)) {
            $contract->update([
                'tarikh_mula_tawaran' => $validated['tarikh_mula_tawaran'] ?? $contract->tarikh_mula_tawaran,
            ]);
        }

        if (! $partial || array_key_exists('sah_sehingga', $validated)) {
            $contract->update([
                'sah_sehingga' => $validated['sah_sehingga'] ?? $contract->sah_sehingga,
            ]);
        }

        if (array_key_exists('remark', $validated)) {
            $contract->update([
                'remark' => $validated['remark'],
            ]);
        }

        if (isset($validated['proceed_steps']) && is_array($validated['proceed_steps'])) {
            AdminProceedSteps::syncDraftFromInput($contract, $validated['proceed_steps']);
        }

        if (! $partial) {
            ActivityLogger::log(
                $admin,
                'application_updated',
                'Maklumat permohonan premis '.$premise->fresh()->nama_ptj.' dikemaskini.',
                ['contract_id' => $contract->id, 'premise_id' => $premise->id],
            );
        }

        return $contract->fresh(['premise', 'submittedBy']);
    }

    private function jsonSaveResponse(RentalContract $contract, string $message): JsonResponse
    {
        $contract->loadMissing(['premise', 'submittedBy']);
        $summary = AdminProceedSteps::progressSummary($contract);
        $progress = AdminProceedSteps::progressFor($contract);

        $steps = [];

        foreach (AdminProceedSteps::stepPanelsFor($contract) as $panel) {
            $steps[$panel['key']] = [
                'completed' => ($progress[$panel['key']]['completed'] ?? false) === true,
                'marked_complete' => ($progress[$panel['key']]['marked_complete'] ?? $progress[$panel['key']]['completed'] ?? false) === true,
                'confirmed_accurate' => ($progress[$panel['key']]['confirmed_accurate'] ?? false) === true,
                'confirmed_promis' => ($progress[$panel['key']]['confirmed_promis'] ?? false) === true,
                'notes' => $progress[$panel['key']]['notes'] ?? '',
                'no_rujukan' => $progress[$panel['key']]['no_rujukan'] ?? '',
                'tarikh_surat' => $progress[$panel['key']]['tarikh_surat'] ?? '',
                'agencies' => $progress[$panel['key']]['agencies'] ?? [],
                'agency_dates' => $progress[$panel['key']]['agency_dates'] ?? [],
            ];
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'progress' => $summary,
            'steps' => $steps,
            'allCompleted' => $contract->isProceedComplete(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formViewData(?RentalContract $contract = null): array
    {
        $admin = auth()->user();

        if (! $admin?->isAdminNegeri() || ! filled($admin->negeri)) {
            abort(403, 'Akses ditolak.');
        }

        return [
            'contract' => $contract,
            'negeriList' => MalaysianStates::all(),
            'jenisList' => BuildingTypes::all(),
            'adminNegeri' => $admin->negeri,
        ];
    }

    private function ensureEditable(RentalContract $contract): void
    {
        $admin = auth()->user();

        if (! $admin?->isAdminNegeri() || ! filled($admin->negeri)) {
            abort(403, 'Akses ditolak.');
        }

        $contract->loadMissing('premise');

        if (! $contract->isPendingProceed()) {
            abort(403, 'Permohonan ini tidak boleh dikemaskini kerana ia telah dihantar kepada Ibu Pejabat.');
        }

        if ($contract->premise?->negeri !== $admin->negeri) {
            abort(403, 'Akses ditolak. Permohonan ini berada di luar negeri anda.');
        }
    }
}
