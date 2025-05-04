<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sales_start_date',
        'sales_end_date',
        'support_start_date',
        'support_end_date',
        'category_id',
        'supplier_id',
        'manufacturer',
        'series',
        'length',
        'unit_price',
        'has_vat',
        'is_active',
        'website',
        'part_number',
        'type',
        'thermal_power',
        'commission',
        'purchase_cost',
    ];

    protected $casts = [
        'sales_start_date' => 'date',
        'sales_end_date' => 'date',
        'support_start_date' => 'date',
        'support_end_date' => 'date',
        'length' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'has_vat' => 'boolean',
        'is_active' => 'boolean',
        'thermal_power' => 'decimal:2',
        'commission' => 'decimal:2',
        'purchase_cost' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
} 