<?php

namespace App\Support;

use App\Models\AppSetting;

class FollowupReminderSettings
{
    public const KEY = 'activities.followup_reminder_offsets';
    private const MAX_MINUTES = 43200; // 30 days

    public static function defaultRows(): array
    {
        return [
            ['minutes' => 1440, 'time_of_day' => null], // 1 day قبل از موعد
        ];
    }

    public static function getRows(): array
    {
        $raw = AppSetting::getValue(static::KEY, null);
        if ($raw === null || $raw === '') {
            return static::defaultRows();
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return static::defaultRows();
        }

        // Backward compatibility: array of numbers
        $isFlat = true;
        foreach ($decoded as $item) {
            if (!is_numeric($item)) {
                $isFlat = false;
                break;
            }
        }

        if ($isFlat) {
            $offsets = static::normalizeOffsets($decoded);
            return array_map(fn($m) => ['minutes' => $m, 'time_of_day' => null], $offsets);
        }

        return static::normalizeRows($decoded);
    }

    public static function setRows(array $rows): void
    {
        $normalized = static::normalizeRows($rows);
        AppSetting::setValue(static::KEY, json_encode(array_values($normalized)));
    }

    public static function normalizeOffsets(array $offsets): array
    {
        $clean = [];
        foreach ($offsets as $offset) {
            if (!is_numeric($offset)) {
                continue;
            }
            $minutes = (int) round((float) $offset);
            if ($minutes < 1) {
                continue;
            }
            if ($minutes > static::MAX_MINUTES) {
                $minutes = static::MAX_MINUTES;
            }
            $clean[] = $minutes;
        }

        $clean = array_values(array_unique($clean));
        rsort($clean, SORT_NUMERIC);
        return $clean;
    }

    public static function normalizeRows(array $rows): array
    {
        $clean = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $minutes = $row['minutes'] ?? null;
            if (!is_numeric($minutes)) {
                continue;
            }
            $minutes = (int) round((float) $minutes);
            if ($minutes < 1) {
                continue;
            }
            if ($minutes > static::MAX_MINUTES) {
                $minutes = static::MAX_MINUTES;
            }

            $time = isset($row['time_of_day']) ? trim((string) $row['time_of_day']) : '';
            if ($time === '') {
                $time = null;
            } elseif (!preg_match('/^\d{2}:\d{2}$/', $time)) {
                $time = null;
            }

            $clean[] = [
                'minutes' => $minutes,
                'time_of_day' => $time,
            ];
        }

        usort($clean, fn($a, $b) => ($b['minutes'] ?? 0) <=> ($a['minutes'] ?? 0));
        return $clean;
    }

    public static function toFormRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $minutes = (int) ($row['minutes'] ?? 0);
            if ($minutes < 1) {
                continue;
            }

            if ($minutes % 1440 === 0) {
                $out[] = [
                    'value' => (int) ($minutes / 1440),
                    'unit' => 'days',
                    'time' => $row['time_of_day'] ?? null,
                ];
                continue;
            }

            if ($minutes % 60 === 0) {
                $out[] = [
                    'value' => (int) ($minutes / 60),
                    'unit' => 'hours',
                    'time' => $row['time_of_day'] ?? null,
                ];
                continue;
            }

            $out[] = [
                'value' => $minutes,
                'unit' => 'minutes',
                'time' => $row['time_of_day'] ?? null,
            ];
        }

        return $out;
    }
}
