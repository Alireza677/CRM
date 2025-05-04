<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Opportunity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'organization_id',
        'contact_id',
        'type',
        'source',
        'assigned_to',
        'success_rate',
        'amount',
        'next_follow_up',
        'description'
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }
}


