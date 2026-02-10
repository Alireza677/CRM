<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityReminder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'activity_id',
        'followup_id',
        'kind',             // relative | same_day
        'offset_minutes',   // for kind=relative (negative minutes before due)
        'time_of_day',      // for kind=same_day (HH:MM)
        'remind_at',        // for kind=absolute
        'notify_user_id',
        'sent_at',
        'created_by_id',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'remind_at' => 'datetime',
    ];

    public function activity(): BelongsTo { return $this->belongsTo(Activity::class); }
    public function followup(): BelongsTo { return $this->belongsTo(ActivityFollowup::class, 'followup_id'); }
    public function notifyUser(): BelongsTo { return $this->belongsTo(User::class, 'notify_user_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_id'); }
}
