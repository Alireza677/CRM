<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'title',
        'type',
        'file_path',
        'opportunity_id',
        'user_id',
    ];
    

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
