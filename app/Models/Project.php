<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name','description','manager_id'];

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
