<?php

namespace App\Support;

class ListSearch
{
    public static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    public static function like(string $value): string
    {
        return '%'.self::escapeLike($value).'%';
    }

    /**
     * @param  array<string|int, string>  $map
     * @return list<string|int>
     */
    public static function matchingKeys(array $map, string $search): array
    {
        $needle = mb_strtolower(trim($search));

        if ($needle === '') {
            return [];
        }

        $matches = [];

        foreach ($map as $key => $label) {
            if (str_contains(mb_strtolower($label), $needle)) {
                $matches[] = $key;
            }
        }

        return $matches;
    }
}
