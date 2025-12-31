<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class EntityMerge extends Model
{
    protected $fillable = [
        'entity_type',
        'winner_id',
        'loser_id',
        'field_resolution',
        'relations_moved',
        'user_id',
    ];

    protected $casts = [
        'field_resolution' => 'array',
        'relations_moved' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
