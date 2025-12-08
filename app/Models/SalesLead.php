<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use App\Models\Activity as CrmActivity;
use App\Models\RoleAssignment;
use App\Models\Contact;
use App\Models\Note;
use App\Traits\NotifiesAssignee; // باقی می‌ماند؛ اما با گارد داخلی جلوی دوبل‌شدن را می‌گیریم
use App\Models\Opportunity;
use App\Services\ActivityGuard;

use App\Models\Traits\AppliesVisibilityScope;

class SalesLead extends Model
{
    use HasFactory, LogsActivity, NotifiesAssignee, AppliesVisibilityScope;

    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_CONVERTED_TO_OPPORTUNITY = 'converted_to_opportunity';
    public const STATUS_DISCARDED = 'discarded';
    public const STATUS_JUNK = 'junk';

    public const POOL_IN_POOL = 'in_pool';
    public const POOL_ASSIGNED = 'assigned';
    public const POOL_NEEDS_REASSIGNMENT = 'needs_reassignment';
    public const POOL_RECYCLED = 'recycled';

    /**
     * نام ستون و نام رابطه‌ی ارجاع‌گیرنده برای سازگاری عمومی
     * (در صورت استفاده از Trait یا سایر ابزارهای عمومی)
     */
    protected string $assigneeColumn = 'assigned_to';
    protected string $assigneeRelation = 'assignedTo';

    protected $fillable = [
        'owner_user_id',
        'prefix',
        'full_name',
        'company',
        'email',
        'mobile',
        'phone',
        'website',
        'lead_source',
        'lead_status',
        'status',
        'disqualify_reason',
        'assigned_to',
        'assigned_at',
        'first_activity_at',
        'pool_status',
        'contact_id',
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
        'building_usage',
        'internal_temperature',
        'external_temperature',
        'building_length',
        'building_width',
        'eave_height',
        'ridge_height',
        'wall_material',
        'insulation_status',
        'spot_heating_systems',
        'central_200_systems',
        'central_300_systems',
        'notes',           // توجه: این ستون متنیِ داخل جدول sales_leads است
        'created_by',
        'team_id',
        'department',
        'visibility',
    ];

    protected $casts = [
        'lead_date'            => 'date',
        'next_follow_up_date'  => 'date',
        'do_not_email'         => 'boolean',
        'owner_user_id'        => 'integer',
        'assigned_to'          => 'integer',
        'contact_id'           => 'integer',
        'team_id'              => 'integer',
        'status'               => 'string',
        'pool_status'          => 'string',
        'disqualify_reason'    => 'string',
        'assigned_at'          => 'datetime',
        'first_activity_at'    => 'datetime',
        'internal_temperature' => 'decimal:2',
        'external_temperature' => 'decimal:2',
        'building_length'      => 'decimal:2',
        'building_width'       => 'decimal:2',
        'eave_height'          => 'decimal:2',
        'ridge_height'         => 'decimal:2',
        'spot_heating_systems' => 'integer',
        'central_200_systems'  => 'integer',
        'central_300_systems'  => 'integer',
        'visibility'           => 'string',
    ];

    protected $attributes = [
        'pool_status' => self::POOL_IN_POOL,
    ];

    /* ---------------- Hooks: ارسال اعلان روی ایجاد/تغییر ارجاع ---------------- */

    /**
     * هوک‌های مدل: بعد از ایجاد، و بعد از بروزرسانی (تغییر assigned_to) اعلان بفرست.
     * گارد داخلی برای جلوگیری از ارسال دوبل اگر Trait هم فعال شد.
     */

    protected static function booted(): void
    {
        static::creating(function (SalesLead $lead) {
            if (!empty($lead->assigned_to)) {
                $lead->assigned_at = $lead->assigned_at ?? Carbon::now();
                $lead->pool_status = $lead->pool_status ?: self::POOL_ASSIGNED;
            }
        });

        static::updating(function (SalesLead $lead) {
            if ($lead->isDirty('assigned_to')) {
                if (!empty($lead->assigned_to)) {
                    $lead->assigned_at = $lead->assigned_at ?? Carbon::now();
                    $lead->pool_status = self::POOL_ASSIGNED;
                } else {
                    $lead->assigned_at = null;
                    $lead->pool_status = self::POOL_IN_POOL;
                }
            }
        });

        static::created(function (SalesLead $lead) {
            if (!empty($lead->assigned_to)) {
                if (isset($lead->_assignment_notified) && $lead->_assignment_notified === true) {
                    return;
                }
                self::notifyAssignee($lead, 'created');
            }
        });

        static::updated(function (SalesLead $lead) {
            if ($lead->wasChanged('assigned_to') && !empty($lead->assigned_to)) {
                if (isset($lead->_assignment_notified) && $lead->_assignment_notified === true) {
                    return;
                }
                self::notifyAssignee($lead, 'updated');
            }
        });
    }

