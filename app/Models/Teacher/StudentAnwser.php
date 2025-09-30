<?php

namespace App\Models\Teacher;

use App\Models\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class StudentAnwser extends Model
{
    use UuidTrait;
    public $incrementing = false;
    protected $keyType = "string";
    protected $fillable = ['id', 'task_id', 'student_id', 'activit_task_id', 'answer_text', 'is_correct', 'order'];

    /**
     * return student who answered
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
