<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationRuleApprover extends Model
{
    protected $fillable = ['automation_rule_id','user_id','priority'];

    public function rule()
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
