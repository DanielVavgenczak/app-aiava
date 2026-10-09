<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Teacher\Discipline;
use App\Models\Module;
use App\Models\Teacher\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lesson>
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        $teacher = Teacher::factory()->create();
        $discipline = Discipline::factory()->for($teacher)->create();
        $module = Module::factory()->create([
            'discipline_id' => $discipline->id,
        ]);

        return [
            'id' => Str::uuid(),
            'original_name' => $this->faker->word(),
            'hash_name' => Str::random(10),
            'path_file' => null,
            'title' => $this->faker->sentence(2),
            'description' => $this->faker->text(),
            'module_id' => $module->id,
            'teacher_id' => $teacher->id,
        ];
    }
}
