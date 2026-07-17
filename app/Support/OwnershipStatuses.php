<?php

namespace App\Support;

class OwnershipStatuses
{
    public const MILIK_SENDIRI = 'milik_sendiri';

    public const SEWA = 'sewa';

    public const PINDAH_MILIK = 'pindah_milikan';

    public const DALAM_SEMAKAN = 'dalam_semakan';

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            self::MILIK_SENDIRI => 'Milik sendiri',
            self::SEWA => 'Sewa',
            self::PINDAH_MILIK => 'Pindah milik',
            self::DALAM_SEMAKAN => 'Dalam semakan',
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
