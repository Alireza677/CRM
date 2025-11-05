<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderWorkflowSetting extends Model
{
    protected $fillable = [
        'first_approver_id',
        'second_approver_id',
        'accounting_user_id',
        'first_approver_substitute_id',
        'second_approver_substitute_id',
        'accounting_approver_substitute_id',
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

    public function firstApproverSubstitute()
    {
        return $this->belongsTo(User::class, 'first_approver_substitute_id');
    }

    public function secondApproverSubstitute()
    {
        return $this->belongsTo(User::class, 'second_approver_substitute_id');
    }

    public function accountingApproverSubstitute()
    {
        return $this->belongsTo(User::class, 'accounting_approver_substitute_id');
    }
}
