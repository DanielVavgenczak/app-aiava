<?php

namespace Database\Factories;

use App\Models\Teacher\Discipline;
use App\Models\Teacher\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher\Discipline>
 */
class DisciplineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'teacher_id' => Teacher::factory(),
            'title' => ucfirst(Str::lower($this->faker->unique()->words(3, true))),
        ];
    }
}