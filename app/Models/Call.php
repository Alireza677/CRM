<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Call extends Model
{
    protected $fillable = ['opportunity_id', 'subject', 'call_time', 'notes'];

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }
}


