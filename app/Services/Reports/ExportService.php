<?php

namespace App\Services\Reports;

use App\Models\Report;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportService
{
    public function __construct(protected QueryEngine $engine)
    {
    }

    protected function filename(Report $report, string $ext): string
    {
        $slug = Str::slug($report->title ?: 'report', '_');
        return sprintf('report_%d_%s_%s.%s', $report->id, $slug, now()->format('Ymd_His'), $ext);
    }

    protected function buildFirstPage(array $query, bool $cache, int $page): array
    {
        if ($cache) {
            $sorted = Arr::sortRecursive($query);
            $hash = hash('sha256', json_encode($sorted));
            $cacheKey = sprintf('report:preview:%s:page:%d', $hash, $page);
            return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($query, $page) {
                $query['page'] = $page;
                return $this->engine->build($query);
            });
        }
        $query['page'] = $page;
        return $this->engine->build($query);
    }

    public function exportCsv(Report $report, ?int $page = null, bool $cache = false): StreamedResponse
    {
        return $this->exportExcelLike($report, 'csv', $page, $cache);
    }

    public function exportXlsx(Report $report, ?int $page = null, bool $cache = false): StreamedResponse
    {
        return $this->exportExcelLike($report, 'xlsx', $page, $cache);
    }

    protected function exportExcelLike(Report $report, string $format, ?int $page, bool $cache): StreamedResponse
    {
        $max = (int) config('reports.max_export_rows', 100000);
        $query = $report->query_json ?? [];
        $query = is_array($query) ? $query : [];
        if (!isset($query['model']) || !$query['model']) {
            abort(422, 'Report has no model configured.');
        }

        $first = $this->buildFirstPage($query, $cache, $page ?? 1);
        $columns = $first['columns'] ?? [];
        $perPage = (int) ($first['meta']['per_page'] ?? 15);
        $total = (int) ($first['meta']['total'] ?? count($first['rows'] ?? []));

        if ($page !== null) {
            // Export only the requested page
            $gen = function () use ($columns, $first, $report) {
                foreach ($first['rows'] as $r) {
                    yield array_map(fn($c) => $r[$c] ?? '', $columns);
                }
                // Watermark footer row
                yield [sprintf('Confidential – %s', config('reports_ui.watermark_text', config('app.name')))] + array_fill(1, max(0, count($columns)-1), '');
            };
            $export = new class($columns, $gen) implements FromGenerator, WithHeadings {
                public function __construct(private array $headings, private \Closure $gen) {}
                public function generator(): \Generator { $g = ($this->gen)(); foreach ($g as $row) yield $row; }
                public function headings(): array { return $this->headings; }
            };
        } else {
            // Export all rows up to max, chunked by page
            if ($total > $max) {
                abort(422, 'تعداد ردیف‌ها بیشتر از حد مجاز برای خروجی است.');
            }
            $pages = max(1, (int) ceil($total / max(1, $perPage)));
            $export = new class($columns, $pages, $query, $this, $cache) implements FromGenerator, WithHeadings {
                public function __construct(private array $headings, private int $pages, private array $query, private ExportService $svc, private bool $cache) {}
                public function generator(): \Generator {
                    for ($p = 1; $p <= $this->pages; $p++) {
                        $res = $this->svc->buildFirstPage($this->query, $this->cache, $p);
                        $columns = $res['columns'] ?? [];
                        foreach ($res['rows'] ?? [] as $r) {
                            yield array_map(fn($c) => $r[$c] ?? '', $columns);
                        }
                    }
                    // watermark row
                    yield [sprintf('Confidential – %s', config('reports_ui.watermark_text', config('app.name')))] + array_fill(1, max(0, count($this->headings)-1), '');
                }
                public function headings(): array { return $this->headings; }
            };
        }

        $filename = $this->filename($report, $format);
        $writerType = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;
        return Excel::download($export, $filename, $writerType);
    }

    public function exportPdf(Report $report, ?int $page = null, bool $cache = false)
    {
        $max = (int) config('reports.max_export_rows', 100000);
        $query = $report->query_json ?? [];
        $query = is_array($query) ? $query : [];
        if (!isset($query['model']) || !$query['model']) {
            abort(422, 'Report has no model configured.');
        }

        // For PDF: if page is present, just that page; otherwise all (up to max)
        $first = $this->buildFirstPage($query, $cache, $page ?? 1);
        $columns = $first['columns'] ?? [];
        $perPage = (int) ($first['meta']['per_page'] ?? 15);
        $total = (int) ($first['meta']['total'] ?? count($first['rows'] ?? []));

        $rows = [];
        if ($page !== null) {
            $rows = $first['rows'] ?? [];
        } else {
            if ($total > $max) {
                abort(422, 'تعداد ردیف‌ها بیشتر از حد مجاز برای خروجی است.');
            }
            $pages = max(1, (int) ceil($total / max(1, $perPage)));
            for ($p = 1; $p <= $pages; $p++) {
                $res = $p === 1 ? $first : $this->buildFirstPage($query, $cache, $p);
                foreach ($res['rows'] ?? [] as $r) {
                    $rows[] = $r;
                }
            }
        }

        $summary = $first['summary'] ?? null; // summary based on filters
        $data = [
            'report' => $report,
            'columns' => $columns,
            'rows' => $rows,
            'summary' => $summary,
            'filters' => $query['filters'] ?? [],
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('reports.export_pdf', $data)->setPaper('a4', 'portrait');
        return $pdf->download($this->filename($report, 'pdf'));
    }
}
