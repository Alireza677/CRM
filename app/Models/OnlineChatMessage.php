<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnlineChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'online_chat_group_id',
        'sender_id',
        'body',
        'image_path',
        'image_title',
        'file_path',
        'file_name',
        'file_size',
        'file_mime',
    ];

    public function group()
    {
        return $this->belongsTo(OnlineChatGroup::class, 'online_chat_group_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
