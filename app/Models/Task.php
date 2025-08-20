<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_URGENT = 'urgent';

    public const STATUS_PENDING = 'pending';
    public const STATUS_DONE    = 'done';

    protected $fillable = [
        'project_id',
        'title', 'description',
        'priority', 'status',
        'assigned_to', 
    ];

    // هر تسک متعلق به یک پروژه است
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // متد راحت برای تایید انجام کار
    public function markDone(): void
    {
        $this->status = self::STATUS_DONE;
        $this->save();
    }
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function notes()
    {
        return $this->morphMany(\App\Models\Note::class, 'noteable')->latest();
    }

}
