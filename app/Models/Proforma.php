<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\NotifiesAssignee;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\AutomationRule;
use App\Models\Traits\AppliesVisibilityScope;

class Proforma extends Model
{
    use HasFactory;
    use NotifiesAssignee;
    use AppliesVisibilityScope;

    protected $fillable = [
        'owner_user_id',
        'subject',
        'proforma_date',
        'contact_name',
        'proforma_stage',       // Ø§Ú¯Ø± Ø§Ø² Ø§ÛŒÙ† ÙÛŒÙ„Ø¯ Ø¨Ø±Ø§ÛŒ ÙˆØ¶Ø¹ÛŒØª Ø§Ø³ØªÙØ§Ø¯Ù‡ Ù…ÛŒâ€ŒÚ©Ù†ÛŒ
        'approval_stage',       // ÙˆØ¶Ø¹ÛŒØª ØªØ£ÛŒÛŒØ¯: draft | sent_for_approval | approved | rejected
        'approval_mode',        // standard | override
        'organization_name',
        'sales_opportunity',
        'assigned_to',
        'city',
        'state',
        'customer_address',
        'address_type',
        'items_subtotal',       // Ø¬Ù…Ø¹ Ø¬Ø²Ø¡ Ø¢ÛŒØªÙ…â€ŒÙ‡Ø§
        'global_discount_type', // percentage | fixed
        'global_discount_value',
        'global_discount_amount',
        'global_tax_type',      // percentage | fixed
        'global_tax_value',
        'global_tax_amount',
        'total_amount',         // Ø¬Ù…Ø¹ Ú©Ù„ Ù†Ù‡Ø§ÛŒÛŒ
        'organization_id',
        'contact_id',
        'opportunity_id',
        'is_favorite',
        'stage_id',             // Ø§Ú¯Ø± Ø³ÛŒØ³ØªÙ… stage Ø¬Ø¯Ø§Ú¯Ø§Ù†Ù‡ Ø¯Ø§Ø±ÛŒ
        'automation_rule_id',
        'first_approved_by',
        'first_approved_at',
        'approved_by',
        'product_name',
        'quantity',
        'price',
        'unit',
        'total',
        'team_id',
        'department',
        'visibility',
    ];
    
    protected $guarded = ['proforma_number'];
    
    protected $casts = [
        'proforma_date'        => 'datetime',
        'first_approved_at'    => 'datetime',
        'total_amount'         => 'integer',
        'items_subtotal'       => 'integer',
        'global_discount_value'=> 'integer',
        'global_discount_amount'=> 'integer',
        'global_tax_value'     => 'integer',
        'global_tax_amount'    => 'integer',
        'is_favorite'          => 'boolean',
        'approval_stage'       => 'string',
        'approval_mode'        => 'string',
        'proforma_stage'       => 'string',
        'status'               => 'string',
        'owner_user_id'        => 'integer',
        'assigned_to'          => 'integer',
        'team_id'              => 'integer',
        'visibility'           => 'string',
    ];
    

