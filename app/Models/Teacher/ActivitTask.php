<?php

namespace App\Models\Teacher;

use App\Models\Module;
use App\Models\Traits\UuidTrait;
use Database\Factories\ActivitTaskFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[UseFactory(ActivitTaskFactory::class)]
class ActivitTask extends Model
{
    use HasFactory, UuidTrait;
    public $incrementing = false;
    protected $keyType = "uuid";
    protected $fillable = ['id', 'title', 'start', 'end', 'module_id', 'discipline_id', 'teacher_id','total_value','is_avaliative'];

    protected function casts(): array
    {
        return [
            'start' => 'datetime',
            'end' => 'datetime',
            'is_avaliative' => 'boolean',
            'total_value' => 'decimal:2',
        ];
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
    public function module()
    {
        return $this->belongsTo(Module::class);
    }
    public function discipline()
    {
        return $this->belongsTo(Discipline::class);
    }
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Indica se a atividade está dentro da janela de disponibilidade.
     * Datas nulas significam sem restrição.
     */
    public function isOpenForAnswers(): bool
    {
        $now = now();

        if ($this->start && $now->lt($this->start)) {
            return false;
        }

        if ($this->end && $now->gt($this->end)) {
            return false;
        }

        return true;
    }

    public function studentAnwsers()
    {
        return $this->hasMany(StudentAnwser::class, 'activit_task_id');
    }

    public function accesses()
    {
        return $this->hasMany(ActivityAccess::class, 'activit_task_id');
    }

    public function activityGrades()
    {
        return $this->hasMany(ActivityGrade::class, 'activit_task_id');
    }

    /**
     * Restringe a consulta às atividades pertencentes ao professor informado,
     * considerando também a disciplina vinculada. Sem professor, nega tudo.
     */
    public function scopeOwnedByTeacher(Builder $query, ?Teacher $teacher = null): Builder
    {
        return $query
            ->where('teacher_id', $teacher?->id)
            ->whereHas('discipline', function ($disciplineQuery) use ($teacher) {
                $disciplineQuery->where('teacher_id', $teacher?->id);
            });
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->title  = 'atividade ' . now()->format('d/m/Y') . Str::uuid()->toString();
        });
    }
}
