<?php

use App\Models\Teacher\Classe;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Teacher;
use Livewire\Livewire;

it('impede criar turma em disciplina de outro professor', function () {
    $owner = Teacher::factory()->create();
    $other = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($owner)->create();

    Livewire::actingAs($other, 'teacher')
        ->test('teacher.classe.modal-new-classe')
        ->set('disciplineId', $discipline->id)
        ->set('class_name', 'Turma invasora')
        ->call('storeClasse')
        ->assertNotFound();

    expect(Classe::where('discipline_id', $discipline->id)->count())->toBe(0);
});

it('permite criar turma na propria disciplina', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.classe.modal-new-classe')
        ->set('disciplineId', $discipline->id)
        ->set('class_name', 'Turma nova')
        ->call('storeClasse')
        ->assertSuccessful();

    expect(Classe::where('teacher_id', $teacher->id)->where('name', 'Turma nova')->exists())->toBeTrue();
});

it('impede editar turma de outro professor', function () {
    $owner = Teacher::factory()->create();
    $other = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($owner)->create();
    $classe = Classe::factory()->create([
        'teacher_id' => $owner->id,
        'discipline_id' => $discipline->id,
        'name' => 'Turma original',
    ]);

    Livewire::actingAs($other, 'teacher')
        ->test('teacher.classe.edit-classe', ['classe' => $classe])
        ->set('classe_name', 'Turma invadida')
        ->call('updateClasse')
        ->assertNotFound();

    expect($classe->fresh()->name)->toBe('Turma original');
});

it('impede mover turma para disciplina de outro professor', function () {
    $teacher = Teacher::factory()->create();
    $ownDiscipline = Discipline::factory()->for($teacher)->create();
    $classe = Classe::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $ownDiscipline->id,
        'name' => 'Turma original',
    ]);

    $other = Teacher::factory()->create();
    $otherDiscipline = Discipline::factory()->for($other)->create();

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.classe.edit-classe', ['classe' => $classe])
        ->set('classe_name', 'Turma alterada')
        ->set('disciplineId', $otherDiscipline->id)
        ->call('updateClasse')
        ->assertNotFound();

    expect($classe->fresh()->discipline_id)->toBe($ownDiscipline->id);
});

it('permite editar turma do proprio professor', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $classe = Classe::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'name' => 'Turma original',
    ]);

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.classe.edit-classe', ['classe' => $classe])
        ->set('classe_name', 'Turma editada')
        ->call('updateClasse')
        ->assertSuccessful();

    expect($classe->fresh()->name)->toBe('Turma editada');
});
