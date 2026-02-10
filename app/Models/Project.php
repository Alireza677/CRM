<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = ['name','description','manager_id','status','start_date','due_date'];

    // مسئول پروژه (یک کاربر)
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // اعضای پروژه (چند کاربر)
    public function members()
    {
        return $this->belongsToMany(User::class, 'project_user')
                    ->withTimestamps();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
