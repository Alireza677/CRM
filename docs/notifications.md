# تنظیمات و مسیریابی اعلان‌ها

این ماژول امکان مدیریت قوانین اعلان را به صورت ماتریسی فراهم می‌کند و اعلان‌ها را از طریق کانال‌های «داخلی (سیستم)» و «ایمیل» ارسال می‌کند.

- مسیر تنظیمات: `/settings/notifications` (فقط مدیر)
- فایل تنظیم رویدادها: `config/notification_events.php`
- جدول قوانین: `notification_rules`

## ماژول‌ها و رویدادها

به طور پیش‌فرض رویدادهای زیر پشتیبانی می‌شوند:

- سفارش‌های خرید (`purchase_orders`): رویداد `status.changed`
- پیش‌فاکتورها (`proformas`): رویداد `approval.sent`
- سرنخ‌ها (`leads`): رویداد `assigned.changed`
- یادداشت‌ها (`notes`): رویداد `note.mentioned`

می‌توانید با افزودن ماژول/رویداد جدید در `config/notification_events.php` (همراه با `label`، `default_channels` و `placeholders`) آن را به ماتریس اضافه کنید.

## قالب‌ها و کلیدواژه‌ها

- برای هر رویداد، فهرستی از کلیدواژه‌های مجاز در پیکربندی تعریف می‌شود. نمونه:
  - `{po_number}`, `{from_status}`, `{to_status}`, `{requester_name}`
  - `{proforma_number}`, `{customer_name}`, `{approver_name}`
  - `{lead_name}`, `{old_user}`, `{new_user}`
  - `{note_excerpt}`, `{mentioned_user}`, `{context}`

- کلیدواژه‌های عمومی نیز پشتیبانی می‌شوند:
  - `{{ url }}`: لینک به صفحه مربوطه
  - `{{ actor.name }}`: نام کاربری که رویداد را انجام داده است (در صورت وجود)

نمونه قالب موضوع: `تغییر وضعیت سفارش خرید {po_number}`

نمونه قالب متن:

```
وضعیت سفارش از {from_status} به {to_status} تغییر یافت.
توسط {{ actor.name }}
مشاهده: {{ url }}
```

## بذر اولیه (Seeder)

برای ایجاد قوانین پیش‌فرض:

```
php artisan db:seed --class=Database\\Seeders\\NotificationRuleSeeder
```

یا از طریق `DatabaseSeeder` فراخوانی شده است.

## نحوه مسیریابی رویدادها

کلاس `App\Services\Notifications\NotificationRouter` قوانین فعال را یافته، قالب‌ها را با داده‌های ایمن جایگزین می‌کند و از طریق کانال‌های انتخابی ارسال می‌کند.

- سفارش خرید: در تغییر وضعیت (`PurchaseOrderController@updateStatus`) با زمینه‌های زیر:
  - `purchase_order`, `prev_status`, `new_status`, `actor`, `url`
- تغییر ارجاع (سرنخ/فرصت/پیش‌فاکتور): هنگام تغییر `assigned_to`
- منشن در یادداشت: هنگام ثبت یادداشت با `@username`
- ارسال برای تأیید پیش‌فاکتور: هنگام ارسال به تأییدکنندگان

## نکات

- همه اعلان‌ها/ایمیل‌های جدید `ShouldQueue` هستند؛ برای پردازش صف:
  - `php artisan queue:work`
- متن‌ها راست‌به‌چپ (RTL) و فارسی هستند.

