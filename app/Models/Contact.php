<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
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
        'position',
        'email',
        'phone',
        'mobile',
        'website',
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
        'merged_into_id',
        'merged_at',
    ];

    protected $casts = [
        'merged_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('notMerged', function (Builder $builder) {
            $table = $builder->getModel()->getTable();
            $builder->whereNull($table . '.merged_into_id');
        });
    }

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

    public function leads()
    {
        return $this->belongsToMany(\App\Models\SalesLead::class, 'lead_contacts')
            ->withTimestamps();
    }

    public function assignedUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function mergedInto()
    {
        return $this->belongsTo(self::class, 'merged_into_id');
    }

    public function scopeNotMerged(Builder $query): Builder
    {
        return $query->whereNull($this->getTable() . '.merged_into_id');
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
                'position',
                'email',
                'phone',
                'mobile',
                'company',
                'website',
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
