<?php

namespace Database\Factories;

use App\Models\Forum\ForumReplie;
use App\Models\Forum\ForumTopic;
use App\Models\Teacher\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Forum\ForumReplie>
 */
class ForumReplieFactory extends Factory
{
    protected $model = ForumReplie::class;

    public function definition(): array
    {
        return [
            'topic_id' => ForumTopic::factory(),
            'parent_id' => null,
            'depth' => 0,
            'content' => $this->faker->paragraph(),
            'author_id' => Teacher::factory(),
            'author_type' => Teacher::class,
        ];
    }
}
