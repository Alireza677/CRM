<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnlineChatGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'call_link',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function memberships()
    {
        return $this->hasMany(OnlineChatMembership::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'online_chat_group_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(OnlineChatMessage::class);
    }

    public function lastMessage()
    {
        return $this->hasOne(OnlineChatMessage::class)->latestOfMany();
    }

    public function scopeForUser($query, User $user)
    {
        return $query->whereHas('memberships', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    public function memberRole(User $user): ?string
    {
        // If memberships are already eager loaded we avoid extra queries
        if ($this->relationLoaded('memberships')) {
            return optional($this->memberships->firstWhere('user_id', $user->id))->role;
        }

        return $this->memberships()
            ->where('user_id', $user->id)
            ->value('role');
    }

    public function canManage(User $user): bool
    {
        $role = $this->memberRole($user);
        return in_array($role, [OnlineChatMembership::ROLE_OWNER, OnlineChatMembership::ROLE_ADMIN], true);
    }
}
