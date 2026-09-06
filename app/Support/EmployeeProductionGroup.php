<?php

namespace App\Support;

use Illuminate\Support\Str;

final class EmployeeProductionGroup
{
    public static function fromCargo(?string $cargo, ?string $codigo = null): ?string
    {
        $codigoTrim = trim((string) $codigo);
        if ($codigoTrim === '8219' || $codigoTrim === '8217') {
            return 'rezago';
        }

        $text = Str::ascii(Str::lower(trim((string) $cargo)));
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text) ?? $text;
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

        if ($text === '') {
            return null;
        }

        if (str_contains($text, 'rezag') || str_contains($text, 'resag')) {
            return 'rezago';
        }

        if (str_contains($text, 'limpia') || str_contains($text, 'limpi')) {
            return 'limpieza';
        }

        if (
            str_contains($text, 'anill')
            || str_contains($text, 'celofan')
            || str_contains($text, 'etiquet')
            || str_contains($text, 'pega')
        ) {
            return 'anillado';
        }

        if (
            str_contains($text, 'llenad')
            || str_contains($text, 'embasad')
            || str_contains($text, 'paquet')
            || str_contains($text, 'sellado')
        ) {
            return 'llenado';
        }

        return null;
    }
}
