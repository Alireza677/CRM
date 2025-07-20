<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\NotifiesAssignee;

class Proforma extends Model
{
    use HasFactory;
    use NotifiesAssignee;

    protected $fillable = [
        'subject',
        'proforma_date',
        'contact_name',
        'inventory_manager',
        'proforma_number',
        'proforma_stage',
        'organization_name',
        'sales_opportunity',
        'assigned_to',
        'city',
        'state',
        'postal_code',
        'customer_address',
        'address_type',
        'total_amount',
        'organization_id',
        'contact_id',
        'opportunity_id',
        'is_favorite'
    ];

    protected $casts = [
        'proforma_date' => 'datetime',
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

    public function items()
    {
        return $this->hasMany(ProformaItem::class);
    }
    public function getModelLabel()
{
    return 'پیش‌فاکتور';
}
public function getNotificationTitle()
{
    return $this->subject ?? 'بدون عنوان';
}
public function approvals()
{
    return $this->morphMany(Approval::class, 'approvable');
}

} 