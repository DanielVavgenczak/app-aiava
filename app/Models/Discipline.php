<?php

namespace App\Models;

use App\Models\Forum\ForumTopic;
use App\Models\Teacher\Student;
use App\Models\Teacher\Teacher;
use App\Models\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Discipline extends Model
{
    use HasFactory, UuidTrait;
    public $incrementing = false;

    protected $keyType = "uuid";
    protected $fillable = ['teacher_id', 'title'];

    public function Teacher()
    {
        return $this->belongsTo(related: Teacher::class);
    }
    public function modules()
    {
        return $this->hasMany(related: Module::class);
    }
    public function students()
    {
        return $this->hasMany(related: Student::class);
    }

    public function ForumTopics()
    {
        return $this->hasMany(related: ForumTopic::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->title = trim($model->title);
        });
        static::updating(function ($model) {
            $model->title = trim($model->title);
        });
    }
    public static function teacherHasDiscipline($teacher_id, $title)
    {
        $normalizedTitle = Str::lower(trim($title));
        return Discipline::where('teacher_id', $teacher_id)
            ->whereRaw('LOWER(trim(title)) = ?', [$normalizedTitle])
            ->exists();
    }
}
