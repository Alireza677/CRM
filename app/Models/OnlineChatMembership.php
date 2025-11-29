<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnlineChatMembership extends Model
{
    use HasFactory;

    public const ROLE_OWNER  = 'owner';
    public const ROLE_ADMIN  = 'admin';
    public const ROLE_MEMBER = 'member';

    protected $table = 'online_chat_group_user';

    protected $fillable = [
        'online_chat_group_id',
        'user_id',
        'role',
    ];

    public function group()
    {
        return $this->belongsTo(OnlineChatGroup::class, 'online_chat_group_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
