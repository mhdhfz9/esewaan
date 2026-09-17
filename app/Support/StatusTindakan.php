<?php

namespace App\Support;

use App\Models\RentalContract;

class StatusTindakan
{
    public const BARU_TERIMA_PERMOHONAN = 'baru_terima_permohonan';

    public const SEMAKAN_CP_SEBELUM_BPH = 'semakan_cp_sebelum_bph';

    public const MINIT_CERAIAN_KELULUSAN_KOS = 'minit_ceraian_kelulusan_kos';

    public const PERTIMBANGAN_MOF = 'pertimbangan_mof';

    public const PERTIMBANGAN_KE = 'pertimbangan_ke';

    public const PERTIMBANGAN_BPH_SUPS = 'pertimbangan_bph_sups';

    public const SEDIA_DRAF_PERJANJIAN = 'sedia_draf_perjanjian';

    public const SEMAKAN_CP_SEBELUM_PUU = 'semakan_cp_sebelum_puu';

    public const SEMAKAN_PUU = 'semakan_puu';

    public const KUIRI_PUU = 'kuiri_puu';

    public const PINDAAN_SYOR_PUU_TANDATANGAN_PEMILIK = 'pindaan_syor_puu_tandatangan_pemilik';

    public const TANDATANGAN_PENYEWA_AADK = 'tandatangan_penyewa_aadk';

    public const TINDAKAN_METERI_SETEM = 'tindakan_meteri_setem';

    /**
     * Ordered status labels shown on the dashboard "Status Tindakan" section.
     *
     * @return list<array{key: string, label: string, workflows: list<string>}>
     */
    public static function definitions(): array
    {
        return [
            [
                'key' => self::BARU_TERIMA_PERMOHONAN,
                'label' => 'Belum Terima Permohonan',
                'workflows' => [],
            ],
            [
                'key' => self::SEMAKAN_CP_SEBELUM_BPH,
                'label' => 'Semakan Cawangan Pembangunan Sebelum ke BPH',
                'workflows' => [],
            ],
            [
                'key' => self::MINIT_CERAIAN_KELULUSAN_KOS,
                'label' => 'Minit Ceraian Kelulusan Kos',
                'workflows' => [],
            ],
            [
                'key' => self::PERTIMBANGAN_MOF,
                'label' => 'Pertimbangan MOF',
                'workflows' => [],
            ],
            [
                'key' => self::PERTIMBANGAN_KE,
                'label' => 'Pertimbangan KE',
                'workflows' => [],
            ],
            [
                'key' => self::PERTIMBANGAN_BPH_SUPS,
                'label' => 'Pertimbangan BPH/SUPS',
                'workflows' => [],
            ],
            [
                'key' => self::SEDIA_DRAF_PERJANJIAN,
                'label' => 'Sedia Draf Perjanjian',
                'workflows' => [RentalContract::WORKFLOW_PENYEDIAAN_DRAF_PERJANJIAN],
            ],
            [
                'key' => self::SEMAKAN_CP_SEBELUM_PUU,
                'label' => 'Semakan Cawangan Pembangunan Sebelum ke PUU',
                'workflows' => [],
            ],
            [
                'key' => self::SEMAKAN_PUU,
                'label' => 'Semakan PUU',
                'workflows' => [RentalContract::WORKFLOW_SEMAKAN_PUU],
            ],
            [
                'key' => self::KUIRI_PUU,
                'label' => 'Kuiri PUU',
                'workflows' => [],
            ],
            [
                'key' => self::PINDAAN_SYOR_PUU_TANDATANGAN_PEMILIK,
                'label' => 'Pindaan Berdasarkan Syor PUU dan Tandatangan Pemilik Premis',
                'workflows' => [RentalContract::WORKFLOW_PINDAAN_BERDASARKAN_PUU],
            ],
            [
                'key' => self::TANDATANGAN_PENYEWA_AADK,
                'label' => 'Tandatangan Penyewa (AADK)',
                'workflows' => [RentalContract::WORKFLOW_DRAF_PERJANJIAN_LULUS, RentalContract::WORKFLOW_DRAF_DIKEMBALIKAN_HQ],
            ],
            [
                'key' => self::TINDAKAN_METERI_SETEM,
                'label' => 'Tindakan Mati Setem',
                'workflows' => [RentalContract::WORKFLOW_MATI_SETEM],
            ],
        ];
    }

    /**
     * @return list<array{key: string, status: string, count: int}>
     */
    public static function breakdownWithCounts(): array
    {
        $definitions = self::definitions();

        $workflowKeys = collect($definitions)
            ->flatMap(fn (array $row): array => $row['workflows'])
            ->unique()
            ->values()
            ->all();

        $progressCounts = empty($workflowKeys)
            ? collect()
            : RentalContract::query()
                ->whereIn('workflow_tahap', $workflowKeys)
                ->selectRaw('workflow_tahap, COUNT(*) as total')
                ->groupBy('workflow_tahap')
                ->pluck('total', 'workflow_tahap');

        return collect($definitions)
            ->map(function (array $row) use ($progressCounts): array {
                $count = collect($row['workflows'])
                    ->sum(fn (string $workflow): int => (int) ($progressCounts[$workflow] ?? 0));

                return [
                    'key' => $row['key'],
                    'status' => $row['label'],
                    'count' => $count,
                ];
            })
            ->values()
            ->all();
    }
}
