<?php

namespace App\Console\Commands;

use App\Models\Holiday;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;

class ImportHolidays extends Command
{
    protected $signature = 'holidays:import {year} {--force : Delete existing rows for that year/source before import}';
    protected $description = 'Import Iranian holidays/occasions from shamsi-holidays JSON';

    public function handle(): int
    {
        $year = (int) $this->argument('year');

        if ($year < 1300 || $year > 1600) {
            $this->error('Invalid Jalali year.');
            return self::FAILURE;
        }

        $source   = 'shamsi-holidays';
        $url      = "https://raw.githubusercontent.com/hasan-ahani/shamsi-holidays/main/holidays/{$year}.json";
        $cacheKey = "holidays:json:{$year}";
        $now      = now();

        // اگر force زده شد، بهتره کش هم دور زده بشه
        if ($this->option('force')) {
            Cache::forget($cacheKey);
        }

        try {
            $data = Cache::remember($cacheKey, $now->copy()->addHours(6), function () use ($url) {
                $resp = Http::timeout(20)->acceptJson()->get($url);
                $resp->throw();
                return $resp->json();
            });
        } catch (\Throwable $e) {
            Log::warning('holidays:import failed to download JSON', [
                'url'   => $url,
                'year'  => $year,
                'error' => $e->getMessage(),
            ]);

            $this->error('Failed to download holidays JSON.');
            return self::FAILURE;
        }

        if (!is_array($data)) {
            Log::warning('holidays:import invalid JSON structure (root is not array)', [
                'year' => $year,
                'url'  => $url,
                'type' => gettype($data),
            ]);

            $this->error('Invalid JSON structure.');
            return self::FAILURE;
        }

        $rows = [];

        foreach ($data as $day) {
            if (!is_array($day)) {
                continue;
            }

            $jalaliDateRaw = (string) ($day['date'] ?? '');
            $jalaliDate    = $this->normalizeDigits(trim($jalaliDateRaw));

            if ($jalaliDate === '') {
                continue;
            }

            // تبدیل تاریخ شمسی به میلادی
            try {
                $gregorianDate = Jalalian::fromFormat('Y-m-d', $jalaliDate)
                    ->toCarbon()
                    ->toDateString();
            } catch (\Throwable $e) {
                Log::warning('holidays:import invalid jalali date', [
                    'year' => $year,
                    'date' => $jalaliDate,
                ]);
                continue;
            }

            // ساختار json ممکنه events داشته باشه یا نداشته باشه
            $events = $day['events'] ?? null;

            if (!is_array($events) || count($events) === 0) {
                $fallbackTitle = (string) ($day['title'] ?? $day['description'] ?? '');
                $fallbackTitle = trim($fallbackTitle);

                if ($fallbackTitle === '') {
                    continue;
                }

                $rows[] = [
                    'date'        => $gregorianDate,
                    'jalali_date' => $jalaliDate,
                    'title'       => $fallbackTitle,
                    'is_holiday'  => (bool) ($day['is_holiday'] ?? false),
                    'source'      => $source,
                    'external_id' => sha1("{$source}|{$jalaliDate}|{$fallbackTitle}"),
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];

                continue;
            }

            foreach ($events as $event) {
                if (!is_array($event)) {
                    continue;
                }

                $title = trim((string) ($event['description'] ?? $event['title'] ?? ''));

                if ($title === '') {
                    continue;
                }

                $isHoliday = (bool) ($event['is_holiday'] ?? $day['is_holiday'] ?? false);

                $rows[] = [
                    'date'        => $gregorianDate,
                    'jalali_date' => $jalaliDate,
                    'title'       => $title,
                    'is_holiday'  => $isHoliday,
                    'source'      => $source,
                    'external_id' => sha1("{$source}|{$jalaliDate}|{$title}"),
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }
        }

        if (count($rows) === 0) {
            $this->warn("No holiday rows to import for {$year}.");
            return self::SUCCESS;
        }

        // اگر force: داده‌های همان سال/سورس حذف شوند
        if ($this->option('force')) {
            $yearStart = Jalalian::fromFormat('Y-m-d', "{$year}-01-01")->toCarbon()->startOfDay();
            $yearEnd   = (clone $yearStart)->addYear()->subDay()->endOfDay();

            Holiday::query()
                ->where('source', $source)
                ->whereBetween('date', [$yearStart->toDateString(), $yearEnd->toDateString()])
                ->delete();
        }

        // upsert برای جلوگیری از تکرار
        Holiday::upsert(
            $rows,
            ['date', 'title', 'source'],
            ['jalali_date', 'is_holiday', 'external_id', 'updated_at']
        );

        $this->info("Imported holidays for {$year}. Rows: " . count($rows));
        return self::SUCCESS;
    }

    private function normalizeDigits(string $value): string
    {
        $map = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];

        $v = strtr($value, $map);
        $v = str_replace('/', '-', $v);

        return $v;
    }
}
