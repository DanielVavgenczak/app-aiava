<?php

use App\Models\Teacher\Discipline;
use App\Models\Teacher\Teacher;
use Illuminate\Support\Facades\Gate;

it('permite que o professor gerencie a própria disciplina', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();

    $gate = Gate::forUser($teacher);

    expect($gate->allows('viewAny', Discipline::class))->toBeTrue()
        ->and($gate->allows('create', Discipline::class))->toBeTrue()
        ->and($gate->allows('view', $discipline))->toBeTrue()
        ->and($gate->allows('update', $discipline))->toBeTrue()
        ->and($gate->allows('delete', $discipline))->toBeTrue();
});

it('nega acesso a outro professor', function () {
    $owner = Teacher::factory()->create();
    $other = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($owner)->create();

    $gate = Gate::forUser($other);

    expect($gate->allows('view', $discipline))->toBeFalse()
        ->and($gate->allows('update', $discipline))->toBeFalse()
        ->and($gate->allows('delete', $discipline))->toBeFalse();
});

it('nega acesso para visitantes não autenticados', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();

    $gate = Gate::forUser(null);

    expect($gate->allows('view', $discipline))->toBeFalse()
        ->and($gate->allows('create', Discipline::class))->toBeFalse();
});