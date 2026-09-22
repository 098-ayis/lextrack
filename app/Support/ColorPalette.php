<?php

namespace App\Support;

final class ColorPalette
{
    /**
     * @return array<string, array<string, string>>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::families() as $family => $shades) {
            foreach ($shades as $shade => $hex) {
                $options[$family][$hex] = sprintf(
                    '<span style="display:inline-flex;align-items:center;gap:.5rem">'
                    . '<span style="display:inline-block;width:.9rem;height:.9rem;border-radius:9999px;background:%1$s;border:1px solid rgb(148 163 184 / .7)"></span>'
                    . '<span>%2$s</span><span style="opacity:.65">%1$s</span></span>',
                    $hex,
                    ucfirst($shade),
                );
            }
        }

        return $options;
    }

    /**
     * @return array<string, array{light: string, standard: string, dark: string}>
     */
    public static function families(): array
    {
        return [
            'Emerald' => ['light' => '#A7F3D0', 'standard' => '#059669', 'dark' => '#065F46'],
            'Blue' => ['light' => '#BFDBFE', 'standard' => '#2563EB', 'dark' => '#1E3A8A'],
            'Indigo' => ['light' => '#C7D2FE', 'standard' => '#4F46E5', 'dark' => '#312E81'],
            'Violet' => ['light' => '#DDD6FE', 'standard' => '#7C3AED', 'dark' => '#4C1D95'],
            'Amber' => ['light' => '#FDE68A', 'standard' => '#D97706', 'dark' => '#92400E'],
            'Rose' => ['light' => '#FECDD3', 'standard' => '#E11D48', 'dark' => '#9F1239'],
            'Cyan' => ['light' => '#A5F3FC', 'standard' => '#0891B2', 'dark' => '#164E63'],
            'Teal' => ['light' => '#99F6E4', 'standard' => '#0F766E', 'dark' => '#134E4A'],
            'Slate' => ['light' => '#CBD5E1', 'standard' => '#475569', 'dark' => '#1E293B'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function presets(): array
    {
        return [
            '#B00000', '#FF0000', '#FF9900', '#FFFF00', '#00FF00',
            '#00FFFF', '#4A86E8', '#0000FF', '#9900FF', '#FF00FF',
        ];
    }

    /**
     * The two fixed rows shown in the color picker.
     *
     * @return array<int, array<int, string>>
     */
    public static function presetGrid(): array
    {
        $grayscale = [
            '#000000', '#404040', '#666666', '#808080', '#A6A6A6',
            '#BFBFBF', '#D9D9D9', '#F2F2F2', '#F8F8F8', '#FFFFFF',
        ];

        return [$grayscale, self::presets()];
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        $values = [];

        foreach (self::families() as $colors) {
            for ($shade = 0; $shade <= 100; $shade++) {
                $values[] = self::shade($colors, $shade);
            }
        }

        return array_values(array_unique($values));
    }

    /**
     * @param  array{light: string, standard: string, dark: string}  $colors
     */
    public static function shade(array $colors, int $position): string
    {
        $position = max(0, min(100, $position));

        if ($position <= 50) {
            return self::mix($colors['light'], $colors['standard'], $position / 50);
        }

        return self::mix($colors['standard'], $colors['dark'], ($position - 50) / 50);
    }

    public static function presetShade(string $baseColor, int $position): string
    {
        $baseColor = strtoupper($baseColor);

        return self::shade([
            'light' => self::mix('#FFFFFF', $baseColor, 0.35),
            'standard' => $baseColor,
            'dark' => self::mix($baseColor, '#000000', 0.55),
        ], $position);
    }

    private static function mix(string $start, string $end, float $amount): string
    {
        $first = self::hexToRgb($start);
        $second = self::hexToRgb($end);

        return sprintf(
            '#%02X%02X%02X',
            (int) round($first[0] + (($second[0] - $first[0]) * $amount)),
            (int) round($first[1] + (($second[1] - $first[1]) * $amount)),
            (int) round($first[2] + (($second[2] - $first[2]) * $amount)),
        );
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function hexToRgb(string $hex): array
    {
        $value = ltrim($hex, '#');

        return [
            (int) hexdec(substr($value, 0, 2)),
            (int) hexdec(substr($value, 2, 2)),
            (int) hexdec(substr($value, 4, 2)),
        ];
    }
}
