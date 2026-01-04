<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Traits\AppliesVisibilityScope;
use Illuminate\Support\Facades\DB;

class Organization extends Model
{
    use HasFactory, LogsActivity, AppliesVisibilityScope;

    protected $fillable = [
        'organization_number',
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
        'assigned_to',
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

        static::creating(function (Organization $organization) {
            if (empty($organization->organization_number)) {
                $organization->organization_number = self::generateOrganizationNumber();
            }
        });
    }

    protected static function generateOrganizationNumber(): string
    {
        return DB::transaction(function () {
            $max = DB::table('organizations')
                ->lockForUpdate()
                ->selectRaw("MAX(CAST(SUBSTRING(organization_number, 3) AS UNSIGNED)) as max_seq")
                ->value('max_seq');

            $next = ((int) $max) + 1;

            return 'OR' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
        });
    }

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

    public function mergedInto()
    {
        return $this->belongsTo(self::class, 'merged_into_id');
    }

    public function scopeNotMerged(Builder $query): Builder
    {
        return $query->whereNull($this->getTable() . '.merged_into_id');
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

