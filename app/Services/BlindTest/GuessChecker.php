<?php

namespace App\Services\BlindTest;

use Illuminate\Support\Str;

class GuessChecker
{
    public static function normalize(string $value): string
    {
        $value = Str::lower(Str::ascii($value));
        $value = str_replace('&', ' and ', $value);
        $value = preg_replace('/[^a-z0-9 ]+/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', trim($value));

        return $value;
    }

    public static function matches(string $guess, string $target): bool
    {
        $guess = self::normalize($guess);
        $target = self::normalize($target);

        if ($guess === '' || $target === '') {
            return false;
        }

        // Exact match, or the guess contains the full target — lets players type
        // "artist - title" in a single field and still match each part.
        if ($guess === $target || str_contains($guess, $target)) {
            return true;
        }

        // Otherwise, only tolerate small typos (a couple of edited characters),
        // not a truncated or partial answer missing whole words.
        $tolerance = max(1, (int) floor(mb_strlen($target) * 0.15));

        return levenshtein($guess, $target) <= $tolerance;
    }
}
