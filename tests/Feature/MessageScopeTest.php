<?php

use App\Models\Chat\Message;
use App\Models\Teacher\Classe;
use App\Models\Teacher\Teacher;

it('agrupa betweenUsers mesmo combinado com outros filtros', function () {
    $teacherA = Teacher::factory()->create();
    $teacherB = Teacher::factory()->create();
    $classeA = Classe::factory()->create();
    $classeB = Classe::factory()->create();

    $private = fn (Classe $classe, Teacher $sender, Teacher $receiver) => Message::factory()->create([
        'classe_id' => $classe->id,
        'sender_id' => $sender->id,
        'sender_type' => Teacher::class,
        'receiver_id' => $receiver->id,
        'receiver_type' => Teacher::class,
    ]);

    $private($classeA, $teacherA, $teacherB);
    $private($classeA, $teacherB, $teacherA);
    $private($classeB, $teacherB, $teacherA);

    $result = Message::where('classe_id', $classeA->id)
        ->betweenUsers($teacherA->id, Teacher::class, $teacherB->id, Teacher::class)
        ->pluck('classe_id');

    expect($result)->toHaveCount(2)
        ->and($result->unique()->values()->all())->toBe([$classeA->id]);
});
