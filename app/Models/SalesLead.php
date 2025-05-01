<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesLead extends Model
{
    use HasFactory;

    protected $fillable = [
        'prefix',
        'first_name',
        'last_name',
        'company',
        'email',
        'mobile',
        'phone',
        'website',
        'lead_source',
        'lead_status',
        'assigned_to',
        'lead_date',
        'next_follow_up_date',
        'do_not_email',
        'customer_type',
        'industry',
        'nationality',
        'main_test_field',
        'dependent_test_field',
        'address',
        'state',
        'city',
        'notes',
        'description',
        'created_by',
    ];

    protected $casts = [
        'lead_date' => 'date',
        'next_follow_up_date' => 'date',
        'do_not_email' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
} 