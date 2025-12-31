<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DuplicateGroup extends Model
{
    protected $fillable = [
        'entity_type',
        'match_key',
        'match_value',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(DuplicateGroupItem::class, 'duplicate_group_id');
    }
}
