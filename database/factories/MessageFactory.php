<?php

namespace Database\Factories;

use App\Models\Chat\Message;
use App\Models\Teacher\Classe;
use App\Models\Teacher\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Chat\Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'classe_id' => Classe::factory(),
            'sender_id' => Teacher::factory(),
            'sender_type' => Teacher::class,
            'receiver_id' => null,
            'receiver_type' => null,
            'content' => $this->faker->paragraph(),
            'is_read' => false,
            'read_at' => null,
        ];
    }
}
