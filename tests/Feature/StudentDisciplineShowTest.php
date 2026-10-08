<?php

use App\Models\Teacher\Classe;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Student;
use App\Models\Teacher\Teacher;

function makeStudentFor(Teacher $teacher, Discipline $discipline): Student
{
    $classe = Classe::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);

    return Student::factory()->create([
        'teacher_id' => $teacher->id,
        'classe_id' => $classe->id,
        'discipline_id' => $discipline->id,
    ]);
}

it('abre a disciplina do próprio aluno', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $student = makeStudentFor($teacher, $discipline);

    $this->actingAs($student, 'student')
        ->get(route('student.lessons', $discipline->id))
        ->assertOk();
});

it('responde 404 para disciplina de outro aluno', function () {
    $teacher = Teacher::factory()->create();
    $ownDiscipline = Discipline::factory()->for($teacher)->create();
    $student = makeStudentFor($teacher, $ownDiscipline);

    $otherTeacher = Teacher::factory()->create();
    $otherDiscipline = Discipline::factory()->for($otherTeacher)->create();

    $this->actingAs($student, 'student')
        ->get(route('student.lessons', $otherDiscipline->id))
        ->assertNotFound();
});
