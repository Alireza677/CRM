<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Note extends Model
{
    protected $fillable = ['body', 'user_id'];

    public function notable()
    {
        return $this->morphTo(null, 'noteable_type', 'noteable_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function getMentionsAttribute()
    {
        // تمام usernameهایی که با @ شروع می‌شن رو از body جدا می‌کنیم
        preg_match_all('/@([a-zA-Z0-9_]+)/u', $this->body, $matches);

        // اگر یوزرنیم‌هایی پیدا شد، کاربرانش رو واکشی می‌کنیم
        if (!empty($matches[1])) {
            return User::whereIn('username', $matches[1])->get();
        }

        return collect(); // اگر چیزی نبود، کالکشن خالی برمی‌گردونه
    }
}
