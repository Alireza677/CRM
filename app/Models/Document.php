<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\AppliesVisibilityScope;

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
    ];
    

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
