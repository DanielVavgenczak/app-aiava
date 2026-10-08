<?php

namespace Database\Factories;

use App\Models\Teacher\Classe;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher\Classe>
 */
class ClasseFactory extends Factory
{
    protected $model = Classe::class;

    public function definition(): array
    {
        return [
            'name' => 'Turma ' . ucfirst($this->faker->unique()->word()),
            'semester' => $this->faker->randomElement(['2026.1', '2026.2']),
            'description' => $this->faker->sentence(),
            'discipline_id' => Discipline::factory(),
            'teacher_id' => Teacher::factory(),
        ];
    }
}
