<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AutomationRuleApprover;
use App\Models\User;

class AutomationRule extends Model
{
    protected $fillable = [
        'proforma_stage',
        'operator',
        'value',
        'emergency_approver_id',
    ];

    /* روابط اصلی */
    public function emergencyApprover()
    {
        return $this->belongsTo(User::class, 'emergency_approver_id');
    }

    // لیست کل تأییدکننده‌ها (priority 1,2,...)
    public function approvers()
    {
        return $this->hasMany(AutomationRuleApprover::class)
                    ->orderBy('priority');
    }

    /* روابط/اکسسورهای کمکی برای فرم و نمایش */

    // رکورد approver با priority=1
    public function approver1Relation()
    {
        return $this->hasOne(AutomationRuleApprover::class)
                    ->where('priority', 1);
    }

    // رکورد approver با priority=2
    public function approver2Relation()
    {
        return $this->hasOne(AutomationRuleApprover::class)
                    ->where('priority', 2);
    }

    // دسترسی سریع به خود Userها
    public function getApprover1IdAttribute()
    {
        return $this->relationLoaded('approver1Relation')
            ? optional($this->approver1Relation)->user_id
            : optional($this->approvers->firstWhere('priority', 1))->user_id;
    }
    public function getApprover2IdAttribute()
    {
        return $this->relationLoaded('approver2Relation')
            ? optional($this->approver2Relation)->user_id
            : optional($this->approvers->firstWhere('priority', 2))->user_id;
    }
}
