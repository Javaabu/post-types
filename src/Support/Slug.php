<?php

namespace Javaabu\PostTypes\Support;

use Illuminate\Support\Str;

/**
 * URL slugs that keep Thaana: Str::slug() drops the vowel signs (fili), which
 * turns Dhivehi words into consonants only
 */
class Slug
{
    public static function make(?string $value, int $max_length = 190): string
    {
        $value = Str::lower(trim((string) $value));

        // Letters, combining marks (fili) and numbers; everything else separates words
        $value = (string) preg_replace('/[^\pL\pM\pN]+/u', '-', $value);
        $value = trim($value, '-');

        return rtrim(mb_substr($value, 0, $max_length), '-');
    }
}
