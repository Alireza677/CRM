@extends('layouts.app')

@php
    $breadcrumb = [
        ['title' => 'گزارش‌ها', 'url' => route('reports.index')],
        ['title' => 'اجرای گزارش'],
    ];
@endphp

@section('content')
<div class="py-6" dir="rtl">
    @include('components.toast')

    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">اجرای گزارش: {{ $report->title }}</h1>
        <div class="space-x-2 space-x-reverse">
            <a href="{{ route('reports.show',$report) }}" class="px-3 py-1 bg-gray-200 rounded">مشاهده گزارش</a>
            <a href="{{ request()->fullUrlWithQuery(['cache' => 1]) }}" class="px-3 py-1 bg-blue-100 text-blue-700 rounded">اجرا با کش</a>
        </div>
    </div>

    @if($message)
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-3 rounded">{{ $message }}</div>
    @elseif($result)
        @php
            $cfg = config('reports.models');
            $modelKey = $report->query_json['model'] ?? null;
            $labels = $modelKey && isset($cfg[$modelKey]['labels']) ? ($cfg[$modelKey]['labels'] ?? []) : [];
            $types  = $modelKey && isset($cfg[$modelKey]['fields']) ? ($cfg[$modelKey]['fields'] ?? []) : [];
            $pad = fn($n) => ($n < 10 ? '0' : '').$n;
            $toJalali = function($v) use ($pad) {
                if (!is_string($v) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/',$v,$m)) return $v;
                $gy=(int)$m[1]; $gm=(int)$m[2]; $gd=(int)$m[3];
                $g_d_m=[0,31,59,90,120,151,181,212,243,273,304,334];
                $jy = ($gy<=1600) ? 0 : 979; $gy -= ($gy<=1600) ? 621 : 1600; $gy2 = ($gm>2)?($gy+1):$gy;
                $days = 365*$gy + intdiv($gy2+3,4) - intdiv($gy2+99,100) + intdiv($gy2+399,400) - 80 + $gd + $g_d_m[$gm-1];
                $jy += 33*intdiv($days,12053); $days %= 12053; $jy += 4*intdiv($days,1461); $days %= 1461;
                if ($days > 365) { $jy += intdiv($days-1,365); $days = ($days-1)%365; }
                $jm = ($days < 186) ? 1+intdiv($days,31) : 7+intdiv($days-186,30);
                $jd = 1 + (($days < 186) ? ($days%31) : (($days-186)%30));
                $jy += ($report??false) ? 0 : 0; // keep scope quiet
                $date = ($jy+($gy<=1600?621:0)).'/'.$pad($jm).'/'.$pad($jd);
                if (!empty($m[4])) { return $date.' '.$m[4].':'.$m[5].(!empty($m[6])?(':'.$m[6]):''); }
                return $date;
            };
        @endphp
        <div class="bg-white rounded shadow p-4"
             x-data="reportRun()"
             x-init='init(@js($result), @js($report->query_json ?? []), @js(config("reports")))'>
            <div class="flex items-center justify-between mb-2 text-sm text-gray-600">
                <div>
                    زمان اجرا: {{ $result['meta']['exec_ms'] ?? 0 }}ms
                    &nbsp;–&nbsp;
                    کل ردیف‌ها: {{ $result['meta']['total'] ?? 0 }}
                </div>
                <div class="relative">
                    <button type="button" @click="openExport = !openExport" class="px-3 py-1 bg-gray-100 rounded">خروجی</button>
                    <div x-show="openExport" @click.away="openExport=false" class="absolute left-0 mt-1 w-40 bg-white border rounded shadow text-right">
                        <a class="block px-3 py-2 hover:bg-gray-50" :href="confirmExport('{{ route('reports.export.csv', $report) }}')">CSV</a>
                        <a class="block px-3 py-2 hover:bg-gray-50" :href="confirmExport('{{ route('reports.export.xlsx', $report) }}')">XLSX</a>
                        <a class="block px-3 py-2 hover:bg-gray-50" :href="confirmExport('{{ route('reports.export.pdf', $report) }}')">PDF</a>
                    </div>
                </div>
            </div>

            @php $maxExport = (int) config('reports.max_export_rows', 100000); @endphp
            @if(($result['meta']['total'] ?? 0) > $maxExport)
                <div class="mb-3 p-2 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded">
                    تعداد ردیف‌ها بیش از حد مجاز خروجی ({{ number_format($maxExport) }}) است. لطفاً فیلترها را محدودتر کنید یا خروجی را در چند مرحله بگیرید.
                </div>
            @endif

            @if(($result['meta']['total'] ?? 0) > 50000)
                <div class="mb-3 p-2 bg-red-50 border border-red-200 text-red-800 rounded">
                    هشدار: خروجی گرفتن از مجموعه‌داده بزرگ ممکن است زمان‌بر باشد.
                </div>
            @endif

            <div class="mb-3">
                <div class="inline-flex rounded overflow-hidden border">
                    <button type="button" @click="tab='table'" :class="tab==='table' ? 'bg-blue-600 text-white' : 'bg-gray-100'" class="px-3 py-1">جدول</button>
                    <button type="button" @click="tab='chart'" :class="tab==='chart' ? 'bg-blue-600 text-white' : 'bg-gray-100'" class="px-3 py-1">نمودار</button>
                </div>
            </div>

            <div x-show="tab==='table'" class="overflow-auto">
                <table class="min-w-full text-right">
                    <thead>
                        <tr>
                            @foreach(($result['columns'] ?? []) as $c)
                                <th class="px-2 py-1 bg-gray-50 border">{{ $labels[$c] ?? $c }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(($result['rows'] ?? []) as $row)
                            <tr class="border-b">
                                @foreach(($result['columns'] ?? []) as $c)
                                    @php $val = $row[$c] ?? ''; @endphp
                                    <td class="px-2 py-1">{{ in_array(($types[$c] ?? ''), ['date','datetime','timestamp'], true) ? $toJalali($val) : $val }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if(!empty($result['summary']))
                @php $currency = config('reports.currency'); @endphp
                <div class="mt-3 p-2 bg-gray-50 rounded">
                    <div class="font-semibold mb-1">خلاصه</div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        @foreach($result['summary'] as $k => $v)
                            @php
                                $formatted = number_format(
                                    $v,
                                    $currency['decimals'] ?? 0,
                                    $currency['decimal_sep'] ?? '.',
                                    $currency['thousands_sep'] ?? ','
                                );
                            @endphp
                            <div>
                                <span class="text-gray-600">{{ $k }}:</span>
                                <span>{{ $formatted }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div x-show="tab==='chart'" class="mt-4">
                <div class="flex items-center gap-2 mb-2">
                    @php $cols = $result['columns'] ?? []; @endphp
                    <label>محور X:</label>
                    <select x-model="chart.xField" class="border rounded p-1">
                        @foreach($cols as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>

                    <label>محور Y:</label>
                    <select x-model="chart.yMetric" class="border rounded p-1">
                        @foreach($cols as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>

                    <label>نوع نمودار:</label>
                    <select x-model="chart.type" class="border rounded p-1">
                        <option value="bar">ستونی</option>
                        <option value="line">خطی</option>
                        <option value="pie">دایره‌ای</option>
                    </select>

                    <button type="button" @click="renderChart()" class="px-3 py-1 bg-blue-600 text-white rounded">رسم</button>
                </div>

                <template x-if="chart.error">
                    <div class="mb-2 p-2 bg-red-50 text-red-700 rounded" x-text="chart.error"></div>
                </template>

                <div class="relative" style="min-height:250px">
                    <canvas id="reportChart"></canvas>
                </div>
            </div>

            @if(!config('app.assets_emergency'))
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            @endif
            <script>
                function reportRun(){
                    return {
                        data: null,
                        query: null,
                        cfg: null,
                        tab: 'table',
                        openExport: false,
                        chart: { type: 'bar', xField: null, yMetric: null, error: null, instance: null },

                        init(result, queryJson, cfg){
                            this.data = result;
                            this.query = queryJson || {};
                            this.cfg = cfg || {};

                            if ((this.query.aggregates||[])[0]) {
                                this.chart.yMetric = (this.query.aggregates[0].as
                                    || (this.query.aggregates[0].fn + '_' + (this.query.aggregates[0].field || 'all')));
                            }
                            this.chart.xField = (this.query.group_by||[])[0] || (this.data.columns||[])[0] || null;

                            window.reportChartData = {
                                columns: this.data.columns,
                                rows: this.data.rows,
                                groups: this.query.group_by||[],
                                aggs: this.query.aggregates||[]
                            };
                        },

                        confirmExport(base){
                            const q = new URLSearchParams(@json(request()->only('cache','page')));
                            const total = (this.data.meta && this.data.meta.total) ? this.data.meta.total : 0;
                            if (total > 50000) {
                                if (!confirm('خروجی گرفتن از مجموعه‌داده بزرگ ممکن است زمان‌بر باشد. ادامه می‌دهید؟')) {
                                    return '#';
                                }
                            }
                            return base + '?' + q.toString();
                        },

                        renderChart(){
                            this.chart.error = null;
                            if (!window.Chart) {
                                this.chart.error = 'Chart.js is not available.';
                                return;
                            }

                            const labels = [];
                            const datasets = [];
                            const xField = this.chart.xField;
                            const yMetric = this.chart.yMetric;

                            if (!xField || !yMetric) {
                                this.chart.error = 'لطفاً فیلدهای محور X و Y را انتخاب کنید.';
                                return;
                            }

                            const rows = this.data.rows || [];
                            const xGroups = {};
                            const secondGroup = (this.query.group_by||[])[1] || null;

                            if (secondGroup) {
                                // چندسری: گروه‌بندی بر اساس xField سپس secondGroup
                                rows.forEach(r => {
                                    const x = r[xField];
                                    const s = r[secondGroup];
                                    const val = Number(r[yMetric] ?? 0);
                                    if (!xGroups[x]) xGroups[x] = {};
                                    xGroups[x][s] = (xGroups[x][s] || 0) + val;
                                });
                                const seriesLabels = Array.from(new Set(rows.map(r => r[secondGroup])));
                                const allLabels = Object.keys(xGroups);
                                seriesLabels.forEach((sl, idx) => {
                                    datasets.push({
                                        label: sl || '—',
                                        data: allLabels.map(x => xGroups[x][sl] || 0),
                                        backgroundColor: `rgba(${(idx*70)%255}, 99, 132, 0.5)`
                                    });
                                });
                                labels.push(...allLabels);
                            } else {
                                // تک‌سری
                                const values = {};
                                rows.forEach(r => {
                                    const x = r[xField];
                                    const val = Number(r[yMetric] ?? 0);
                                    values[x] = (values[x] || 0) + val;
                                });
                                labels.push(...Object.keys(values));
                                datasets.push({
                                    label: yMetric,
                                    data: labels.map(l => values[l] || 0),
                                    backgroundColor: 'rgba(54, 162, 235, 0.5)'
                                });
                            }

                            try {
                                const ctx = document.getElementById('reportChart').getContext('2d');
                                if (this.chart.instance) this.chart.instance.destroy();
                                this.chart.instance = new Chart(ctx, {
                                    type: this.chart.type,
                                    data: { labels, datasets },
                                    options: { responsive: true, maintainAspectRatio: false }
                                });
                            } catch(e){
                                this.chart.error = 'نمایش نمودار با خطا مواجه شد.';
                            }
                        },
                    }
                }
            </script>
        </div>
    @endif
</div>
@endsection
