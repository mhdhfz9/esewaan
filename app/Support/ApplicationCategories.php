<?php

namespace App\Support;

class ApplicationCategories
{
    public const BARU = 'baru';

    public const PINDAH = 'pindah';

    public const LANJUTAN = 'lanjutan';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::BARU,
            self::PINDAH,
            self::LANJUTAN,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::BARU => 'Baru',
            self::PINDAH => 'Pindah',
            self::LANJUTAN => 'Lanjutan',
        ];
    }

    /**
     * @return list<int>
     */
    public static function stepSequence(?string $kategori): array
    {
        return match ($kategori) {
            self::LANJUTAN => [1, 3, 5],
            self::BARU, self::PINDAH => [2, 3, 5],
            default => [2, 3, 5],
        };
    }

    public static function label(?string $kategori): string
    {
        return self::labels()[$kategori] ?? '–';
    }

    public static function description(?string $kategori): string
    {
        return match ($kategori) {
            self::BARU => 'Permohonan baharu.',
            self::PINDAH => 'Permohonan pindah premis.',
            self::LANJUTAN => 'Permohonan lanjutan kontrak.',
            default => 'Sila pilih kategori permohonan.',
        };
    }

    /**
     * Pindah and lanjutan reuse an existing contract; baru does not.
     */
    public static function isFollowUp(?string $kategori): bool
    {
        return in_array($kategori, [self::PINDAH, self::LANJUTAN], true);
    }
}
