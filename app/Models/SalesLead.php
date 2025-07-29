<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SalesLead extends Model
{
    use HasFactory;

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
        'notes',
        'created_by',
    ];

    protected $casts = [
        'lead_date' => 'date',
        'next_follow_up_date' => 'date',
        'do_not_email' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    //
    public function notes()
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // یا مشخص کردن فیلدهای خاص
            ->useLogName('lead');
    }

    public function activities()
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }
} 