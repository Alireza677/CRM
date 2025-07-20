<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['opportunity_id', 'order_number', 'status'];

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }
}
