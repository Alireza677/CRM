<?php

// app/Models/OpportunityNote.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OpportunityNote extends Model
{
    protected $fillable = ['opportunity_id', 'user_id', 'content'];

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
