<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'module',
        'is_default',
        'is_active',
        'content',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public static function getModules()
    {
        return [
            'products' => 'محصولات',
            'suppliers' => 'تأمین‌کنندگان',
            'purchase_orders' => 'سفارش‌های خرید',
            'customers' => 'مشتریان',
            'opportunities' => 'فرصت‌های فروش',
        ];
    }
} 