<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'mail_message_id',
        'filename',
        'mime',
        'size',
        'storage_path',
        'content_id',
        'is_inline',
    ];

    protected $casts = [
        'is_inline' => 'boolean',
        'size'      => 'integer',
    ];

    public function message()
    {
        return $this->belongsTo(MailMessage::class, 'mail_message_id');
    }
}
