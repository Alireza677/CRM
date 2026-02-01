<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RoleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignable_type',
        'assignable_id',
        'user_id',
        'role_type',
        'level',
        'base_commission_percent',
        'final_commission_amount',
        'created_by',
        'is_system',
    ];

    protected $casts = [
        'assignable_id' => 'integer',
        'user_id' => 'integer',
        'created_by' => 'integer',
        'base_commission_percent' => 'decimal:2',
        'final_commission_amount' => 'decimal:2',
        'is_system' => 'boolean',
    ];

    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
