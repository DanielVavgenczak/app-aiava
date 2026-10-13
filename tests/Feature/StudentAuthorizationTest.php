<?php

use App\Models\Teacher\Classe;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Student;
use App\Models\Teacher\Teacher;
use Livewire\Livewire;

it('impede cadastrar aluno em turma de outro professor', function () {
    $owner = Teacher::factory()->create();
    $other = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($owner)->create();
    $classe = Classe::factory()->create([
        'teacher_id' => $owner->id,
        'discipline_id' => $discipline->id,
    ]);

    Livewire::actingAs($other, 'teacher')
        ->test('teacher.classe.new-student-modal')
        ->dispatch('teacher::new-student', classe_id: $classe->id, discipline_id: $discipline->id)
        ->set('students', [[
            'name' => 'Aluno Invasor',
            'email' => 'invasor@example.com',
            'phone' => '11999990000',
        ]])
        ->call('storeStudent')
        ->assertNotFound();

    expect(Student::where('email', 'invasor@example.com')->exists())->toBeFalse();
});

it('permite cadastrar aluno na propria turma', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $classe = Classe::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.classe.new-student-modal')
        ->dispatch('teacher::new-student', classe_id: $classe->id, discipline_id: $discipline->id)
        ->set('students', [[
            'name' => 'Aluno Novo',
            'email' => 'novo@example.com',
            'phone' => '11988887777',
        ]])
        ->call('storeStudent')
        ->assertSuccessful();

    expect(Student::where('email', 'novo@example.com')->exists())->toBeTrue();
});

it('impede editar aluno de outro professor', function () {
    $owner = Teacher::factory()->create();
    $other = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($owner)->create();
    $classe = Classe::factory()->create([
        'teacher_id' => $owner->id,
        'discipline_id' => $discipline->id,
    ]);
    $student = Student::factory()->create([
        'teacher_id' => $owner->id,
        'classe_id' => $classe->id,
        'discipline_id' => $discipline->id,
        'full_name' => 'Aluno Original',
    ]);

    Livewire::actingAs($other, 'teacher')
        ->test('teacher.classe.edit-student-modal', ['student' => $student])
        ->set('studentName', 'Aluno Invadido')
        ->set('studentEmail', 'invadido@example.com')
        ->call('updateStudent')
        ->assertNotFound();

    expect($student->fresh()->full_name)->toBe('Aluno Original');
});

it('impede deletar aluno de outro professor', function () {
    $owner = Teacher::factory()->create();
    $other = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($owner)->create();
    $classe = Classe::factory()->create([
        'teacher_id' => $owner->id,
        'discipline_id' => $discipline->id,
    ]);
    $student = Student::factory()->create([
        'teacher_id' => $owner->id,
        'classe_id' => $classe->id,
        'discipline_id' => $discipline->id,
    ]);

    Livewire::actingAs($other, 'teacher')
        ->test('teacher.classe.delete-student-modal', ['student' => $student])
        ->call('delete')
        ->assertNotFound();

    expect(Student::find($student->id))->not->toBeNull();
});
