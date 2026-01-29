<?php

namespace App\Models;

use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\Task;
use App\Models\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory, UuidTrait;
    public $incrementing = false;
    protected $keyType = "string";
    protected $fillable = ['id','title','discipline_id'];

    public function discipline()
    {
        return $this->belongsTo(Discipline::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }
    public function activitTasks()
    {
        return $this->hasMany(ActivitTask::class);
    }

    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = trim($value);
    }
    
    // 👇 SCOPE para buscar por título normalizado
    public function scopeWhereNormalizedTitle($query, $title)
    {
        return $query->whereRaw('LOWER(TRIM(title)) = LOWER(TRIM(?))', [$title]);
    }
}
