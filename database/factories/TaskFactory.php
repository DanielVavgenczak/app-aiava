<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Task;
use App\Models\Teacher\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher\Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'question' => $this->faker->sentence() . '?',
            'type' => 'dissertative',
            'order' => 1,
            'value' => null,
            'module_id' => Module::factory(),
            'discipline_id' => Discipline::factory(),
            'teacher_id' => Teacher::factory(),
        ];
    }

    /**
     * Mantém a tarefa no mesmo vínculo (professor/módulo/disciplina) da atividade.
     */
    public function forActivitTask(ActivitTask $activitTask): static
    {
        return $this->state(fn () => [
            'module_id' => $activitTask->module_id,
            'discipline_id' => $activitTask->discipline_id,
            'teacher_id' => $activitTask->teacher_id,
        ])->afterCreating(function (Task $task) use ($activitTask) {
            $task->forceFill(['activit_task_id' => $activitTask->id])->save();
        });
    }
}
