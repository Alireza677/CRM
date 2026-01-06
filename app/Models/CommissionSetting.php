<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionSetting extends Model
{
    protected $fillable = [
        'role_percents',
    ];

    protected $casts = [
        'role_percents' => 'array',
    ];

    public static function resolveRolePercents(): array
    {
        $defaults = config('commission.roles', []);
        $settings = static::query()->first();

        if (! $settings || ! is_array($settings->role_percents)) {
            return $defaults;
        }

        $overrides = array_intersect_key($settings->role_percents, $defaults);

        return array_merge($defaults, $overrides);
    }
}
