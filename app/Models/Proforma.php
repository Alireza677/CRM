<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\NotifiesAssignee;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class Proforma extends Model
{
    use HasFactory;
    use NotifiesAssignee;

    protected $fillable = [
        'subject',
        'proforma_date',
        'contact_name',
        'inventory_manager',
        'proforma_stage',
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
        'stage_id',
    ];
    protected $guarded = ['proforma_number'];

    protected $casts = [
        'proforma_date' => 'datetime',
        'total_amount' => 'decimal:2',
        'is_favorite' => 'boolean'
    ];

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
    public function getModelLabel()
{
    return 'پیش‌فاکتور';
}
public function getNotificationTitle()
{
    return $this->subject ?? 'بدون عنوان';
}
public function approvals()
{
    return $this->morphMany(Approval::class, 'approvable');
}
protected static function boot()
{
    parent::boot();

    static::creating(function ($proforma) {
        // فقط اگر شماره قبلاً تنظیم نشده باشد
        if (empty($proforma->proforma_number)) {
            $latestId = self::max('id') + 1;
            $proforma->proforma_number = 'QU' . str_pad($latestId, 5, '0', STR_PAD_LEFT);
        }
    });
}
public function setTotalAmountAttribute($value)
{
    $orig = $value; // ← اضافه کنید 
    if ($value === null || $value === '') {
        Log::debug('setTotalAmount: empty input', ['input' => $value]);

        $this->attributes['total_amount'] = null;
        return;
    }

    // ارقام فارسی/عربی → انگلیسی
    $value = strtr((string)$value, [
        '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
        '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
    ]);

    // حذف جداکننده هزارگان و کاراکترهای غیرعددی (به‌جز نقطه و منفی)
    $value = preg_replace('/[^\d\.\-]/', '', str_replace(',', '', trim($value)));

    // اگر اعشار نداری و ستون decimal است، .00 اضافه شود
    if ($value !== '' && strpos($value, '.') === false) {
        $value .= '.00';
    }

    $this->attributes['total_amount'] = $value === '' ? null : $value;
    Log::debug('setTotalAmount', [
        'input_raw'   => $orig,
        'normalized'  => $value,
        'final_attr'  => $this->attributes['total_amount'],
    ]);
}


} 