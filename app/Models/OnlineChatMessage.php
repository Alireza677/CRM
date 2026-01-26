<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnlineChatMessage extends Model
{
    use HasFactory;

    private const MENTION_PATTERN = '/@\\[([^\\]]+)\\]\\(user:(\\d+)\\)/u';

    protected $fillable = [
        'online_chat_group_id',
        'sender_id',
        'body',
        'image_path',
        'image_title',
        'file_path',
        'file_name',
        'file_size',
        'file_mime',
    ];

    public function group()
    {
        return $this->belongsTo(OnlineChatGroup::class, 'online_chat_group_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function mentions()
    {
        return $this->belongsToMany(User::class, 'online_chat_message_mentions', 'message_id', 'user_id')
            ->withTimestamps()
            ->withPivot('notified_at');
    }

    public static function extractMentionIds(string $body): array
    {
        if ($body === '') {
            return [];
        }

        preg_match_all(self::MENTION_PATTERN, $body, $matches);

        return collect($matches[2] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    public static function renderBodyForDisplay(string $body): string
    {
        if ($body === '') {
            return '';
        }

        return preg_replace_callback(self::MENTION_PATTERN, function ($match) {
            $name = trim((string) ($match[1] ?? ''));
            return $name !== '' ? '@' . $name : '@';
        }, $body) ?? $body;
    }
}
