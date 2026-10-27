<?php

namespace App\Models\Teacher;

use App\Models\Traits\UuidTrait;
use Database\Factories\ActivityAccessFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseFactory(ActivityAccessFactory::class)]
class ActivityAccess extends Model
{
    use HasFactory, UuidTrait;

    public $incrementing = false;
    protected $keyType = 'uuid';

    protected $fillable = [
        'id',
        'activit_task_id',
        'student_id',
        'first_accessed_at',
    ];

    protected function casts(): array
    {
        return [
            'first_accessed_at' => 'datetime',
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
}
