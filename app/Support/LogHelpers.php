<?php
namespace App\Support;

class LogHelpers
{
    public static function maskToken(?string $token): string
    {
        if (!$token) return '[empty]';
        $len = mb_strlen($token);
        if ($len <= 8) return str_repeat('*', $len);
        return mb_substr($token, 0, 4) . str_repeat('*', max(0, $len - 8)) . mb_substr($token, -4);
    }

    public static function clip(mixed $val, int $limit = 2000): mixed
    {
        $s = is_string($val) ? $val : json_encode($val, JSON_UNESCAPED_UNICODE);
        if ($s === false) return '[unserializable]';
        return mb_strlen($s) > $limit ? (mb_substr($s, 0, $limit) . '...') : $s;
    }
}
