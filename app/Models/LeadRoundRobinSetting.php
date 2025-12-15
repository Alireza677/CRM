<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadRoundRobinSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'sla_duration_value',
        'sla_duration_unit',
        'max_reassign_count',
    ];
}
