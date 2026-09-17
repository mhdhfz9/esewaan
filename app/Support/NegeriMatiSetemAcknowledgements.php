<?php

namespace App\Support;

class NegeriMatiSetemAcknowledgements
{
    public const MATI_SETEM_SELESAI = 'mati_setem_acknowledged';

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            self::MATI_SETEM_SELESAI,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::MATI_SETEM_SELESAI => 'Dokumen perjanjian telah dimatikan setem dan diedarkan kepada pemilik premis.',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, bool>
     */
    public static function normalizeInput(array $input): array
    {
        $acknowledgements = [];

        foreach (self::keys() as $key) {
            $acknowledgements[$key] = filter_var($input[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        return $acknowledgements;
    }
}
