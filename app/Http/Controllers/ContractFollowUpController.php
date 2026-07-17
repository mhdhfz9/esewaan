<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFollowUpApplicationRequest;
use App\Models\Premise;
use App\Models\RentalContract;
use App\Services\ActivityLogger;
use App\Support\ApplicationCategories;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractFollowUpController extends Controller
{
    /**
     * Start a pindah/lanjutan application from an existing HQ-approved contract.
     * Negeri is carried over from the parent. For lanjutan, keluasan is copied
     * from the existing contract; pindah and other maklumat baharu start empty.
     */
    public function store(StoreFollowUpApplicationRequest $request, RentalContract $contract): RedirectResponse
    {
        $contract->loadMissing('premise');
        $parentPremise = $contract->premise;

        if (! $parentPremise) {
            abort(404);
        }

        $kategori = $request->validated('kategori_permohonan');
        $admin = $request->user();

        $followUp = null;

        DB::transaction(function () use ($contract, $parentPremise, $kategori, $admin, &$followUp): void {
            $premise = Premise::query()->create([
                'nama_ptj' => '',
                'negeri' => $parentPremise->negeri,
                'daerah' => '',
                'alamat_penuh' => '',
                'jenis_bangunan' => null,
                'nama_pemilik' => null,
                'kadar_sewa' => null,
            ]);

            $keluasan = $kategori === ApplicationCategories::LANJUTAN
                ? $contract->keluasan_mp
                : null;

            $followUp = RentalContract::query()->create([
                'parent_contract_id' => $contract->id,
                'premise_id' => $premise->id,
                'submitted_by_user_id' => $contract->submitted_by_user_id,
                'admin_negeri_user_id' => $admin->id,
                'tarikh_mula' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
                'tarikh_tamat' => RentalContract::PLACEHOLDER_CONTRACT_DATE,
                'kadar_sewa_bulanan' => 0,
                'keluasan_mp' => $keluasan,
                'status_aktif' => 'dalam_proses',
                'peringkat_proses' => null,
                'workflow_tahap' => RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI,
                'kategori_permohonan' => $kategori,
                'sah_sehingga' => null,
            ]);
        });

        ActivityLogger::log(
            $admin,
            'follow_up_application_drafted',
            'Permohonan '.ApplicationCategories::label($kategori).' untuk premis '.$parentPremise->nama_ptj.' dicipta daripada kontrak sedia ada.',
            [
                'contract_id' => $followUp->id,
                'parent_contract_id' => $contract->id,
                'kategori' => $kategori,
            ],
        );

        return redirect()
            ->route('application.edit', $followUp)
            ->with('success', 'Permohonan '.ApplicationCategories::label($kategori).' dicipta. Sila lengkapkan maklumat baharu dan langkah tindakan.');
    }

    /**
     * Cancel a mistaken pindah/lanjutan draft and return to the original
     * contract. The draft and its auto-created premise are removed completely
     * so the parent contract reappears in the Kontrak Sewaan list.
     */
    public function destroy(Request $request, RentalContract $contract): RedirectResponse
    {
        $this->ensureRevertable($request, $contract);

        $contract->loadMissing(['premise', 'parentContract']);
        $parent = $contract->parentContract;
        $premise = $contract->premise;
        $kategori = $contract->kategori_permohonan;

        DB::transaction(function () use ($contract, $premise, $parent): void {
            $contract->forceDelete();

            if ($premise && $premise->id !== $parent?->premise_id) {
                $premise->delete();
            }
        });

        ActivityLogger::log(
            $request->user(),
            'follow_up_application_reverted',
            'Permohonan '.ApplicationCategories::label($kategori).' dibatalkan dan kontrak asal dikembalikan.',
            [
                'reverted_contract_id' => $contract->id,
                'parent_contract_id' => $parent?->id,
                'kategori' => $kategori,
            ],
        );

        return redirect()
            ->route('kontrak-sewaan.index')
            ->with('success', 'Permohonan '.ApplicationCategories::label($kategori).' dibatalkan. Kontrak asal telah dikembalikan ke senarai kontrak sewaan.');
    }

    private function ensureRevertable(Request $request, RentalContract $contract): void
    {
        $user = $request->user();

        if (! $user?->isAdminNegeri() || ! filled($user->negeri)) {
            abort(403, 'Akses ditolak.');
        }

        $contract->loadMissing('premise');

        if (! $contract->isFollowUpApplication()) {
            abort(403, 'Hanya permohonan pindah atau lanjutan boleh dibatalkan ke kontrak asal.');
        }

        if (! $contract->isPendingProceed()) {
            abort(403, 'Permohonan ini tidak boleh dibatalkan kerana ia telah dihantar kepada HQ.');
        }

        if ($contract->premise?->negeri !== $user->negeri) {
            abort(403, 'Akses ditolak. Permohonan ini berada di luar negeri anda.');
        }
    }
}
