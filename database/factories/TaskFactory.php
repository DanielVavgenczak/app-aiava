<?php

namespace Database\Factories;

use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\Task;
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
            'activit_task_id' => ActivitTask::factory(),
        ];
    }

    /**
     * Mantém a tarefa vinculada à atividade (que concentra dono/módulo/disciplina).
     */
    public function forActivitTask(ActivitTask $activitTask): static
    {
        return $this->state(fn () => [
            'activit_task_id' => $activitTask->id,
        ]);
    }
}