    /**
     * ارسال اعلان ارجاع/تغییر ارجاع برای کاربر مقصد
     */
    protected static function notifyAssignee(SalesLead $lead, string $event): void
    {
        $user = $lead->assignedTo; // رابطه‌ی تعریف‌شده در همین مدل
        if (!$user) {
            return;
        }

        $assignedBy = auth()->user(); // در صف ممکن است null باشد؛ مشکلی نیست
        $title = $event === 'created' ? 'ارجاع سرنخ جدید' : 'تغییر ارجاع سرنخ';

        try {
            $user->notify(new \App\Notifications\FormAssignedNotification($lead, $assignedBy, null, $title));
        } catch (\Throwable $e) {
            \Log::error('Failed to send assignment notification email', [
                'lead_id' => $lead->id ?? null,
                'user_id' => $user->id ?? null,
                'error'   => $e->getMessage(),
            ]);
        }
        // Route via NotificationRouter in parallel
        try {
            $router = app(\App\Services\Notifications\NotificationRouter::class);
            $context = [
                'model' => $lead,
                'old_assignee' => null,
                'new_assignee' => optional($lead->assignedTo)->name,
                'actor' => $assignedBy,
                'url' => route('marketing.leads.show', $lead->id),
            ];
            $router->route('leads', 'assigned.changed', $context, [$lead->assigned_to]);
        } catch (\Throwable $e) {
            \Log::warning('SalesLead notifyAssignee: NotificationRouter failed', ['error' => $e->getMessage()]);
        }

        // گارد برای جلوگیری از ارسال دوباره در همین چرخه
        $lead->_assignment_notified = true;
    }

