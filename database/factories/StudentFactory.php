<?php

namespace Database\Factories;

use App\Models\Teacher\Classe;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Student;
use App\Models\Teacher\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher\Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'full_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->unique()->numerify('55119########'),
            'teacher_id' => Teacher::factory(),
            'classe_id' => Classe::factory(),
            'discipline_id' => Discipline::factory(),
            'password' => 'password',
            'status' => 'active',
            'temporary_password' => null,
            'must_change_password' => false,
            'password_changed_at' => now(),
        ];
    }
}
