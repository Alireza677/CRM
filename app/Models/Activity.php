<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = ['opportunity_id', 'title', 'status', 'due_date'];

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }
    public function user()
{
    return $this->belongsTo(User::class);
}

}
