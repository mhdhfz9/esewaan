<?php

namespace App\Support;

class MalaysianStates
{
    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'Johor' => 'Johor',
            'Kedah' => 'Kedah',
            'Kelantan' => 'Kelantan',
            'Melaka' => 'Melaka',
            'Negeri Sembilan' => 'Negeri Sembilan',
            'Pahang' => 'Pahang',
            'Perak' => 'Perak',
            'Perlis' => 'Perlis',
            'Pulau Pinang' => 'Pulau Pinang',
            'Sabah' => 'Sabah',
            'Sarawak' => 'Sarawak',
            'Selangor' => 'Selangor',
            'Terengganu' => 'Terengganu',
            'W.P. Kuala Lumpur' => 'W.P. Kuala Lumpur',
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
