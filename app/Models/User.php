<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles; // ← تکراری نبودن HasRoles

    protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'mobile',        // ← اضافه شد
        'password',
        'is_admin',      // ← اگر از فلگ دیتابیسی استفاده می‌کنی
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'mobile_verified_at' => 'datetime', // ← اضافه شد
            'password'           => 'hashed',
        ];
    }

    public function automationRules()
    {
        return $this->belongsToMany(AutomationRule::class)->withPivot('role');
    }

    protected static function booted(): void
    {
        static::creating(function ($user) {
            if (empty($user->username)) {
                // ساخت username از نام
                $base = \Str::slug($user->name, '_') ?: 'user';

                // اگر تکراری بود، عدد اضافه کن
                $counter = 1;
                $username = $base;
                while (User::where('username', $username)->exists()) {
                    $username = $base . '_' . $counter++;
                }

                $user->username = $username;
            }
        });
    }

    public function mentionedInNotes()
    {
        return $this->belongsToMany(\App\Models\Note::class, 'note_mentions', 'user_id', 'note_id')
            ->withTimestamps()
            ->withPivot('notified_at');
    }

    public function isAdmin(): bool
    {
        // 1) فلگ دیتابیس
        if ((bool) $this->getAttribute('is_admin')) {
            return true;
        }

        // 2) نقش‌های Spatie (بدون چک permission)
        if (method_exists($this, 'hasAnyRole')) {
            try {
                if ($this->hasAnyRole(['admin', 'super-admin'])) {
                    return true;
                }
            } catch (\Throwable $e) { /* ignore */ }
        }

        // 3) fallback های احتمالی پروژه
        if (isset($this->role_name) && in_array(strtolower($this->role_name), ['admin','super-admin'], true)) {
            return true;
        }
        if (method_exists($this, 'roles')) {
            try {
                if ($this->roles()->whereIn('name', ['admin','super-admin'])->exists()) {
                    return true;
                }
            } catch (\Throwable $e) { /* ignore */ }
        }
        if (isset($this->role) && is_object($this->role) && isset($this->role->name)) {
            return in_array(strtolower($this->role->name), ['admin','super-admin'], true);
        }

        return false;
    }
}
