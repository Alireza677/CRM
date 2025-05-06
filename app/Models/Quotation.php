<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'quotation_date',
        'contact_id',
        'organization_id',
        'opportunity_id',
        'assigned_to',
        'product_manager',
        'quotation_number',
        'billing_address_source',
        'shipping_address_source',
        'province',
        'city',
        'customer_address',
        'postal_code',
        'item_name',
        'quantity',
        'price_list',
        'discount',
        'unit',
        'no_tax_region',
        'tax_type',
        'adjustment_type',
        'adjustment_value',
        'item_total',
        'total_discount',
        'surcharge',
        'subtotal',
        'tax',
        'tax_on_surcharge',
        'tax_deduction',
        'total',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'no_tax_region' => 'boolean',
        'quantity' => 'decimal:2',
        'discount' => 'decimal:2',
        'adjustment_value' => 'decimal:2',
        'item_total' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'surcharge' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'tax_on_surcharge' => 'decimal:2',
        'tax_deduction' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function productManager()
    {
        return $this->belongsTo(User::class, 'product_manager');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_name');
    }

    public function priceList()
    {
        return $this->belongsTo(PriceList::class, 'price_list');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit');
    }
} 