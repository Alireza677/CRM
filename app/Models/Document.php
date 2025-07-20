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
    ];
    

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }
}
