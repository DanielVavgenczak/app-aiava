<?php

use App\Models\Teacher\Classe;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Student;
use App\Models\Teacher\Teacher;

it('responde 404 ao professor abrir o fórum de disciplina alheia', function () {
    $owner = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($owner)->create();
    $other = Teacher::factory()->create();

    $this->actingAs($other, 'teacher')
        ->get(route('teacher.forum.show', $discipline->id))
        ->assertNotFound();
});

it('responde 404 ao aluno abrir o fórum de disciplina alheia', function () {
    $teacher = Teacher::factory()->create();
    $ownDiscipline = Discipline::factory()->for($teacher)->create();
    $classe = Classe::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $ownDiscipline->id,
    ]);
    $student = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'classe_id' => $classe->id,
        'discipline_id' => $ownDiscipline->id,
    ]);

    $otherTeacher = Teacher::factory()->create();
    $otherDiscipline = Discipline::factory()->for($otherTeacher)->create();

    $this->actingAs($student, 'student')
        ->get(route('student.forum.show', $otherDiscipline->id))
        ->assertNotFound();
});
