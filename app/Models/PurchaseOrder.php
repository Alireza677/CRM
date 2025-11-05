<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'purchase_type',
        'supplier_id',
        'requested_by',
        'request_date',
        'purchase_date',
        'needed_by_date',
        'status',
        'settlement_type',
        'usage_type',
        'project_name',
        'vat_percent',
        'vat_amount',
        'total_amount',
        'total_with_vat',
        'previously_paid_amount',
        'remaining_payable_amount',
        'assigned_to',
        'ready_for_delivery_notified_at',
        'description',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'request_date' => 'date',
        'needed_by_date' => 'date',
        'vat_percent' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_with_vat' => 'decimal:2',
        'previously_paid_amount' => 'decimal:2',
        'remaining_payable_amount' => 'decimal:2',
        'ready_for_delivery_notified_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function requestedByUser()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    // Notes (polymorphic)
    public function notes()
    {
        return $this->morphMany(\App\Models\Note::class, 'noteable')->latest();
    }

    public function lastNote()
    {
        return $this->morphOne(\App\Models\Note::class, 'noteable')->latestOfMany();
    }

    // Activity logs (if present)
    public function activities()
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }

    // Approvals (polymorphic) for timeline/steps
    public function approvals()
    {
        return $this->morphMany(\App\Models\Approval::class, 'approvable');
    }

    public function pendingApproval()
    {
        return $this->approvals()
            ->with('approver')
            ->where('status', 'pending')
            ->orderBy('step')
            ->orderBy('id')
            ->first();
    }

    // وضعیت‌های مجاز سفارش خرید برای استفاده در فرم‌ها و اعتبارسنجی
    // نکته: برچسب‌های فارسی مطابق نمایش در UI تنظیم شده‌اند
    public static function statuses(): array
    {
        return [
            'created'             => 'ایجاد شده',
            'supervisor_approval' => 'تأیید سرپرست کارخانه',
            'manager_approval'    => 'تأیید مدیر کل',
            'accounting_approval' => 'تأیید حسابداری / پرداخت',
            'purchasing'          => 'در حال خرید',
            'purchased'           => 'خرید انجام شده',
            'warehouse_delivered' => 'تحویل انبار',
            'rejected'            => 'رد شده',
        ];
    }

    protected static function booted()
    {
        static::created(function (self $po) {
            // If not assigned yet, set a deterministic P-prefixed number based on ID
            if (empty($po->po_number)) {
                $po->po_number = 'p' . str_pad((string)$po->id, 6, '0', STR_PAD_LEFT);
                // Use saveQuietly to avoid triggering observers/logging
                if (method_exists($po, 'saveQuietly')) {
                    $po->saveQuietly();
                } else {
                    $po->save();
                }
            }
        });
    }
}
