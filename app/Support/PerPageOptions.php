<?php

namespace App\Support;

final class PerPageOptions
{
    private const BASE_OPTIONS = [10, 25, 50, 100];

    public static function forTotal(int $total): array
    {
        $options = self::BASE_OPTIONS;
        $total = max($total, 0);

        if ($total <= 500) {
            return $options;
        }

        $step = self::dynamicStep($total);

        if ($step > 1000 && $total > 1000) {
            $options[] = 1000;
        }

        for ($value = $step; $value < $total; $value += $step) {
            $options[] = $value;
        }

        $options = array_values(array_unique($options));
        sort($options);

        return $options;
    }

    public static function resolve(mixed $requested, int $total, int $default): int|string
    {
        if ($requested === 'all') {
            return 'all';
        }

        $value = is_int($requested) || (is_string($requested) && ctype_digit($requested))
            ? (int) $requested
            : 0;

        return in_array($value, self::forTotal($total), true) ? $value : $default;
    }

    public static function pageSize(int|string $selected, int $total): int
    {
        return $selected === 'all' ? max($total, 1) : $selected;
    }

    private static function dynamicStep(int $total): int
    {
        $target = max($total / 4, 500);
        $magnitude = 10 ** floor(log10($target));
        $candidates = array_map(
            fn (float $multiplier) => (int) round($multiplier * $magnitude),
            [1, 2.5, 5, 10]
        );
        $step = 500;
        $smallestDifference = INF;

        foreach ($candidates as $candidate) {
            if ($candidate < 500) {
                continue;
            }

            $difference = abs($candidate - $target);

            if ($difference <= $smallestDifference) {
                $step = $candidate;
                $smallestDifference = $difference;
            }
        }

        return $step;
    }
}
