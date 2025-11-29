<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class OnlineMeeting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'scheduled_at',
        'duration_minutes',
        'notes',
        'room_name',
        'jitsi_url',
        'online_chat_group_id',
        'related_type',
        'related_id',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function related()
    {
        return $this->morphTo();
    }

    public function chatGroup()
    {
        return $this->belongsTo(OnlineChatGroup::class, 'online_chat_group_id');
    }

    public static function generateUniqueRoomName(?string $relatedType, ?int $relatedId, $scheduledAt): string
    {
        $timePart = $scheduledAt
            ? Carbon::parse($scheduledAt)->format('YmdHis')
            : now()->format('YmdHis');

        $typePart = $relatedType
            ? strtolower(class_basename($relatedType))
            : 'general';

        $idPart = $relatedId ?: 'na';

        $base = Str::slug("meet-{$typePart}-{$idPart}-{$timePart}", '-', 'en');
        if ($base === '') {
            $base = 'meet-' . Str::random(6);
        }

        $room = $base;
        $counter = 1;
        while (self::withTrashed()->where('room_name', $room)->exists()) {
            $room = $base . '-' . $counter;
            $counter++;
        }

        return $room;
    }
}
