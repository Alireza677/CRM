<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelephonyWebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'trace_id',
        'source',
        'received_at',
        'ip',
        'user_agent',
        'headers',
        'payload',
        'query',
        'content_type',
        'processing_status',
        'processed_at',
        'error_message',
        'phone_call_id',
    ];

    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
        'query' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function phoneCall(): BelongsTo
    {
        return $this->belongsTo(PhoneCall::class);
    }
}
