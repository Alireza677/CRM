<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $notifications = DB::table('notifications')
            ->where('type', \App\Notifications\CustomRoutedNotification::class)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.module')) = 'emails'")
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.event')) = 'received'")
            ->where(function ($q) {
                $q->whereNull(DB::raw("JSON_EXTRACT(data, '$.title')"))
                  ->orWhere(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.title'))"), '=', '')
                  ->orWhere(DB::raw("JSON_EXTRACT(data, '$.title')"), '=', 'null');
            })
            ->get(['id', 'data']);

        foreach ($notifications as $row) {
            $data = json_decode($row->data, true) ?: [];
            $title = $data['title'] ?? $data['message'] ?? 'ایمیل جدید دارید';

            $data['title'] = $title;
            $data['message'] = $data['message'] ?? $title;

            DB::table('notifications')
                ->where('id', $row->id)
                ->update(['data' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
        }
    }

    public function down(): void
    {
        // no-op
    }
};
