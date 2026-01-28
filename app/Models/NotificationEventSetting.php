<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class NotificationEventSetting extends Model
{
    protected $fillable = [
        'module',
        'event',
        'sound_path',
        'icon_path',
        'sound_enabled',
    ];

    protected $casts = [
        'sound_enabled' => 'boolean',
    ];

    public static function cacheKey(): string
    {
        return 'notification_event_settings_map';
    }

    public static function clearCache(): void
    {
        Cache::forget(static::cacheKey());
    }

    public static function getCachedMap(): array
    {
        return Cache::remember(static::cacheKey(), 300, function () {
            return static::buildMap();
        });
    }

    public static function buildMap(): array
    {
        return static::query()
            ->get()
            ->mapWithKeys(function (self $setting) {
                $key = $setting->module.'.'.$setting->event;
                $soundUrl = $setting->sound_path
                    ? Storage::disk('public')->url($setting->sound_path)
                    : null;
                $iconUrl = $setting->icon_path
                    ? Storage::disk('public')->url($setting->icon_path)
                    : null;

                return [
                    $key => [
                        'module' => $setting->module,
                        'event' => $setting->event,
                        'sound_enabled' => (bool) $setting->sound_enabled,
                        'sound_url' => $soundUrl,
                        'icon_url' => $iconUrl,
                    ],
                ];
            })
            ->all();
    }
}
