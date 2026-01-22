<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function cacheKey(string $key): string
    {
        return 'app_settings.' . $key;
    }

    public static function getValue(string $key, $default = null)
    {
        $cacheKey = static::cacheKey($key);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $value = static::query()->where('key', $key)->value('value');

        if ($value === null) {
            return $default;
        }

        Cache::forever($cacheKey, $value);

        return $value;
    }

    public static function getBool(string $key, $default = false): bool
    {
        $value = static::getValue($key, null);

        if ($value === null) {
            return (bool) $default;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $normalized ?? (bool) $value;
    }

    public static function setValue(string $key, $value): void
    {
        $value = $value === null ? null : (string) $value;

        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forever(static::cacheKey($key), $value);
    }
}
