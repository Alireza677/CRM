<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\AppliesVisibilityScope;

class Lead extends Model
{
    use HasFactory, AppliesVisibilityScope;

    protected $fillable = [
        'owner_user_id',
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
        'team_id',
        'department',
        'visibility',
    ];
    protected $table = 'sales_leads';
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }


}
