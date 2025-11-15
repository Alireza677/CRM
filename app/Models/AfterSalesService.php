<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AfterSalesService extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'address',
        'coordinator_name',
        'coordinator_mobile',
        'issue_description',
        'created_by_id',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
