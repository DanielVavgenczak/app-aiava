<?php

namespace Database\Factories;

use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\ActivityAccess;
use App\Models\Teacher\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher\ActivityAccess>
 */
class ActivityAccessFactory extends Factory
{
    protected $model = ActivityAccess::class;

    public function definition(): array
    {
        return [
            'activit_task_id' => ActivitTask::factory(),
            'student_id' => Student::factory(),
            'first_accessed_at' => now(),
        ];
    }

    public function forActivity(ActivitTask $activitTask, Student $student): static
    {
        return $this->state(fn () => [
            'activit_task_id' => $activitTask->id,
            'student_id' => $student->id,
        ]);
    }
}
