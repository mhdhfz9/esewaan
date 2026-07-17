<?php

namespace App\Support;

class BuildingTypes
{
    public const KOMPLEKS_KERAJAAN = 'kompleks kerajaan';

    public const KOMERSIAL = 'komersial';

    public const RUMAH_KEDIAMAN = 'rumah kediaman';

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            self::KOMPLEKS_KERAJAAN => 'Kompleks kerajaan',
            self::KOMERSIAL => 'Komersial',
            self::RUMAH_KEDIAMAN => 'Rumah Kediaman',
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
