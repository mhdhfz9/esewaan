<?php

namespace App\Support;

class NegeriMatiSetemAcknowledgements
{
    public const MATI_SETEM_LHDN = 'mati_setem_lhdn_acknowledged';

    public const EDARAN_PEMILIK = 'edaran_pemilik_acknowledged';

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            self::MATI_SETEM_LHDN,
            self::EDARAN_PEMILIK,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::MATI_SETEM_LHDN => '3 Salinan Dokumen Perjanjian telah dimatikan setem di LHDN',
            self::EDARAN_PEMILIK => '1 Salinan Dokumen Perjanjian telah diedar kepada Pemilik Premis',
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
