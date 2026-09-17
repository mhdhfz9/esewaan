<?php

namespace App\Support;

use App\Models\RentalContract;

class HqJrpChecklist
{
    public const MOF = 'mof_negeri_budget_office';

    public const EPU = 'epu_jpm_planning';

    public const KP = 'kp';

    public const AADK_RECEIVE_EPU_COMMENTS = 'aadk_development_receive_epu_comments';

    public const AADK_RECEIVE_MOF_COMMENTS = 'aadk_development_receive_mof_comments';

    public const AADK_SUBMIT_BPH = 'aadk_development_submit_bph';

    public const AADK_RECEIVE_BPH_APPROVAL = 'aadk_development_receive_bph_approval';

    public const KELUASAN_THRESHOLD = 465;

    /**
     * @return list<string>
     */
    public static function allKeys(): array
    {
        return [
            self::KP,
            self::MOF,
            self::EPU,
            self::AADK_RECEIVE_EPU_COMMENTS,
            self::AADK_RECEIVE_MOF_COMMENTS,
            self::AADK_SUBMIT_BPH,
            self::AADK_RECEIVE_BPH_APPROVAL,
        ];
    }

    /**
     * Checklist items that record a date when checked.
     *
     * @return list<string>
     */
    public static function keysRequiringDate(): array
    {
        return [
            self::KP,
            self::MOF,
            self::EPU,
            self::AADK_RECEIVE_EPU_COMMENTS,
            self::AADK_RECEIVE_MOF_COMMENTS,
            self::AADK_SUBMIT_BPH,
            self::AADK_RECEIVE_BPH_APPROVAL,
        ];
    }

    public static function requiresDate(string $key): bool
    {
        return in_array($key, self::keysRequiringDate(), true);
    }

    /**
     * @return array<string, string>
     */
    public static function definitions(): array
    {
        return [
            self::KP => 'Kelulusan Pengurusan Tertinggi',
            self::MOF => 'Pejabat Belanjawan Negeri, Kementerian Kewangan Malaysia (MOF) — untuk permohonan yang ada kenaikan kadar sewa (≥ RM500)',
            self::EPU => 'Kementerian Ekonomi (KE) (jika keluasan melebihi 465 mps) — untuk permohonan perpindahan ruang pejabat / ruang pejabat baharu',
            self::AADK_RECEIVE_EPU_COMMENTS => 'Cawangan Pembangunan AADK menerima ulasan daripada Kementerian Ekonomi (KE)',
            self::AADK_RECEIVE_MOF_COMMENTS => 'Cawangan Pembangunan AADK menerima ulasan daripada MOF melalui KDN',
            self::AADK_SUBMIT_BPH => 'Cawangan Pembangunan AADK mengemukakan permohonan Bahagian Hartanah (BPH) Jabatan Perdana Menteri untuk mendapatkan kelulusan',
            self::AADK_RECEIVE_BPH_APPROVAL => 'Cawangan Pembangunan AADK terima surat kelulusan daripada BPH',
        ];
    }

    /**
     * @return list<string>
     */
    public static function applicableKeys(RentalContract $contract): array
    {
        return self::allKeys();
    }

    public static function requiresMofCheckbox(RentalContract $contract): bool
    {
        $difference = $contract->rentDifferenceFromParent();

        return $difference !== null && $difference >= RentalContract::RENT_DIFFERENCE_THRESHOLD;
    }

    public static function requiresEpuCheckbox(RentalContract $contract): bool
    {
        $keluasan = $contract->keluasan_mp;

        return $keluasan !== null && (float) $keluasan > self::KELUASAN_THRESHOLD;
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, bool>  $checklist
     * @return array<string, string|null>
     */
    public static function normalizeDatesInput(array $input, array $checklist): array
    {
        $dates = [];

        foreach (self::keysRequiringDate() as $key) {
            $raw = $input[$key] ?? null;
            $dates[$key] = ($checklist[$key] ?? false) && filled($raw)
                ? (string) $raw
                : null;
        }

        return $dates;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, bool|array<string, string|null>>
     */
    public static function normalizeInput(array $input, RentalContract $contract): array
    {
        $normalized = [];

        foreach (self::applicableKeys($contract) as $key) {
            $normalized[$key] = filter_var($input[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        $datesInput = is_array($input['dates'] ?? null) ? $input['dates'] : [];
        $normalized['dates'] = self::normalizeDatesInput($datesInput, $normalized);

        return $normalized;
    }
}
