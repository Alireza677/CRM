<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; direction: rtl; font-size: 12px; }
        .header { margin-bottom: 12px; }
        .title { font-size: 18px; font-weight: bold; }
        .meta { color: #555; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: right; }
        th { background: #f5f5f5; }
        .summary { margin-top: 10px; background: #f9f9f9; padding: 8px; }
        .filters { margin-top: 8px; font-size: 11px; }
        @page { margin: 90px 30px 60px 30px; }
        footer { position: fixed; left: 0; right: 0; bottom: -10px; text-align: center; font-size: 10px; color: #999; }
    </style>
    <title>گزارش - {{ $report->title }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
</head>
<body>
    <div class="header">
        <div class="title">{{ $report->title }}</div>
        <div class="meta">
            <span>سازنده: {{ $report->creator->name ?? '—' }}</span>
            <span style="margin-right:12px;">تاریخ تولید: {{ jdate($generated_at)->format('Y/m/d H:i') }}</span>
        </div>
        @if(!empty($filters))
            <div class="filters">
                <strong>خلاصه فیلترها:</strong>
                @foreach($filters as $f)
                    <span style="margin-left:8px;">{{ $f['field'] ?? '' }} {{ $f['operator'] ?? '' }} {{ $f['value'] ?? '' }}</span>
                @endforeach
            </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                @foreach($columns as $c)
                    <th>{{ $c }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    @foreach($columns as $c)
                        <td>{{ $row[$c] ?? '' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(!empty($summary))
        <div class="summary">
            <strong>خلاصه محاسبات:</strong>
            @foreach($summary as $k => $v)
                <span style="margin-left:10px;">{{ $k }}: {{ number_format($v, 0, '.', ',') }}</span>
            @endforeach
        </div>
    @endif
    <footer>
        Confidential – {{ config('reports_ui.watermark_text', config('app.name')) }}
    </footer>
</body>
</html>
