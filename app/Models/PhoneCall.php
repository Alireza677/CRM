<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneCall extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'status',
        'customer_number',
        'customer_id',
        'customer_name',
        'notes',
        'handled_by_user_id',
        'source_identifier',
        'started_at',
        'direction',
        'payload_raw',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'payload_raw' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_user_id');
    }
}
