<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

class Activity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subject','start_at','due_at',
        'assigned_to_id','related_type','related_id',
        'status','priority','description','is_private',
        'created_by_id','updated_by_id',
    ];

    // اگر تایم‌زون مشخص داری همین‌جا ست کن
    protected $casts = [
        'start_at'   => 'datetime', // یا 'datetime:Asia/Tehran'
        'due_at'     => 'datetime',
        'is_private' => 'boolean',
    ];

    protected $appends = ['start_at_jalali','due_at_jalali'];

    // ---------- Accessors (خروجی شمسی) ----------
    public function getStartAtJalaliAttribute(): ?string
    {
        return $this->start_at ? Jalalian::fromCarbon($this->start_at)->format('Y/m/d H:i') : null;
    }

    public function getDueAtJalaliAttribute(): ?string
    {
        return $this->due_at ? Jalalian::fromCarbon($this->due_at)->format('Y/m/d H:i') : null;
    }

    // ---------- Mutators (ورودی شمسی) ----------
    public function setStartAtJalaliAttribute($value): void
    {
        $this->attributes['start_at'] = $this->parseJalaliToCarbon($value)?->format('Y-m-d H:i:s');
    }

    public function setDueAtJalaliAttribute($value): void
    {
        $this->attributes['due_at'] = $this->parseJalaliToCarbon($value)?->format('Y-m-d H:i:s');
    }

    // ورودی‌های میلادی مستقیم (اگر از کنترلر ست شوند) → ارقام را لاتین کن
    public function setStartAtAttribute($value): void
    {
        $this->attributes['start_at'] = is_string($value) ? $this->toEnDigits($value) : $value;
    }
    public function setDueAtAttribute($value): void
    {
        $this->attributes['due_at'] = is_string($value) ? $this->toEnDigits($value) : $value;
    }

    // ---------- Helpers ----------
    /** ارقام فارسی/عربی → لاتین */
    protected function toEnDigits(?string $s): ?string
    {
        if ($s === null) return null;
        $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $ar = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $en = ['0','1','2','3','4','5','6','7','8','9'];
        return str_replace($ar, $en, str_replace($fa, $en, $s));
    }

    /** رشتهٔ شمسی را (با یا بدون ساعت) به Carbon میلادی تبدیل می‌کند */
    protected function parseJalaliToCarbon($value): ?Carbon
    {
        if (blank($value)) return null;

        $v = $this->toEnDigits(trim((string)$value));
        // اسلش و خط‌تیره هر دو را قبول کن
        $v = str_replace('-', '/', $v);

        $formats = [
            'Y/m/d H:i:s',
            'Y/m/d H:i',
            'Y/m/d',        // فقط تاریخ
        ];

        foreach ($formats as $fmt) {
            try {
                return Jalalian::fromFormat($fmt, $v)->toCarbon();
            } catch (\Throwable $e) {
                // try next
            }
        }

        // عمداً دیگر به Carbon::parse تکیه نمی‌کنیم تا خطای ارقام فارسی تکرار نشود
        throw new \InvalidArgumentException("Invalid Jalali datetime format: {$value}");
    }

    // روابط و اسکوپ‌ها و خروجی تقویم همان قبلی...
    public function assignedTo() { return $this->belongsTo(User::class, 'assigned_to_id'); }
    public function creator()    { return $this->belongsTo(User::class, 'created_by_id'); }
    public function updater()    { return $this->belongsTo(User::class, 'updated_by_id'); }
    public function related()    { return $this->morphTo(); }
    public function reminders()  { return $this->hasMany(ActivityReminder::class); }

    public function scopeVisibleTo($q, User $u) {
        return $q->where(function($qq) use ($u) {
            $qq->where('is_private', false)
               ->orWhere('created_by_id', $u->id)
               ->orWhere('assigned_to_id', $u->id);
        });
    }

    public function toCalendarEvent(): array {
        return [
            'id'    => $this->id,
            'title' => $this->subject,
            'start' => optional($this->start_at)->toIso8601String(),
            'end'   => optional($this->due_at)->toIso8601String(),
            'url'   => route('activities.show', $this->id),
            'extendedProps' => [
                'status'   => $this->status,
                'priority' => $this->priority,
                'private'  => $this->is_private,
                'assigned_to' => $this->assigned_to_id,
            ],
        ];
    }
}
