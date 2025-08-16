<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\NotifiesAssignee;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\AutomationRule;

class Proforma extends Model
{
    use HasFactory;
    use NotifiesAssignee;

    protected $fillable = [
        'subject',
        'proforma_date',
        'contact_name',
        'inventory_manager',
        'proforma_stage',   // اگر از این فیلد برای وضعیت استفاده می‌کنی، بماند
        'approval_stage',   // وضعیت تأیید: draft | sent_for_approval | approved | rejected
        'approval_mode',    // standard | override
        'organization_name',
        'sales_opportunity',
        'assigned_to',
        'city',
        'state',
        'postal_code',
        'customer_address',
        'address_type',
        'total_amount',
        'organization_id',
        'contact_id',
        'opportunity_id',
        'is_favorite',
        'stage_id',         // اگر سیستم stage جداگانه داری
        'automation_rule_id',
        'first_approved_by',
        'first_approved_at',
        'approved_by',
    ];

    protected $guarded = ['proforma_number'];

    protected $casts = [
        'proforma_date'      => 'datetime',
        'first_approved_at'  => 'datetime',
        'total_amount'       => 'decimal:2',
        'is_favorite'        => 'boolean',
        // اگر ستون approval_stage داری، این Cast کمک می‌کند
        'approval_stage'     => 'string',
        'approval_mode'      => 'string',
        'proforma_stage'     => 'string',
        'status'             => 'string',

    ];

    /*
    |--------------------------------------------------------------------------
    | روابط اصلی
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

    /**
     * قانون اتوماسیون مرتبط با این پیش‌فاکتور
     * (کلید پیش‌فرض: automation_rule_id)
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
     * کاربری که تأیید نهایی را انجام داده
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * اگر از سیستم قبلیِ approvals (polymorphic) استفاده می‌کنی، بماند
     * در غیر این صورت می‌تونی حذفش کنی.
     */
    public function approvals()
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    /**
     * رکورد مرحله‌ای که هنوز pending است (نفر بعدی که باید تایید کند)
     */
    public function pendingApproval()
    {
        return $this->approvals()
            ->with('approver')
            ->where('status', 'pending')
            ->orderBy('step') // اولویت مرحله
            ->orderBy('id')   // در صورت برابر بودن step
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | برچسب و عنوان نوتیفیکیشن‌ها (برای Trait)
    |--------------------------------------------------------------------------
    */

    public function getModelLabel()
    {
        return 'پیش‌فاکتور';
    }

    public function getNotificationTitle()
    {
        return $this->subject ?? 'بدون عنوان';
    }

    /*
    |--------------------------------------------------------------------------
    | رویدادهای مدل
    |--------------------------------------------------------------------------
    */

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($proforma) {
            // ایجاد شماره پیش‌فاکتور اگر قبلاً تنظیم نشده
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

        // ارقام فارسی/عربی → انگلیسی
        $value = strtr((string) $value, [
            '۰' => '0','۱' => '1','۲' => '2','۳' => '3','۴' => '4',
            '۵' => '5','۶' => '6','۷' => '7','۸' => '8','۹' => '9',
            '٠' => '0','١' => '1','٢' => '2','٣' => '3','٤' => '4',
            '٥' => '5','٦' => '6','٧' => '7','٨' => '8','٩' => '9',
        ]);

        // حذف جداکننده هزارگان و کاراکترهای غیرعددی (به‌جز نقطه و منفی)
        $value = preg_replace('/[^\d\.\-]/', '', str_replace(',', '', trim($value)));

        // اگر اعشار نداری و ستون decimal است، .00 اضافه شود
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
}
