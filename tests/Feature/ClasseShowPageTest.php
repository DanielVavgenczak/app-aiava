<?php

use App\Models\Teacher\Classe;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Teacher;

it('abre a turma para o professor dono', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $classe = Classe::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);

    $this->actingAs($teacher, 'teacher')
        ->get(route('teacher.classes.show', $classe->id))
        ->assertOk();
});

it('nega a turma para outro professor', function () {
    $owner = Teacher::factory()->create();
    $other = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($owner)->create();
    $classe = Classe::factory()->create([
        'teacher_id' => $owner->id,
        'discipline_id' => $discipline->id,
    ]);

    $this->actingAs($other, 'teacher')
        ->get(route('teacher.classes.show', $classe->id))
        ->assertForbidden();
});
