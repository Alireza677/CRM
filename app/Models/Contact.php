<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\Traits\AppliesVisibilityScope;

class Contact extends Model
{
    use HasFactory, LogsActivity, AppliesVisibilityScope;

    protected $fillable = [
        'owner_user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'mobile',
        'company',
        'state',
        'city',
        'address',
        'organization_id',
        'opportunity_id',
        'assigned_to',
        'team_id',
        'department',
        'visibility',
    ];

    // Relations
    public function opportunities()
    {
        return $this->hasMany(\App\Models\Opportunity::class);
    }

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }

    public function proformas()
    {
        return $this->hasMany(\App\Models\Proforma::class)
            ->orderByDesc('proforma_date')
            ->orderByDesc('created_at');
    }

    public function assignedUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    // Accessors
    public function getNameAttribute()
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'first_name',
                'last_name',
                'email',
                'phone',
                'mobile',
                'company',
                'state',
                'city',
                'address',
                'organization_id',
                'assigned_to',
            ])
            ->useLogName('contact')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function activities()
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }
}
