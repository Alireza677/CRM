<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Opportunity;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'mobile',
        'company',
        'city',
        'address',
        'organization_id',
        'opportunity_id',
        'assigned_to',
    ];
    
    

    // فرصت‌های فروش مرتبط با مخاطب
    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
    }

    // ترکیب نام و نام خانوادگی برای نمایش
    public function getNameAttribute()
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
    public function proformas()
    {
        return $this->hasMany(Proforma::class);
    }
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

}
