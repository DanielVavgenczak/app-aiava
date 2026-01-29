<?php

namespace Database\Factories;

use App\Models\Discipline;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Module>
 */
class ModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Module::class;
    public function definition(): array
    {
        return [
            "title" => $this->faker->word(),
            "discipline_id" => null
        ];
    }
}
