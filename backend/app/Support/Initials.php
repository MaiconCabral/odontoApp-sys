<?php

namespace App\Support;

use Illuminate\Support\Str;

class Initials
{
    /**
     * Initials of the first and last words of a name.
     * First and last words, the same rule as patientInitials in the frontend.
     */
    public static function of(string $name): string
    {
        $titleless = preg_replace('/^dra?\.?\s+/iu', '', trim($name));
        $words = preg_split('/\s+/', $titleless, flags: PREG_SPLIT_NO_EMPTY);
        $edges = count($words) > 1 ? [$words[0], end($words)] : $words;

        return Str::upper(implode('', array_map(fn (string $word) => Str::substr($word, 0, 1), $edges)));
    }
}
