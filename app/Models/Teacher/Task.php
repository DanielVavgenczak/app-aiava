<?php

namespace App\Models\Teacher;

use App\Models\Traits\UuidTrait;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseFactory(TaskFactory::class)]
class Task extends Model
{
    use HasFactory, UuidTrait;
    public $incrementing = false;
    protected $keyType = "uuid";
    protected $fillable = ['id', 'question', 'type', 'activit_task_id', 'value', 'order'];

    public function activitTask()
    {
        return $this->belongsTo(ActivitTask::class, 'activit_task_id');
    }

    public function options()
    {
        return $this->hasMany(TaskOptions::class);
    }

    public function studentAnwsers()
    {
        return $this->hasMany(StudentAnwser::class, 'task_id');
    }
}