    /* ---------------- Relations (users) ---------------- */

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * CRM activities (calls/meetings/tasks) related to this lead.
     * هنگام ثبت تماس/جلسه/پیگیری، همین رابطه را برای ایجاد Activity استفاده کنید.
     */
    public function crmActivities(): MorphMany
    {
        return $this->morphMany(CrmActivity::class, 'related');
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'lead_favorites', 'lead_id', 'user_id')
            ->withTimestamps();
    }

    public function isFavoritedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->relationLoaded('favoritedBy')) {
            return $this->favoritedBy->contains('id', $user->id);
        }

        return $this->favoritedBy()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function assignedUser()
    {
        // Alias kept for backward compatibility; uses the primary assignedTo relation.
        return $this->assignedTo();
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
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

    /* ---------------- Notes (polymorphic: noteable_type/noteable_id) ---------------- */

    /**
     * ⚠️ به‌دلیل وجود ستون فیزیکی به نام `notes` در جدول sales_leads،
     * استفاده از $lead->notes مقدار ستون را می‌دهد.
     * برای دسترسی به رابطه، از $lead->notes() (با پرانتز) یا از leadNotes()/lastNote استفاده کن.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /** لیست یادداشت‌ها با ترتیب نزولی (برای نمایش لیستی) */
    public function leadNotes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable')->latest();
    }

    /** آخرین یادداشت (برای نمایش در تب اطلاعات) */
    public function lastNote(): MorphOne
    {
        // اگر خواستی بر اساس id انتخاب شود: ->latestOfMany('id')
        return $this->morphOne(Note::class, 'noteable')->latestOfMany();
    }

    /**
     * Sets the first interaction timestamp; call this from the first call/note/activity creation flow
     * so the "first touch" SLA timer stops once the lead is actually contacted.
     */
    public function markFirstActivity(?Carbon $timestamp = null, bool $force = false): void
    {
        if ($this->first_activity_at && !$force) {
            return;
        }

        $time = $timestamp ? Carbon::parse($timestamp) : Carbon::now();

        $this->first_activity_at = $time;
        if ($this->pool_status !== self::POOL_RECYCLED) {
            $this->pool_status = self::POOL_ASSIGNED;
        }

        $this->save();
    }

    /**
     * Require at least one recent activity before allowing status/stage change.
     */
    public function canChangeStageTo(string $newStage, int $withinDays = 30): bool
    {
        $current = $this->getStatusValue();
        $target  = strtolower(trim((string) $newStage));

        if ($target === '' || $target === $current) {
            return true;
        }

        $hasRecentActivity = $this->hasRecentActivity($withinDays);

        \Log::info('lead_stage_guard_called', [
            'lead_id' => $this->id,
            'current_status' => $current,
            'target_status' => $target,
            'has_recent_activity' => $hasRecentActivity,
        ]);

        return $hasRecentActivity;
    }

    /**
     * Checks for interaction/activity records via CRM activities and notes.
     */
    public function hasRecentActivity(int $withinDays = 30): bool
    {
        $breakdown = ActivityGuard::realActivityBreakdown($this, $withinDays);

        \Log::info('lead_recent_activity_check', [
            'lead_id' => $this->id,
            'within_days' => $withinDays,
            'since' => $breakdown['since']->toDateTimeString(),
            'has_crm_activity' => $breakdown['crm'] > 0,
            'has_notes_activity' => $breakdown['notes'] > 0,
            'crm_activity_count' => $breakdown['crm'],
            'notes_activity_count' => $breakdown['notes'],
            'real_activities_count' => $breakdown['total'],
        ]);

        return $breakdown['total'] > 0;
    }

    /* ---------------- Activity Log ---------------- */

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('lead');
    }

    public function activities()
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }

    /* ---------------- Notification Title ---------------- */

    public function getNotificationTitle(): string
    {
        // اگر prefix دارید (مثل آقا/خانم)، می‌توانید در عنوان لحاظ کنید
        $person = trim(($this->prefix ? $this->prefix . ' ' : '') . ($this->full_name ?? ''));
        if ($person !== '') {
            return $person;
        }

        if (!empty($this->company)) {
            return $this->company;
        }

        // گزینه‌ی کمکی؛ اگر بعداً subject اضافه شد
        if (!empty($this->subject ?? null)) {
            return $this->subject;
        }

        return "سرنخ #{$this->id}";
    }

   

    /* ---------------- Conversion ---------------- */

    public function opportunities()
    {
        return $this->belongsTo(Opportunity::class, 'converted_opportunity_id');
    }

    public function convertedOpportunity()
    {
        return $this->belongsTo(\App\Models\Opportunity::class, 'converted_opportunity_id');
    }

    public function getIsConvertedAttribute(): bool
    {
        return !is_null($this->converted_at ?? null);
    }

    public static function statusOptions(): array
    {
        $statuses = config('lead.statuses', [
            self::STATUS_NEW                      => 'جدید',
            self::STATUS_CONTACTED                => 'تماس گرفته شده',
            self::STATUS_CONVERTED_TO_OPPORTUNITY => 'تبدیل شده به فرصت',
            self::STATUS_DISCARDED                => 'سرکاری / حذف شده',
        ]);

        $aliases = [
            self::STATUS_CONVERTED => self::STATUS_CONVERTED_TO_OPPORTUNITY,
            self::STATUS_JUNK      => self::STATUS_DISCARDED,
        ];

        foreach ($aliases as $alias => $target) {
            if (isset($statuses[$target]) && !isset($statuses[$alias])) {
                $statuses[$alias] = $statuses[$target];
            }
        }

        return $statuses;
    }


    public static function disqualifyReasons(): array
    {
        $reasons = config('lead.disqualify_reasons', []);
        if (!empty($reasons)) {
            return array_combine($reasons, $reasons);
        }

        return [
            'no_need'             => 'no_need',
            'no_budget'           => 'no_budget',
            'not_decision_maker'  => 'not_decision_maker',
            'competitor_price'    => 'competitor_price',
            'wrong_or_duplicate'  => 'wrong_or_duplicate',
            'out_of_scope'        => 'out_of_scope',
            'unrealistic_timing'  => 'unrealistic_timing',
        ];
    }

    public function getStatusValue(): ?string
    {
        $raw = $this->getRawOriginal('lead_status');
        if ($raw === null || $raw === '') {
            $raw = $this->getRawOriginal('status');
        }

        $raw = is_string($raw) ? strtolower(trim($raw)) : null;

        return self::normalizeStatus($raw);
    }

    public static function normalizeStatus(?string $status): ?string
    {
        $normalized = is_string($status) ? strtolower(trim($status)) : null;

        if ($normalized === null || $normalized === '') {
            return null;
        }

        $aliases = [
            self::STATUS_CONVERTED => self::STATUS_CONVERTED_TO_OPPORTUNITY,
            self::STATUS_JUNK      => self::STATUS_DISCARDED,
        ];

        return $aliases[$normalized] ?? $normalized;
    }

    public function isOpen(): bool
    {
        $status = $this->getStatusValue();
        return !in_array($status, [self::STATUS_DISCARDED, self::STATUS_JUNK], true);
    }

    public function isJunk(): bool
    {
        return in_array($this->getStatusValue(), [self::STATUS_DISCARDED, self::STATUS_JUNK], true);
    }

    public function isConverted(): bool
    {
        return in_array(
            $this->getStatusValue(),
            [self::STATUS_CONVERTED_TO_OPPORTUNITY, self::STATUS_CONVERTED],
            true
        );
    }

}

