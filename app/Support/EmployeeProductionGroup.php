<?php

namespace App\Support;

use Illuminate\Support\Str;

final class EmployeeProductionGroup
{
    public static function fromCargo(?string $cargo): ?string
    {
        $text = Str::ascii(Str::lower(trim((string) $cargo)));
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text) ?? $text;

        if (str_contains($text, 'rezag')) {
            return 'rezago';
        }

        if (
            str_contains($text, 'anill')
            || str_contains($text, 'celofan')
            || str_contains($text, 'sellad')
            || str_contains($text, 'etiquet')
        ) {
            return 'anillado';
        }

        if (
            str_contains($text, 'llenad')
            || str_contains($text, 'embasad')
            || str_contains($text, 'paquet')
        ) {
            return 'llenado';
        }

        return null;
    }
}
