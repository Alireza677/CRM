<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'module',
        'event',
        'enabled',
        'channels',
        'conditions',
        'subject_template',
        'body_template',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'channels' => 'array',
        'conditions' => 'array',
    ];
}

