<?php

use App\Models\Teacher\Discipline;
use App\Models\Teacher\Teacher;
use Illuminate\Support\Str;

it('abre a página de conteúdo para o dono da disciplina', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();

    $this->actingAs($teacher, 'teacher')
        ->get(route('teacher.discipline.show', $discipline->id))
        ->assertOk();
});

it('responde 404 para disciplina inexistente', function () {
    $teacher = Teacher::factory()->create();

    $this->actingAs($teacher, 'teacher')
        ->get(route('teacher.discipline.show', (string) Str::uuid()))
        ->assertNotFound();
});

it('responde 403 para outro professor', function () {
    $owner = Teacher::factory()->create();
    $other = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($owner)->create();

    $this->actingAs($other, 'teacher')
        ->get(route('teacher.discipline.show', $discipline->id))
        ->assertForbidden();
});