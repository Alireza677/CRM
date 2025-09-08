<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'prefix',
        'full_name',
        'company',
        'email',
        'mobile',
        'phone',
        'website',
        'industry',
        'nationality',
        'address',
        'state',
        'city',
        'notes',
        'lead_source',
        'lead_status',
        'lead_date',
        'next_follow_up_date',
        'assigned_to',
    ];

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }


}
