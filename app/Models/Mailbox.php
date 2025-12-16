<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Mailbox extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_address',
        'imap_host',
        'imap_port',
        'imap_encryption',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'username',
        'password_encrypted',
        'is_active',
        'last_sync_at',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'last_sync_at'=> 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function folders()
    {
        return $this->hasMany(MailFolder::class);
    }

    public function messages()
    {
        return $this->hasMany(MailMessage::class);
    }

    public function getPasswordAttribute(): ?string
    {
        if (empty($this->password_encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->password_encrypted);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
