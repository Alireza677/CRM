<?php

namespace App\Models;

use App\Traits\NotifiesAssignee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Traits\CausesActivity;
use App\Models\Traits\AppliesVisibilityScope;

class Opportunity extends Model
{
    use NotifiesAssignee;
    use HasFactory;
    use LogsActivity, CausesActivity;
    use AppliesVisibilityScope;

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
        'owner_user_id',
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
        'description',
        'team_id',
        'department',
        'visibility'
    ];

    protected $casts = [
        'next_follow_up' => 'date',
        'amount' => 'integer',
        'success_rate' => 'integer',
        'owner_user_id' => 'integer',
        'assigned_to'   => 'integer',
        'team_id'       => 'integer',
        'visibility'    => 'string'
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
        ->orderByDesc('proforma_date')
        ->orderByDesc('created_at'); 
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

protected static function booted()
{
    static::saving(function (Opportunity $op) {
        $wonValues = ['won', 'برنده'];
        if (in_array($op->stage, $wonValues, true)) {
            $op->next_follow_up = null;
        }
    });
}

    // ---------------- Accessors (display labels) ----------------
    public function getStageAttribute($value)
    {
        return \App\Helpers\FormOptionsHelper::getOpportunityStageLabel($value);
    }

    public function getSourceAttribute($value)
    {
        return \App\Helpers\FormOptionsHelper::getOpportunitySourceLabel($value);
    }






}
