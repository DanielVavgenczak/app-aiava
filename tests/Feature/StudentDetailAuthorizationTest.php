<?php

use App\Models\Teacher\Classe;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Student;
use App\Models\Teacher\Teacher;
use Livewire\Livewire;

it('responde 404 ao professor abrir detalhes de aluno de outra turma', function () {
    $owner = Teacher::factory()->create();
    $ownDiscipline = Discipline::factory()->for($owner)->create();
    $ownClasse = Classe::factory()->create([
        'teacher_id' => $owner->id,
        'discipline_id' => $ownDiscipline->id,
    ]);
    $ownStudent = Student::factory()->create([
        'teacher_id' => $owner->id,
        'classe_id' => $ownClasse->id,
        'discipline_id' => $ownDiscipline->id,
    ]);

    $other = Teacher::factory()->create();

    Livewire::actingAs($other, 'teacher')
        ->test('teacher.classe.student-detail')
        ->dispatch('teacher::view-student', id: $ownStudent->id)
        ->assertNotFound();
});

it('permite professor abrir detalhes de aluno da sua turma', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $classe = Classe::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);
    $student = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'classe_id' => $classe->id,
        'discipline_id' => $discipline->id,
    ]);

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.classe.student-detail')
        ->dispatch('teacher::view-student', id: $student->id)
        ->assertSet('open', true)
        ->assertSet('student.id', $student->id)
        ->assertSuccessful();
});

it('renderiza apenas um modal de detalhes mesmo com vários alunos na turma', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $classe = Classe::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);

    Student::factory()->count(3)->create([
        'teacher_id' => $teacher->id,
        'classe_id' => $classe->id,
        'discipline_id' => $discipline->id,
    ]);

    $html = Livewire::actingAs($teacher, 'teacher')
        ->test('pages::teacher.classes.show', ['classe' => $classe])
        ->html();

    expect(substr_count($html, 'Informações detalhadas do aluno'))->toBe(1);
});

it('abre os detalhes somente do aluno solicitado', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $classe = Classe::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);

    $students = Student::factory()->count(3)->create([
        'teacher_id' => $teacher->id,
        'classe_id' => $classe->id,
        'discipline_id' => $discipline->id,
    ]);

    $target = $students->first();

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.classe.student-detail')
        ->dispatch('teacher::view-student', id: $target->id)
        ->assertSet('open', true)
        ->assertSet('student.id', $target->id);
});
