<?php

namespace App\Models\Teacher;

use App\Models\Traits\UuidTrait;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseFactory(TaskFactory::class)]
class Task extends Model
{
    use HasFactory, UuidTrait;
    public $incrementing = false;
    protected $keyType = "uuid";
    protected $fillable = ['id', 'question', 'type', 'activit_task_id', 'value', 'order'];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'order' => 'integer',
        ];
    }

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

    public function feedbacks()
    {
        return $this->hasMany(QuestionFeedback::class, 'task_id');
    }

    /**
     * Ordena as questões conforme a posição definida pelo professor.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

    /**
     * Restringe a consulta às questões cuja atividade pertence ao professor.
     */
    public function scopeOwnedByTeacher(Builder $query, ?Teacher $teacher = null): Builder
    {
        return $query->whereHas('activitTask', function ($activitQuery) use ($teacher) {
            $activitQuery->ownedByTeacher($teacher);
        });
    }
}
