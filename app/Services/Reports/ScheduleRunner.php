<?php

namespace App\Services\Reports;

use App\Models\Report;
use App\Models\ReportRun;
use App\Models\ReportSchedule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ScheduleRunner
{
    public function __construct(protected QueryEngine $engine)
    {
    }

    public function runDue(): void
    {
        $now = Carbon::now();
        $schedules = ReportSchedule::query()->where('active', true)->get();

        foreach ($schedules as $s) {
            if (!$this->isDue($s, $now)) { continue; }
            try {
                $this->executeSchedule($s);
            } catch (\Throwable $e) {
                Log::error('ScheduleRunner error', ['schedule_id' => $s->id, 'message' => $e->getMessage()]);
            }
        }
    }

    protected function isDue(ReportSchedule $s, Carbon $now): bool
    {
        // Check frequency date conditions
        switch ($s->frequency) {
            case 'weekly':
                if ($s->weekday === null) return false;
                if ((int)$now->dayOfWeek !== (int)$s->weekday) return false;
                break;
            case 'monthly':
                if ($s->day_of_month === null) return false;
                if ((int)$now->day !== (int)$s->day_of_month) return false;
                break;
            case 'daily':
            case 'custom':
            default:
                // no extra date check
                break;
        }

        // Check time window within +/- 7 minutes of time_of_day
        $tod = Carbon::parse($s->time_of_day, $now->timezone)->setDate($now->year, $now->month, $now->day);
        $diff = abs($now->diffInMinutes($tod, false));
        return $diff <= 7; // allow window, since command runs every 15 min
    }

    protected function executeSchedule(ReportSchedule $s): void
    {
        $report = $s->report; if (!$report || !$report->is_active) return;
        $query = $report->query_json ?? [];
        if (!is_array($query) || empty($query) || empty($query['model'])) return;

        // Build first page to get meta and columns
        $query['page'] = 1;
        $result = $this->engine->build($query);
        $columns = $result['columns'] ?? [];
        $perPage = (int)($result['meta']['per_page'] ?? 15);
        $total = (int)($result['meta']['total'] ?? count($result['rows'] ?? []));

        $filename = sprintf('report_%d_%s_%s', $report->id, Str::slug($report->title ?: 'report', '_'), now()->format('Ymd_His'));
        $dir = 'reports/exports';
        Storage::makeDirectory($dir);

        switch ($s->export_format) {
            case 'csv':
            case 'xlsx':
                $writerType = $s->export_format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;
                $pages = max(1, (int) ceil($total / max(1,$perPage)));
                $export = new class($columns, $pages, $query, $this) implements FromGenerator, WithHeadings {
                    public function __construct(private array $headings, private int $pages, private array $query, private ScheduleRunner $runner) {}
                    public function generator(): \Generator {
                        for ($p = 1; $p <= $this->pages; $p++) {
                            $q = $this->query; $q['page'] = $p; $res = $this->runner->engine->build($q);
                            $columns = $res['columns'] ?? [];
                            foreach ($res['rows'] ?? [] as $r) {
                                yield array_map(fn($c) => $r[$c] ?? '', $columns);
                            }
                        }
                    }
                    public function headings(): array { return $this->headings; }
                };
                $path = "$dir/{$filename}.{$s->export_format}";
                Excel::store($export, $path, null, $writerType);
                $fullPath = storage_path('app/'.$path);
                break;
            case 'pdf':
            default:
                $rows = [];
                $pages = max(1, (int) ceil($total / max(1,$perPage)));
                for ($p = 1; $p <= $pages; $p++) {
                    $q = $query; $q['page'] = $p; $res = $this->engine->build($q);
                    foreach ($res['rows'] ?? [] as $r) { $rows[] = $r; }
                }
                $pdf = Pdf::loadView('reports.export_pdf', [
                    'report' => $report,
                    'columns' => $columns,
                    'rows' => $rows,
                    'summary' => $result['summary'] ?? null,
                    'filters' => $query['filters'] ?? [],
                    'generated_at' => now(),
                ])->setPaper('a4','portrait');
                $path = "$dir/{$filename}.pdf";
                $fullPath = storage_path('app/'.$path);
                $pdf->save($fullPath);
                break;
        }

        // Send email with attachment
        $emails = (array) $s->emails;
        if (!empty($emails)) {
            $data = [
                'report' => $report,
                'generated_at' => now(),
                'link' => route('reports.run', $report),
            ];
            Mail::send('emails.reports.scheduled_report', $data, function ($m) use ($emails, $fullPath, $report, $s) {
                $m->to($emails)->subject('گزارش زمان‌بندی شده: '.$report->title);
                $m->attach($fullPath);
            });
        }

        // Log run
        ReportRun::create([
            'report_id' => $report->id,
            'user_id' => $s->user_id,
            'executed_at' => now(),
            'exec_ms' => (int)($result['meta']['exec_ms'] ?? 0),
            'rows_count' => $total,
            'cache_used' => false,
        ]);

        \Log::channel('reports')->info('schedule-run', [
            'report_id' => $report->id,
            'user_id' => $s->user_id,
            'exec_ms' => $result['meta']['exec_ms'] ?? null,
            'rows_count' => $total,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}
