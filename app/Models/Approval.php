<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Approval extends Model
{
    use HasFactory;

    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'user_id',
        'status',
        'note',
        'approved_at',
        'step'
    ];
    protected $casts = [
        'approved_at' => 'datetime',
        'step'        => 'integer', 
    ];

    public function approvable()
    {
        return $this->morphTo();
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

