<?php

namespace App\Services\Merge;

class Normalizer
{
    public function normalizeEmail(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return mb_strtolower($value, 'UTF-8');
    }

    public function normalizePhone(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $digitMap = [
            "\u{06F0}" => '0', "\u{06F1}" => '1', "\u{06F2}" => '2', "\u{06F3}" => '3', "\u{06F4}" => '4',
            "\u{06F5}" => '5', "\u{06F6}" => '6', "\u{06F7}" => '7', "\u{06F8}" => '8', "\u{06F9}" => '9',
            "\u{0660}" => '0', "\u{0661}" => '1', "\u{0662}" => '2', "\u{0663}" => '3', "\u{0664}" => '4',
            "\u{0665}" => '5', "\u{0666}" => '6', "\u{0667}" => '7', "\u{0668}" => '8', "\u{0669}" => '9',
        ];

        $value = strtr($value, $digitMap);
        $value = preg_replace('/[^\d]/u', '', $value) ?? '';

        return $value !== '' ? $value : null;
    }

    public function normalizeName(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/\s+/u', ' ', $value) ?? '';
        $value = trim($value);

        return $value !== '' ? mb_strtolower($value, 'UTF-8') : null;
    }
}
