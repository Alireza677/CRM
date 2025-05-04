<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'supplier_id',
        'purchase_date',
        'status',
        'total',
        'assigned_to',
        'description',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'total' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public static function getTypes()
    {
        return [
            'purchase' => 'فرم خرید',
            'sales' => 'فرم فروش',
            'inventory' => 'فرم موجودی',
            'service' => 'فرم خدمات',
        ];
    }

    public static function getStatuses()
    {
        return [
            'draft' => 'پیش‌نویس',
            'pending' => 'در انتظار',
            'approved' => 'تایید شده',
            'rejected' => 'رد شده',
        ];
    }
} 