<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationCondition extends Model
{
    protected $table = 'automation_conditions';

    protected $fillable = [
        'model_type',
        'field',
        'operator',
        'value',
        'approver1_id',
        'approver2_id',
    ];

    public $timestamps = false;

    // اگر ارتباطی با کاربران دارید:
    public function approver1()
    {
        return $this->belongsTo(User::class, 'approver1_id');
    }

    public function approver2()
    {
        return $this->belongsTo(User::class, 'approver2_id');
    }
}
