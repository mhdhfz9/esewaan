<?php

namespace App\Support;

class HqDraftAcknowledgements
{
    public const TERIMA_DOKUMEN = 'terima_dokumen_acknowledged';

    public const PERJANJIAN_DITANDATANGANI = 'perjanjian_ditandatangani_acknowledged';

    public const SALINAN_PROMIS = 'salinan_promis_acknowledged';

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            self::TERIMA_DOKUMEN,
            self::PERJANJIAN_DITANDATANGANI,
            self::SALINAN_PROMIS,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::TERIMA_DOKUMEN => 'Cawangan Pembangunan AADK menerima dokumen perjanjian dan mengemukakan kepada TKPP AADK untuk tandatangan bagi pihak AADK/Kerajaan Malaysia.',
            self::PERJANJIAN_DITANDATANGANI => 'Perjanjian ditandatangani TKPP AADK dikembalikan kepada AADK Negeri untuk dimatikan setem dan edaran kepada pemilik premis.',
            self::SALINAN_PROMIS => '1 salinan perjanjian dihantar ke Cawangan Pembangunan AADK untuk dimuat naik ke dalam Sistem ProMIS dan rekod fail Cawangan Pembangunan.',
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
