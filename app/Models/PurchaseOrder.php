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
        'total_amount',
        'previously_paid_amount',
        'remaining_payable_amount',
        'assigned_to',
        'description',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'request_date' => 'date',
        'needed_by_date' => 'date',
        'total_amount' => 'decimal:2',
        'previously_paid_amount' => 'decimal:2',
        'remaining_payable_amount' => 'decimal:2',
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
} 
