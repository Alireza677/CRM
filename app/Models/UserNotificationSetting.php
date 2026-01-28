<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotificationSetting extends Model
{
    public const EMAIL_RECEIVED_KEY = 'notifications.email_received.enabled';
    public const MUTE_ALL_KEY = 'notifications.mute_all.enabled';

    protected $fillable = [
        'user_id',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'boolean',
    ];

    public static function getBool(int $userId, string $key, bool $default = false): bool
    {
        $value = static::query()
            ->where('user_id', $userId)
            ->where('key', $key)
            ->value('value');

        return $value === null ? $default : (bool) $value;
    }

    public static function setBool(int $userId, string $key, bool $value): self
    {
        return static::query()->updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $value]
        );
    }
}
