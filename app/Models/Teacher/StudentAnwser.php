<?php

namespace App\Models\Teacher;

use App\Models\Traits\UuidTrait;
use Database\Factories\StudentAnwserFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseFactory(StudentAnwserFactory::class)]
class StudentAnwser extends Model
{
    use HasFactory, UuidTrait;
    public $incrementing = false;
    protected $keyType = "string";
    protected $fillable = ['id', 'task_id', 'student_id', 'activit_task_id', 'answer_text', 'answer_option_id', 'is_correct', 'order', 'corrected', 'attempt_number'];

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
