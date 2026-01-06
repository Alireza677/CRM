<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Traits\NotifiesAssignee;
use App\Models\Traits\AppliesVisibilityScope;
use App\Models\User;
use App\Models\AutomationRule;

class Proforma extends Model
{
    use HasFactory;
    use NotifiesAssignee;
    use AppliesVisibilityScope;

    protected $guarded = ['proforma_number'];

    protected $fillable = [
        'owner_user_id',
        'subject',
        'proforma_date',
        'contact_name',

        'proforma_stage',
        'approval_stage',
        'approval_mode',

        'organization_name',
        'assigned_to',
        'organization_id',
        'contact_id',
        'opportunity_id',

        'items_subtotal',
        'global_discount_type',
        'global_discount_value',
        'global_discount_amount',
        'global_tax_type',
        'global_tax_value',
        'global_tax_amount',
        'total_amount',

        'stage_id',
        'automation_rule_id',

        'first_approved_by',
        'first_approved_at',
        'approved_by',

        'team_id',
        'department',
        'visibility',
        'is_favorite',
    ];

    protected $casts = [
        'proforma_date'      => 'datetime',
        'first_approved_at'  => 'datetime',
        'items_subtotal'     => 'integer',
        'total_amount'       => 'integer',
        'is_favorite'        => 'boolean',
        'assigned_to'        => 'integer',
        'owner_user_id'      => 'integer',
        'team_id'            => 'integer',
        'visibility'         => 'string',
        'approval_stage'     => 'string',
        'approval_mode'      => 'string',
        'proforma_stage'     => 'string',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function items()
    {
        return $this->hasMany(ProformaItem::class);
    }

    public function notes()
    {
        return $this->morphMany(Note::class, 'noteable')->latest();
    }

    public function lastNote()
    {
        return $this->morphOne(Note::class, 'noteable')->latestOfMany();
    }

    public function activities()
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }

    public function approvals()
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function automationRule()
    {
        return $this->belongsTo(AutomationRule::class);
    }

    public function firstApprovedBy()
    {
        return $this->belongsTo(User::class, 'first_approved_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * ✅ REAL relationship (because stage_id exists)
     */
    public function stage()
    {
        return $this->belongsTo(\App\Models\Stage::class, 'stage_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Business Logic
    |--------------------------------------------------------------------------
    */

    /**
     * 🔑 Logical stage key (NOT relationship)
     */
    public function stageKey(): string
    {
        return strtolower((string) ($this->approval_stage ?? $this->proforma_stage ?? ''));
    }

    public function hasStartedApprovalFlow(): bool
    {
        $stages = [
            'send_for_approval',
            'awaiting_second_approval',
            'approved',
            'rejected',
        ];

        if (in_array($this->stageKey(), $stages, true)) {
            return true;
        }

        return $this->approvals()->exists();
    }

    protected function lockedStages(): array
    {
        return ['finalized', 'converted', 'invoiced', 'issued_invoice'];
    }

    public function canEdit(): bool
    {
        return ! in_array($this->stageKey(), $this->lockedStages(), true);
    }

    public function isLockedForEditing(): bool
    {
        return ! $this->canEdit();
    }

    /*
    |--------------------------------------------------------------------------
    | Notification helpers
    |--------------------------------------------------------------------------
    */

    public function getModelLabel(): string
    {
        return 'پیش‌فاکتور';
    }

    public function getNotificationTitle(): string
    {
        return $this->subject ?: 'بدون عنوان';
    }

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    */

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($proforma) {
            if (empty($proforma->proforma_number)) {
                $next = (int) self::max('id') + 1;
                $proforma->proforma_number = 'QU' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Mutators
    |--------------------------------------------------------------------------
    */

    public function setTotalAmountAttribute($value)
    {
        if ($value === null || $value === '') {
            $this->attributes['total_amount'] = null;
            return;
        }

        $value = strtr((string) $value, [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4',
            '۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4',
            '٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ]);

        $value = preg_replace('/[^\d\.\-]/', '', str_replace(',', '', trim($value)));

        if ($value !== '' && strpos($value, '.') === false) {
            $value .= '.00';
        }

        $this->attributes['total_amount'] = $value;
    }

    public function getCommissionBaseAmountAttribute(): ?float
    {
        $value = $this->getRawOriginal('total_amount');

        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = strtr((string) $value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $value = str_replace(['تومان', 'ریال', ',', ' '], '', $value);
        $value = preg_replace('/[^\d\.\-]/', '', trim($value));

        if ($value === '' || $value === '-' || $value === '.') {
            return null;
        }

        return (float) $value;
    }
}
