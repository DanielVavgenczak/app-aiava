<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher\ActivitTask>
 */
class ActivitTaskFactory extends Factory
{
    protected $model = ActivitTask::class;

    public function definition(): array
    {
        return [
            'title' => 'atividade ' . $this->faker->uuid(),
            'start' => null,
            'end' => null,
            'module_id' => Module::factory(),
            'discipline_id' => Discipline::factory(),
            'teacher_id' => Teacher::factory(),
            'is_avaliative' => false,
            'total_value' => null,
        ];
    }

    /**
     * Vincula a atividade a um módulo/disciplina/professor existentes,
     * mantendo a consistência necessária para os escopos de autorização.
     */
    public function forDiscipline(Discipline $discipline, Module $module, Teacher $teacher): static
    {
        return $this->state(fn () => [
            'discipline_id' => $discipline->id,
            'module_id' => $module->id,
            'teacher_id' => $teacher->id,
        ]);
    }
}
