<?php

use App\Models\Forum\ForumTopic;
use App\Models\Teacher\Classe;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Student;
use App\Models\Teacher\Teacher;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

it('bloqueia adulteracao do disciplineID no forum do professor', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $otherDiscipline = Discipline::factory()->for(Teacher::factory())->create();

    Livewire::actingAs($teacher, 'teacher')
        ->test('pages::teacher.forum.show', ['id' => $discipline->id])
        ->set('disciplineID', $otherDiscipline->id);
})->throws(CannotUpdateLockedPropertyException::class);

it('bloqueia adulteracao do disciplineID no forum do aluno', function () {
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

    Livewire::actingAs($student, 'student')
        ->test('pages::student.forum.show', ['id' => $discipline->id])
        ->set('disciplineID', $discipline->id);
})->throws(CannotUpdateLockedPropertyException::class);

it('lista apenas os topicos da disciplina carregada', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $ownTopic = ForumTopic::factory()->create([
        'discipline_id' => $discipline->id,
        'owner_type' => Teacher::class,
        'owner_id' => $teacher->id,
    ]);

    $otherTeacher = Teacher::factory()->create();
    $otherDiscipline = Discipline::factory()->for($otherTeacher)->create();
    $otherTopic = ForumTopic::factory()->create([
        'discipline_id' => $otherDiscipline->id,
        'owner_type' => Teacher::class,
        'owner_id' => $otherTeacher->id,
    ]);

    Livewire::actingAs($teacher, 'teacher')
        ->test('pages::teacher.forum.show', ['id' => $discipline->id])
        ->assertSee($ownTopic->title)
        ->assertDontSee($otherTopic->title);
});
