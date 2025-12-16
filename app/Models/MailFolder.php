<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'mailbox_id',
        'name',
        'imap_path',
        'uid_validity',
        'last_uid',
        'last_sync_at',
    ];

    protected $casts = [
        'last_uid'     => 'integer',
        'uid_validity' => 'integer',
        'last_sync_at' => 'datetime',
    ];

    public function mailbox()
    {
        return $this->belongsTo(Mailbox::class);
    }

    public function messages()
    {
        return $this->hasMany(MailMessage::class, 'folder_id');
    }
}
