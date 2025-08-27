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
    use HasFactory, Notifiable, HasRoles;


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
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
        

}
