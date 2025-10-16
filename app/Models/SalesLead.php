<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Traits\NotifiesAssignee; // باقی می‌ماند؛ اما با گارد داخلی جلوی دوبل‌شدن را می‌گیریم

class SalesLead extends Model
{
    use HasFactory, LogsActivity, NotifiesAssignee;

    /**
     * نام ستون و نام رابطه‌ی ارجاع‌گیرنده برای سازگاری عمومی
     * (در صورت استفاده از Trait یا سایر ابزارهای عمومی)
     */
    protected string $assigneeColumn = 'assigned_to';
    protected string $assigneeRelation = 'assignedTo';

    protected $fillable = [
        'prefix',
        'full_name',
        'company',
        'email',
        'mobile',
        'phone',
        'website',
        'lead_source',
        'lead_status',
        'assigned_to',
        'lead_date',
        'next_follow_up_date',
        'do_not_email',
        'customer_type',
        'industry',
        'nationality',
        'main_test_field',
        'dependent_test_field',
        'address',
        'state',
        'city',
        'notes',           // توجه: این ستون متنیِ داخل جدول sales_leads است
        'created_by',
    ];

    protected $casts = [
        'lead_date'            => 'date',
        'next_follow_up_date'  => 'date',
        'do_not_email'         => 'boolean',
    ];

    /* ---------------- Hooks: ارسال اعلان روی ایجاد/تغییر ارجاع ---------------- */

    /**
     * هوک‌های مدل: بعد از ایجاد، و بعد از بروزرسانی (تغییر assigned_to) اعلان بفرست.
     * گارد داخلی برای جلوگیری از ارسال دوبل اگر Trait هم فعال شد.
     */
    protected static function booted(): void
    {
        static::created(function (SalesLead $lead) {
            if (!empty($lead->assigned_to)) {
                // اگر قبلاً در همین چرخه ارسال شده، دوباره نفرست
                if (isset($lead->_assignment_notified) && $lead->_assignment_notified === true) {
                    return;
                }
                self::notifyAssignee($lead, 'created');
            }
        });

        static::updated(function (SalesLead $lead) {
            if ($lead->wasChanged('assigned_to') && !empty($lead->assigned_to)) {
                if (isset($lead->_assignment_notified) && $lead->_assignment_notified === true) {
                    return;
                }
                self::notifyAssignee($lead, 'updated');
            }
        });
    }

    /**
     * ارسال اعلان ارجاع/تغییر ارجاع برای کاربر مقصد
     */
    protected static function notifyAssignee(SalesLead $lead, string $event): void
    {
        $user = $lead->assignedTo; // رابطه‌ی تعریف‌شده در همین مدل
        if (!$user) {
            return;
        }

        $assignedBy = auth()->user(); // در صف ممکن است null باشد؛ مشکلی نیست
        $title = $event === 'created' ? 'ارجاع سرنخ جدید' : 'تغییر ارجاع سرنخ';

        $user->notify(new \App\Notifications\FormAssignedNotification($lead, $assignedBy, null, $title));

        // گارد برای جلوگیری از ارسال دوباره در همین چرخه
        $lead->_assignment_notified = true;
    }

    /* ---------------- Relations (users) ---------------- */

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /* ---------------- Notes (polymorphic: noteable_type/noteable_id) ---------------- */

    /**
     * ⚠️ به‌دلیل وجود ستون فیزیکی به نام `notes` در جدول sales_leads،
     * استفاده از $lead->notes مقدار ستون را می‌دهد.
     * برای دسترسی به رابطه، از $lead->notes() (با پرانتز) یا از leadNotes()/lastNote استفاده کن.
     */
    public function notes()
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /** لیست یادداشت‌ها با ترتیب نزولی (برای نمایش لیستی) */
    public function leadNotes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable')->latest();
    }

    /** آخرین یادداشت (برای نمایش در تب اطلاعات) */
    public function lastNote(): MorphOne
    {
        // اگر خواستی بر اساس id انتخاب شود: ->latestOfMany('id')
        return $this->morphOne(Note::class, 'noteable')->latestOfMany();
    }

    /* ---------------- Activity Log ---------------- */

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('lead');
    }

    public function activities()
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }

    /* ---------------- Notification Title ---------------- */

    public function getNotificationTitle(): string
    {
        // اگر prefix دارید (مثل آقا/خانم)، می‌توانید در عنوان لحاظ کنید
        $person = trim(($this->prefix ? $this->prefix . ' ' : '') . ($this->full_name ?? ''));
        if ($person !== '') {
            return $person;
        }

        if (!empty($this->company)) {
            return $this->company;
        }

        // گزینه‌ی کمکی؛ اگر بعداً subject اضافه شد
        if (!empty($this->subject ?? null)) {
            return $this->subject;
        }

        return "سرنخ #{$this->id}";
    }

   

    /* ---------------- Conversion ---------------- */

    public function convertedOpportunity()
    {
        return $this->belongsTo(\App\Models\Opportunity::class, 'converted_opportunity_id');
    }

    public function getIsConvertedAttribute(): bool
    {
        return !is_null($this->converted_at ?? null);
    }

}
