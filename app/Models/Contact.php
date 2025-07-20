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
        'address',
        'organization_id',
        'position',
        'department',
        'notes',
    ];

    // اتصال به سازمان
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

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

}
