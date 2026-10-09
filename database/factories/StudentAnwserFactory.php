<?php

namespace Database\Factories;

use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\Student;
use App\Models\Teacher\StudentAnwser;
use App\Models\Teacher\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher\StudentAnwser>
 */
class StudentAnwserFactory extends Factory
{
    protected $model = StudentAnwser::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'student_id' => Student::factory(),
            'activit_task_id' => null,
            'answer_text' => $this->faker->paragraph(),
            'answer_option_id' => null,
            'is_correct' => false,
            'order' => 1,
            'corrected' => false,
            'attempt_number' => 1,
        ];
    }

    /**
     * Vincula a resposta à tarefa e à atividade informadas.
     */
    public function forTask(Task $task, ?ActivitTask $activitTask = null): static
    {
        return $this->state(fn () => [
            'task_id' => $task->id,
            'activit_task_id' => $activitTask?->id ?? $task->activit_task_id,
        ]);
    }
}
