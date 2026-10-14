<?php

use App\Models\Teacher\Classe;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Student;
use App\Models\Teacher\Teacher;
use Illuminate\Support\Facades\Hash;
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

it('exibe a senha temporária do aluno ao professor', function () {
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
        'temporary_password' => 'Ab1!',
        'must_change_password' => true,
    ]);

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.classe.student-detail')
        ->dispatch('teacher::view-student', id: $student->id)
        ->assertSet('temporaryPassword', 'Ab1!')
        ->assertSee('Ab1!');
});

it('gera nova senha com até 6 caracteres incluindo caractere especial', function () {
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
        'temporary_password' => null,
        'must_change_password' => false,
    ]);

    $component = Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.classe.student-detail')
        ->dispatch('teacher::view-student', id: $student->id)
        ->call('regeneratePassword', $student->id)
        ->assertSet('open', true);

    $password = $component->get('temporaryPassword');

    expect($password)->toBeString()
        ->and(strlen($password))->toBeLessThanOrEqual(6)
        ->and(strpbrk($password, '!@#$%&*'))->not->toBeFalse();

    $student->refresh();

    expect($student->temporary_password)->toBe($password)
        ->and($student->must_change_password)->toBeTrue()
        ->and(Hash::check($password, $student->password))->toBeTrue();
});

it('nega gerar senha de aluno de outra turma', function () {
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
        ->call('regeneratePassword', $ownStudent->id)
        ->assertNotFound();
});
