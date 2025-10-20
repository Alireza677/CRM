<div dir="rtl" style="font-family:Tahoma,Arial,sans-serif;">
    <h2>گزارش زمان‌بندی شده</h2>
    <p>عنوان گزارش: <strong>{{ $report->title }}</strong></p>
    <p>تاریخ اجرا: {{ jdate($generated_at)->format('Y/m/d H:i') }}</p>
    <p>
        مشاهده در سیستم:
        <a href="{{ $link }}">(لینک اجرا)</a>
    </p>
    <p>فایل خروجی به این ایمیل پیوست شده است.</p>
</div>

