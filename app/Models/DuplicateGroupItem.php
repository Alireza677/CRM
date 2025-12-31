<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DuplicateGroupItem extends Model
{
    protected $fillable = [
        'duplicate_group_id',
        'entity_type',
        'entity_id',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(DuplicateGroup::class, 'duplicate_group_id');
    }
}
