<?php

use App\Models\Chat\Message;
use App\Models\Forum\ForumReplie;
use App\Models\Forum\ForumTopic;
use App\Models\Teacher\Classe;
use App\Models\Teacher\Student;

it('cria as factories base da P3', function () {
    $classe = Classe::factory()->create();
    $student = Student::factory()->create();
    $topic = ForumTopic::factory()->create();
    $reply = ForumReplie::factory()->create();
    $message = Message::factory()->create();

    expect($classe->id)->not->toBeNull()
        ->and($student->id)->not->toBeNull()
        ->and($topic->id)->not->toBeNull()
        ->and($reply->id)->not->toBeNull()
        ->and($message->id)->not->toBeNull();
});
