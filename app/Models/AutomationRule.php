<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationRule extends Model
{
    // فیلدهایی که اجازه داریم از طریق فرم یا کد ذخیره کنیم:
    protected $fillable = [
        'proforma_stage',
        'operator',
        'value',
        'approver_1',
        'approver_2'
    ];

    // تعریف رابطه بین این قانون و کاربر هدف:
    public function user()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
    public function approvers()
    {
        return $this->belongsToMany(User::class)->withPivot('role');
    }


}

