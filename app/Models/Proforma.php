<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proforma extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'stage',
        'organization_id',
        'contact_id',
        'total_amount',
        'proforma_date',
        'opportunity_id',
        'assigned_to',
        'is_favorite'
    ];

    protected $casts = [
        'proforma_date' => 'date',
        'total_amount' => 'decimal:2',
        'is_favorite' => 'boolean'
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
} 