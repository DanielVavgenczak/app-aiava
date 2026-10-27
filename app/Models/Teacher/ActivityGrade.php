<?php

namespace App\Models\Teacher;

use App\Models\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class ActivityGrade extends Model
{
    use UuidTrait;

    public $incrementing = false;
    protected $keyType = 'uuid';

    protected $fillable = [
        'id',
        'activit_task_id',
        'student_id',
        'teacher_id',
        'final_grade',
        'feedback',
        'registered_at',
    ];

    protected function casts(): array
    {
        return [
            'final_grade' => 'decimal:2',
            'registered_at' => 'datetime',
        ];
    }
    public function activitTask()
    {
        return $this->belongsTo(ActivitTask::class, 'activit_task_id');
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
