<?php

namespace App\Support;

class PremiseStatuses
{
    public const KOSONG = 'kosong';

    public const RENTED = 'sedang_disewa';

    public const DALAM_PENYELENGGARAAN = 'dalam_penyelenggaraan';

    public const DALAM_PROSES_KELULUSAN = 'dalam_proses_kelulusan';

    public const TIDAK_AKTIF = 'tidak_aktif';

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            self::KOSONG => 'Kosong',
            self::RENTED => 'Sedang disewa',
            self::DALAM_PENYELENGGARAAN => 'Dalam penyelenggaraan',
            self::DALAM_PROSES_KELULUSAN => 'Dalam proses kelulusan',
            self::TIDAK_AKTIF => 'Tidak aktif',
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_keys(self::all());
    }
}
