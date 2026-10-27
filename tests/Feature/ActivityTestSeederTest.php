<?php

use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\Task;
use Database\Seeders\ActivityTestSeeder;

it('cria uma atividade avaliativa com múltipla escolha e dissertativa', function () {
    $this->seed(ActivityTestSeeder::class);

    $activity = ActivitTask::query()->firstOrFail();

    expect($activity->is_avaliative)->toBeTrue()
        ->and((float) $activity->total_value)->toBe(100.0);

    $tasks = $activity->tasks()->ordered()->get();

    expect($tasks)->toHaveCount(2);

    $multipleChoice = $tasks->firstWhere('type', 'multiple_choice');
    $dissertative = $tasks->firstWhere('type', 'dissertative');

    expect($multipleChoice)->not->toBeNull()
        ->and($dissertative)->not->toBeNull()
        ->and($multipleChoice->options)->toHaveCount(4)
        ->and($multipleChoice->options->where('correct', true))->toHaveCount(1);

    expect((float) $multipleChoice->value + (float) $dissertative->value)->toBe(100.0);
});
