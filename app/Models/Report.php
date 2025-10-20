<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'model',
        'query_json',
        'visibility',
        'created_by',
        'is_pinned',
        'is_active',
    ];

    protected $casts = [
        'query_json' => 'array',
        'is_pinned' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relations
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sharedUsers()
    {
        return $this->belongsToMany(User::class, 'report_user')
            ->withPivot('can_edit');
    }

    public function runs()
    {
        return $this->hasMany(ReportRun::class);
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function scopeSharedWith($query, $userId)
    {
        return $query->whereHas('sharedUsers', function ($q) use ($userId) {
            $q->where('users.id', $userId);
        });
    }
}
