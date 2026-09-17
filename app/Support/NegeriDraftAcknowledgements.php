<?php

namespace App\Support;

class NegeriDraftAcknowledgements
{
    public const DRAF_AKHIR_DITERIMA = 'draf_akhir_diterima_acknowledged';

    public const DOKUMEN_DISEDIAKAN = 'dokumen_perjanjian_disediakan_acknowledged';

    public const DOKUMEN_DITANDATANGANI = 'dokumen_perjanjian_ditandatangani_acknowledged';

    public const DOKUMEN_ASAL_DIHANTAR = 'dokumen_asal_dihantar_acknowledged';

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            self::DRAF_AKHIR_DITERIMA,
            self::DOKUMEN_DISEDIAKAN,
            self::DOKUMEN_DITANDATANGANI,
            self::DOKUMEN_ASAL_DIHANTAR,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::DRAF_AKHIR_DITERIMA => 'Draf akhir diterima untuk penyediaan dokumen perjanjian',
            self::DOKUMEN_DISEDIAKAN => '3 Salinan Dokumen Perjanjian telah disediakan',
            self::DOKUMEN_DITANDATANGANI => 'Dokumen Perjanjian telah ditandatangani oleh pemilik premis',
            self::DOKUMEN_ASAL_DIHANTAR => 'Dokumen Asal telah dihantar melalui Kurier / Serahan tangan kepada Cawangan Pembangunan',
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
