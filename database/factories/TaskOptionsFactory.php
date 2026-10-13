<?php

namespace Database\Factories;

use App\Models\Teacher\Task;
use App\Models\Teacher\TaskOptions;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher\TaskOptions>
 */
class TaskOptionsFactory extends Factory
{
    protected $model = TaskOptions::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'text' => ucfirst($this->faker->words(3, true)),
            'correct' => false,
            'order' => 0,
        ];
    }

    public function correct(): static
    {
        return $this->state(fn () => ['correct' => true]);
    }
}
