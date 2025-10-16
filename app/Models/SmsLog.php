<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class SmsLog extends Model
{
    protected $fillable = [
        'to',
        'type',
        'pattern_code',
        'message',
        'values',
        'provider_message_id',
        'status_code',
        'status_text',
        'provider_response',
        'sent_by', // 👈 فیلد جدید برای ثبت کاربر ارسال‌کننده
    ];

    protected $casts = [
        'values'            => 'array',
        'provider_response' => 'array',
    ];

    /**
     * ارتباط با کاربر ارسال‌کننده
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
