<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Traits\AppliesVisibilityScope;

class Organization extends Model
{
    use HasFactory, LogsActivity, AppliesVisibilityScope;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'website',
        'industry',
        'size',
        'notes',
        'state',
        'city',
        'assigned_to',     // ← اضافه کن
    ];

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'email',
                'phone',
                'address',
                'website',
                'industry',
                'state',
                'city',
                'assigned_to',
            ])
            ->useLogName('organization')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function activities()
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }
}
