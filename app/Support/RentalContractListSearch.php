<?php

namespace App\Support;

use App\Models\RentalContract;
use Illuminate\Database\Eloquent\Builder;

class RentalContractListSearch
{
    /**
     * @param  array{include_admin_negeri?: bool, include_deleted_by?: bool, include_delete_reason?: bool, include_peringkat_proses?: bool}  $options
     */
    public static function apply(Builder $query, string $search, array $options = []): void
    {
        $search = trim($search);

        if ($search === '') {
            return;
        }

        $like = ListSearch::like($search);
        $needle = mb_strtolower($search);

        $query->where(function (Builder $query) use ($like, $needle, $options): void {
            $query->whereHas('premise', function (Builder $premiseQuery) use ($like): void {
                $premiseQuery->where('nama_ptj', 'like', $like)
                    ->orWhere('negeri', 'like', $like)
                    ->orWhere('nama_pemilik', 'like', $like)
                    ->orWhere('alamat_penuh', 'like', $like);
            })
                ->orWhereHas('submittedBy', function (Builder $userQuery) use ($like): void {
                    $userQuery->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });

            if (($options['include_admin_negeri'] ?? false) === true) {
                $query->orWhereHas('adminNegeriUser', fn (Builder $userQuery) => $userQuery->where('name', 'like', $like));
            }

            if (($options['include_deleted_by'] ?? false) === true) {
                $query->orWhereHas('deletedBy', fn (Builder $userQuery) => $userQuery->where('name', 'like', $like));
            }

            $query->orWhere('kategori_permohonan', 'like', $like)
                ->orWhere('workflow_tahap', 'like', $like)
                ->orWhere('status_aktif', 'like', $like);

            if (($options['include_peringkat_proses'] ?? false) === true) {
                $query->orWhere('peringkat_proses', 'like', $like);
            }

            if (($options['include_delete_reason'] ?? false) === true) {
                $query->orWhere('delete_reason', 'like', $like);
            }

            $categoryMatches = ListSearch::matchingKeys(ApplicationCategories::labels(), $needle);

            if ($categoryMatches !== []) {
                $query->orWhereIn('kategori_permohonan', $categoryMatches);
            }

            $statusMatches = ListSearch::matchingKeys([
                'aktif' => 'Aktif',
                'dalam_proses' => 'Dalam proses',
                'tamat_tempoh' => 'Tamat tempoh',
                'batal' => 'Batal',
            ], $needle);

            if ($statusMatches !== []) {
                $query->orWhereIn('status_aktif', $statusMatches);
            }

            $workflowMatches = ListSearch::matchingKeys(self::workflowLabelMap(), $needle);

            if ($workflowMatches !== []) {
                $query->orWhereIn('workflow_tahap', $workflowMatches);
            }

            if (str_contains($needle, 'sedia') && str_contains($needle, 'hq')) {
                $query->orWhere('workflow_tahap', RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI);
            }

            if (str_contains($needle, 'tarik semula')) {
                $query->orWhere('withdrawal_status', RentalContract::WITHDRAWAL_PENDING);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    private static function workflowLabelMap(): array
    {
        return [
            RentalContract::WORKFLOW_MENUNGGU_PROCEED_NEGERI => 'Menunggu langkah tindakan',
            RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_HQ => 'Menunggu semakan HQ',
            RentalContract::WORKFLOW_MENUNGGU_SEMAKAN_NEGERI => 'Menunggu semakan pentadbir negeri',
        ];
    }
}