    /*
    |--------------------------------------------------------------------------
    | Ø±ÙˆØ§Ø¨Ø· Ø§ØµÙ„ÛŒ
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
        return $this->belongsTo(\App\Models\Opportunity::class);
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
        return $this->morphMany(\App\Models\Note::class, 'noteable')->latest();
    }

    public function lastNote()
    {
        return $this->morphOne(\App\Models\Note::class, 'noteable')->latestOfMany();
    }

    public function activities()
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }

    /**
     * Ù‚Ø§Ù†ÙˆÙ† Ø§ØªÙˆÙ…Ø§Ø³ÛŒÙˆÙ† Ù…Ø±ØªØ¨Ø· Ø¨Ø§ Ø§ÛŒÙ† Ù¾ÛŒØ´â€ŒÙØ§Ú©ØªÙˆØ±
     * (Ú©Ù„ÛŒØ¯ Ù¾ÛŒØ´â€ŒÙØ±Ø¶: automation_rule_id)
     */
    public function automationRule()
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }
    public function firstApprovedBy()
    {
        return $this->belongsTo(User::class, 'first_approved_by');
    }
    /**
     * Ú©Ø§Ø±Ø¨Ø±ÛŒ Ú©Ù‡ ØªØ£ÛŒÛŒØ¯ Ù†Ù‡Ø§ÛŒÛŒ Ø±Ø§ Ø§Ù†Ø¬Ø§Ù… Ø¯Ø§Ø¯Ù‡
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Ø§Ú¯Ø± Ø§Ø² Ø³ÛŒØ³ØªÙ… Ù‚Ø¨Ù„ÛŒÙ approvals (polymorphic) Ø§Ø³ØªÙØ§Ø¯Ù‡ Ù…ÛŒâ€ŒÚ©Ù†ÛŒØŒ Ø¨Ù…Ø§Ù†Ø¯
     * Ø¯Ø± ØºÛŒØ± Ø§ÛŒÙ† ØµÙˆØ±Øª Ù…ÛŒâ€ŒØªÙˆÙ†ÛŒ Ø­Ø°ÙØ´ Ú©Ù†ÛŒ.
     */
    public function approvals()
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    /**
     * Ø±Ú©ÙˆØ±Ø¯ Ù…Ø±Ø­Ù„Ù‡â€ŒØ§ÛŒ Ú©Ù‡ Ù‡Ù†ÙˆØ² pending Ø§Ø³Øª (Ù†ÙØ± Ø¨Ø¹Ø¯ÛŒ Ú©Ù‡ Ø¨Ø§ÛŒØ¯ ØªØ§ÛŒÛŒØ¯ Ú©Ù†Ø¯)
     */
    public function pendingApproval()
    {
        return $this->approvals()
            ->with('approver')
            ->where('status', 'pending')
            ->orderBy('step') // Ø§ÙˆÙ„ÙˆÛŒØª Ù…Ø±Ø­Ù„Ù‡
            ->orderBy('id')   // Ø¯Ø± ØµÙˆØ±Øª Ø¨Ø±Ø§Ø¨Ø± Ø¨ÙˆØ¯Ù† step
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Ø¨Ø±Ú†Ø³Ø¨ Ùˆ Ø¹Ù†ÙˆØ§Ù† Ù†ÙˆØªÛŒÙÛŒÚ©ÛŒØ´Ù†â€ŒÙ‡Ø§ (Ø¨Ø±Ø§ÛŒ Trait)
    |--------------------------------------------------------------------------
    */

    public function getModelLabel()
    {
        return 'Ù¾ÛŒØ´â€ŒÙØ§Ú©ØªÙˆØ±';
    }

    public function getNotificationTitle()
    {
        return $this->subject ?? 'Ø¨Ø¯ÙˆÙ† Ø¹Ù†ÙˆØ§Ù†';
    }

    /*
    |--------------------------------------------------------------------------
    | Ø±ÙˆÛŒØ¯Ø§Ø¯Ù‡Ø§ÛŒ Ù…Ø¯Ù„
    |--------------------------------------------------------------------------
    */

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($proforma) {
            // Ø§ÛŒØ¬Ø§Ø¯ Ø´Ù…Ø§Ø±Ù‡ Ù¾ÛŒØ´â€ŒÙØ§Ú©ØªÙˆØ± Ø§Ú¯Ø± Ù‚Ø¨Ù„Ø§Ù‹ ØªÙ†Ø¸ÛŒÙ… Ù†Ø´Ø¯Ù‡
            if (empty($proforma->proforma_number)) {
                $latestId = self::max('id') + 1;
                $proforma->proforma_number = 'QU' . str_pad($latestId, 5, '0', STR_PAD_LEFT);
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
        $orig = $value;

        if ($value === null || $value === '') {
            Log::debug('setTotalAmount: empty input', ['input' => $value]);
            $this->attributes['total_amount'] = null;
            return;
        }

        // Ø§Ø±Ù‚Ø§Ù… ÙØ§Ø±Ø³ÛŒ/Ø¹Ø±Ø¨ÛŒ â†’ Ø§Ù†Ú¯Ù„ÛŒØ³ÛŒ
        $value = strtr((string) $value, [
            'Û°' => '0','Û±' => '1','Û²' => '2','Û³' => '3','Û´' => '4',
            'Ûµ' => '5','Û¶' => '6','Û·' => '7','Û¸' => '8','Û¹' => '9',
            'Ù ' => '0','Ù¡' => '1','Ù¢' => '2','Ù£' => '3','Ù¤' => '4',
            'Ù¥' => '5','Ù¦' => '6','Ù§' => '7','Ù¨' => '8','Ù©' => '9',
        ]);

        // Ø­Ø°Ù Ø¬Ø¯Ø§Ú©Ù†Ù†Ø¯Ù‡ Ù‡Ø²Ø§Ø±Ú¯Ø§Ù† Ùˆ Ú©Ø§Ø±Ø§Ú©ØªØ±Ù‡Ø§ÛŒ ØºÛŒØ±Ø¹Ø¯Ø¯ÛŒ (Ø¨Ù‡â€ŒØ¬Ø² Ù†Ù‚Ø·Ù‡ Ùˆ Ù…Ù†ÙÛŒ)
        $value = preg_replace('/[^\d\.\-]/', '', str_replace(',', '', trim($value)));

        // Ø§Ú¯Ø± Ø§Ø¹Ø´Ø§Ø± Ù†Ø¯Ø§Ø±ÛŒ Ùˆ Ø³ØªÙˆÙ† decimal Ø§Ø³ØªØŒ .00 Ø§Ø¶Ø§ÙÙ‡ Ø´ÙˆØ¯
        if ($value !== '' && strpos($value, '.') === false) {
            $value .= '.00';
        }

        $this->attributes['total_amount'] = $value === '' ? null : $value;

        Log::debug('setTotalAmount', [
            'input_raw'  => $orig,
            'normalized' => $value,
            'final_attr' => $this->attributes['total_amount'],
        ]);
    }

    public function stage(): string
    {
        return strtolower((string)($this->approval_stage ?? $this->proforma_stage ?? ''));
    }

    /**
     * Determine whether this proforma has already entered the approval workflow.
     */
    public function hasStartedApprovalFlow(): bool
    {
        $stage = $this->stage();
        $approvalStages = ['send_for_approval', 'awaiting_second_approval', 'approved', 'rejected'];

        if (in_array($stage, $approvalStages, true)) {
            return true;
        }

        try {
            return $this->approvals()->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Stages that are treated as final/locked for editing.
     */
    protected function lockedStages(): array
    {
        return ['finalized', 'converted', 'invoiced', 'issued_invoice'];
    }

    public function canEdit(): bool
    {
        $stage = $this->stage();
        return ! in_array($stage, $this->lockedStages(), true);
    }

    public function isLockedForEditing(): bool
    {
        return ! $this->canEdit();
    }

   
}

