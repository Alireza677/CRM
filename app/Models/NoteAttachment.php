<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoteAttachment extends Model
{
    protected $fillable = ['note_id', 'file_path', 'file_name', 'file_size', 'file_mime'];

    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }
}
