<?php

namespace Database\Factories;

use App\Models\Forum\ForumTopic;
use App\Models\Teacher\Discipline;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Forum\ForumTopic>
 */
class ForumTopicFactory extends Factory
{
    protected $model = ForumTopic::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'discipline_id' => Discipline::factory(),
            'classe_id' => null,
            'owner_id' => null,
            'owner_type' => null,
        ];
    }
}
