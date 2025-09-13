<?php

namespace App\Models;

use App\Traits\NotifiesAssignee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Traits\CausesActivity;

class Opportunity extends Model
{
    use NotifiesAssignee;
    use HasFactory;
    use LogsActivity, CausesActivity;

    protected static $logAttributes = ['name',
        'organization_id',
        'contact_id',
        'stage',
        'type',
        'source',
        'building_usage',
        'assigned_to',
        'success_rate',
        'amount',
        'next_follow_up',
        'description'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'opportunity';

    protected $fillable = [
        'name',
        'organization_id',
        'contact_id',
        'stage',
        'type',
        'source',
        'building_usage',
        'assigned_to',
        'success_rate',
        'amount',
        'next_follow_up',
        'description'
    ];

    protected $casts = [
        'next_follow_up' => 'date',
        'amount' => 'integer',
        'success_rate' => 'integer'
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function assignedUser()
    {
    return $this->belongsTo(User::class, 'assigned_to');
    }



    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }
    public function getModelLabel()
    {
        return 'فرصت';
    }
    public function getNotificationTitle()
    {
        return $this->name ?? 'بدون عنوان';
    }
    public function notes()
    {
        return $this->morphMany(\App\Models\Note::class, 'noteable')->latest();
    }

    public function lastNote()
    {
        // نیازمند timestamps در جدول notes
        return $this->morphOne(Note::class, 'noteable')->latestOfMany();
    }

// فعالیت‌ها (مثلاً Task یا Activity)
public function activities()
{
    return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
}
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
    ->logOnly([
        'name',
        'organization_id',
        'contact_id',
        'stage',
        'type',
        'source',
        'assigned_to',
        'success_rate',
        'amount',
        'next_follow_up',
        'description'
    ])
     // فقط این فیلدها را لاگ بگیر
        ->useLogName('opportunity') // نام دسته‌بندی لاگ‌ها
        ->logOnlyDirty() // فقط تغییرات را ذخیره کن
        ->dontSubmitEmptyLogs(); // اگر چیزی تغییر نکرد، لاگ ذخیره نشه
}

// تماس‌های تلفنی
public function calls()
{
    return $this->hasMany(Call::class);
}

// تأییدیه‌ها
public function approvals()
{
    return $this->morphMany(Approval::class, 'approvable');
}


// پیش‌فاکتورها
public function proformas()
{
    return $this->hasMany(\App\Models\Proforma::class)
        ->orderByDesc('proforma_date'); 
}


// سفارش‌ها
public function orders()
{
    return $this->hasMany(Order::class);
}

// اسناد
public function documents()
{
    return $this->hasMany(Document::class);
}






}


