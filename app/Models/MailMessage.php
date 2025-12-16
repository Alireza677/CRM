<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'mailbox_id',
        'folder_id',
        'imap_uid',
        'message_id',
        'subject',
        'from_name',
        'from_email',
        'to',
        'cc',
        'thread_key',
        'in_reply_to',
        'references',
        'date',
        'snippet',
        'body_text',
        'body_html',
        'is_read',
        'is_starred',
        'is_archived',
        'is_deleted',
    ];

    protected $casts = [
        'to'       => 'array',
        'cc'       => 'array',
        'references' => 'array',
        'imap_uid' => 'integer',
        'is_read'  => 'boolean',
        'is_starred' => 'boolean',
        'is_archived' => 'boolean',
        'is_deleted' => 'boolean',
        'date'     => 'datetime',
    ];

    public function mailbox()
    {
        return $this->belongsTo(Mailbox::class);
    }

    public function folder()
    {
        return $this->belongsTo(MailFolder::class, 'folder_id');
    }

    public function attachments()
    {
        return $this->hasMany(MailAttachment::class, 'mail_message_id');
    }
}
