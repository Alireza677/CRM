<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

class Holiday extends Model
{
    protected $fillable = [
        'date', 'title', 'notify', 'notify_message', 'created_by_id', 'notify_sent_at',
    ];

    protected $casts = [
        'date' => 'date',
        'notify' => 'boolean',
        'notify_sent_at' => 'datetime',
    ];

    // Accept Jalali date via date_jalali virtual attribute
    public function setDateJalaliAttribute($value): void
    {
        $c = $this->parseJalaliToCarbonDate($value);
        $this->attributes['date'] = $c ? $c->toDateString() : null;
    }

    public function setDateAttribute($value): void
    {
        if (is_string($value)) {
            $v = $this->toEnDigits(trim($value));
            // Try direct Y-m-d first
            try {
                $c = Carbon::createFromFormat('Y-m-d', $v)->startOfDay();
                $this->attributes['date'] = $c->toDateString();
                return;
            } catch (\Throwable $e) { /* continue */ }

            // Try parse Jalali like 1402/01/15
            $c = $this->parseJalaliToCarbonDate($v);
            if ($c) {
                $this->attributes['date'] = $c->toDateString();
                return;
            }
        }
        $this->attributes['date'] = $value;
    }

    protected function parseJalaliToCarbonDate(?string $value): ?Carbon
    {
        if ($value === null || $value === '') return null;
        $v = $this->toEnDigits($value);
        $v = str_replace('-', '/', $v);
        $formats = ['Y/m/d'];
        foreach ($formats as $fmt) {
            try {
                return Jalalian::fromFormat($fmt, $v)->toCarbon()->startOfDay();
            } catch (\Throwable $e) { /* try next */ }
        }
        return null;
    }

    protected function toEnDigits(?string $s): ?string
    {
        if ($s === null) return null;
        $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $ar = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $en = ['0','1','2','3','4','5','6','7','8','9'];
        return str_replace($ar, $en, str_replace($fa, $en, $s));
    }

    public function toCalendarEvent(): array
    {
        return [
            'id'    => 'h-'.$this->id,
            'title' => $this->title ?: 'تعطیلی شرکت',
            'start' => optional($this->date)->toDateString(),
            'allDay'=> true,
            'color' => '#ef4444', // red-500
            'extendedProps' => [
                'kind' => 'holiday',
            ],
        ];
    }
}
