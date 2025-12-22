<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Approval extends Model
{
    use HasFactory;

    public const STATUS_SUPERSEDED = 'superseded';

    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'user_id',
        'approved_by',
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

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
