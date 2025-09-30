<?php

namespace App\Models\Teacher;

use App\Models\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class QuestionFeedback extends Model
{
    use UuidTrait;
    public $incrementing = false;
    protected $keyType = "uuid";
    protected $fillable = [
        'id',
        'feedback',
        'student_anwser_id',
        'task_id',
        'student_id',
        'teacher_id',
        'registered_at',
    ];

    public function studentAnwser()
    {
        return $this->belongsTo(StudentAnwser::class, 'student_anwser_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
}
