<?php

use App\Models\Teacher\Classe;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Student;
use App\Models\Teacher\Teacher;
use Livewire\Livewire;

function makeClasseWithStudent(Teacher $teacher): array
{
    $discipline = Discipline::factory()->for($teacher)->create();
    $classe = Classe::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);
    $student = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'classe_id' => $classe->id,
        'discipline_id' => $discipline->id,
        'status' => 'active',
    ]);

    return [$classe, $student];
}

it('impede professor alternar o status de aluno de outra turma', function () {
    $teacher = Teacher::factory()->create();
    [$classe] = makeClasseWithStudent($teacher);

    $other = Teacher::factory()->create();
    [, $otherStudent] = makeClasseWithStudent($other);

    Livewire::actingAs($teacher, 'teacher')
        ->test('pages::teacher.classes.show', ['classe' => $classe])
        ->call('toggleStatus', $otherStudent->id)
        ->assertNotFound();

    expect($otherStudent->fresh()->status)->toBe('active');
});

it('permite professor alternar o status de aluno da sua turma', function () {
    $teacher = Teacher::factory()->create();
    [$classe, $student] = makeClasseWithStudent($teacher);

    Livewire::actingAs($teacher, 'teacher')
        ->test('pages::teacher.classes.show', ['classe' => $classe])
        ->call('toggleStatus', $student->id)
        ->assertSuccessful();

    expect($student->fresh()->status)->toBe('inactive');
});
