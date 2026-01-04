<?php

namespace App\Models;

use App\Traits\NotifiesAssignee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Traits\CausesActivity;
use App\Models\Traits\AppliesVisibilityScope;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\RoleAssignment;
use App\Models\Activity as CrmActivity;
use App\Services\ActivityGuard;
use Illuminate\Support\Facades\DB;

class Opportunity extends Model
{
    use NotifiesAssignee;
    use HasFactory;
    use LogsActivity, CausesActivity;
    use AppliesVisibilityScope;

    public const STAGE_OPEN = 'open';
    public const STAGE_PROPOSAL_SENT = 'proposal_sent';
    public const STAGE_NEGOTIATION = 'negotiation';
    public const STAGE_WON = 'won';
    public const STAGE_LOST = 'lost';
    public const STAGE_DEAD = 'dead';

    protected static $logAttributes = ['name',
        'organization_id',
        'contact_id',
        'stage',
        'lost_reason',
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
        'opportunity_number',
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
        'visibility'    => 'string',
        'stage'         => 'string',
        'lost_reason'   => 'string',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class, 'opportunity_id');
    }

    public function assignedUser()
    {
    return $this->belongsTo(User::class, 'assigned_to');
    }



    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function roleAssignments(): MorphMany
    {
        return $this->morphMany(RoleAssignment::class, 'assignable');
    }

    public function getRoleUser(string $roleType, ?string $level = null): ?User
    {
        $query = $this->roleAssignments()
            ->where('role_type', $roleType)
            ->with('user');

        if ($level !== null) {
            $query->where('level', $level);
        } else {
            $levelOrder = config('commission.level_order', ['A', 'B', 'C']);

            if (!empty($levelOrder)) {
                $cases = [];
                $bindings = [];
                foreach ($levelOrder as $index => $lvl) {
                    $cases[] = "WHEN ? THEN {$index}";
                    $bindings[] = $lvl;
                }
                $orderCase = 'CASE level ' . implode(' ', $cases) . ' ELSE ' . count($levelOrder) . ' END';
                $query->orderByRaw($orderCase, $bindings);
            }
        }

        $assignment = $query->first();

        return $assignment?->user;
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }
    public function setStageAttribute($value): void
    {
        if (is_string($value)) {
            $value = preg_replace('/\s+/u', ' ', trim($value));
        }
        $this->attributes['stage'] = $value;
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

    /**
     * CRM activities (calls/meetings/tasks) related to this opportunity.
     * هنگام ثبت تماس/جلسه/پیشنهاد، همین رابطه را برای ایجاد Activity استفاده کنید.
     */
    public function crmActivities(): MorphMany
    {
        return $this->morphMany(CrmActivity::class, 'related');
    }

    public function lastNote()
    {
        // نیازمند timestamps در جدول notes
        return $this->morphOne(Note::class, 'noteable')->latestOfMany();
    }

    /**
     * Require at least one recent activity before allowing stage change.
     */
    public function canChangeStageTo(?string $newStage, int $withinDays = 30): bool
    {
        $current = $this->getStageValue();
        if (!$newStage || strtolower((string) $newStage) === $current) {
            return true;
        }

        return $this->hasRecentActivity($withinDays);
    }

    /**
     * Checks for interaction/activity records via shared ActivityGuard (manual CRM activities/notes).
     */
    public function hasRecentActivity(int $withinDays = 30): bool
    {
        return ActivityGuard::hasRealActivities($this, $withinDays);
    }

    public function activities()
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }

    public static function stageOptions(): array
    {
        return config('opportunity.stages', [
            self::STAGE_OPEN          => 'open',
            self::STAGE_PROPOSAL_SENT => 'proposal_sent',
            self::STAGE_NEGOTIATION   => 'negotiation',
            self::STAGE_WON           => 'won',
            self::STAGE_LOST          => 'lost',
            self::STAGE_DEAD          => 'dead',
        ]);
    }

    public static function lostReasons(): array
    {
        $reasons = config('opportunity.lost_reasons', []);
        if (!empty($reasons)) {
            return array_combine($reasons, $reasons);
        }

        return [
            'price'                  => 'price',
            'decision_delay'         => 'decision_delay',
            'competitor_capability'  => 'competitor_capability',
            'no_budget'              => 'no_budget',
            'requirement_changed'    => 'requirement_changed',
            'internal_choice'        => 'internal_choice',
            'no_trust_in_brand'      => 'no_trust_in_brand',
        ];
    }

    public static function closedStages(): array
    {
        $configured = config('opportunity.closed_stages', []);
        if (!empty($configured)) {
            return $configured;
        }

        return [self::STAGE_WON, self::STAGE_LOST, self::STAGE_DEAD];
    }

    public function getStageValue(): ?string
    {
        $raw = $this->getRawOriginal('stage');
        $raw = is_string($raw) ? strtolower(trim($raw)) : null;

        return $raw ?: null;
    }

    public function isOpen(): bool
    {
        $stage = $this->getStageValue();
        return in_array($stage, [
            self::STAGE_OPEN,
            self::STAGE_PROPOSAL_SENT,
            self::STAGE_NEGOTIATION,
        ], true);
    }

    public function isWon(): bool
    {
        return $this->getStageValue() === self::STAGE_WON;
    }

    public function isLost(): bool
    {
        return in_array($this->getStageValue(), [self::STAGE_LOST, self::STAGE_DEAD], true);
    }

// فعالیت‌ها (مثلاً Task یا Activity)
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
    static::creating(function (Opportunity $op) {
        if (empty($op->opportunity_number)) {
            $op->opportunity_number = self::generateOpportunityNumber();
        }
    });

    static::saving(function (Opportunity $op) {
        $stage = $op->getStageValue();
        if (in_array($stage, self::closedStages(), true)) {
            $op->next_follow_up = null;
        }
    });
}

protected static function generateOpportunityNumber(): string
{
    return DB::transaction(function () {
        $max = DB::table('opportunities')
            ->lockForUpdate()
            ->selectRaw("MAX(CAST(SUBSTRING(opportunity_number, 3) AS UNSIGNED)) as max_seq")
            ->value('max_seq');

        $next = ((int) $max) + 1;

        return 'OP' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
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
