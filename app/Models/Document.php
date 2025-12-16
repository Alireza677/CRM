<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\AppliesVisibilityScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use AppliesVisibilityScope;
    protected $fillable = [
        'owner_user_id',
        'title',
        'type',
        'file_path',
        'opportunity_id',
        'purchase_order_id',
        'user_id',
        'assigned_to',
        'team_id',
        'department',
        'visibility',
        'is_voided',
        'voided_at',
        'voided_by',
    ];

    protected $casts = [
        'is_voided' => 'boolean',
        'voided_at' => 'datetime',
    ];
    

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'voided_by');
    }

    public function scopeVoided(Builder $query): Builder
    {
        return $query->where('is_voided', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder->where('is_voided', false)->orWhereNull('is_voided');
        });
    }
}
