<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $subject }}</title>
    <style>body{font-family:tahoma,iransans,arial;direction:rtl;text-align:right}</style>
    </head>
<body>
    <h3 style="margin-top:0">{{ $subject }}</h3>
    <p style="white-space:pre-line">{{ $body }}</p>
    @if(!empty($url))
        <p>
            <a href="{{ $url }}" style="display:inline-block;background:#2563eb;color:#fff;padding:8px 12px;border-radius:6px;text-decoration:none">مشاهده در سیستم</a>
        </p>
    @endif
</body>
</html>

