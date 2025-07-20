<?php

// app/Helpers/NotificationHelper.php

namespace App\Helpers;

use Illuminate\Support\Str;
use Illuminate\Notifications\Notifiable;

class NotificationHelper
{
    public static function send($notifiable, string $type, array $data): void
    {
        if (!in_array(Notifiable::class, class_uses_recursive(get_class($notifiable)))) {
            throw new \Exception("این کلاس قابلیت دریافت اعلان را ندارد.");
        }

        $notifiable->notifications()->create([
            'id' => (string) Str::uuid(), // 👈 ایجاد UUID یکتا
            'type' => $type,
            'data' => $data,
        ]);
    }
}
