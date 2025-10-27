<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderWorkflowSetting extends Model
{
    protected $fillable = [
        'first_approver_id',
        'second_approver_id',
        'accounting_user_id',
    ];

    public function firstApprover()
    {
        return $this->belongsTo(User::class, 'first_approver_id');
    }

    public function secondApprover()
    {
        return $this->belongsTo(User::class, 'second_approver_id');
    }

    public function accountingUser()
    {
        return $this->belongsTo(User::class, 'accounting_user_id');
    }
}

