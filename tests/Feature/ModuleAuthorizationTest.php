<?php

use App\Models\Module;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Teacher;
use Livewire\Livewire;

it('impede criar aula em disciplina de outro professor', function () {
    $owner = Teacher::factory()->create();
    $other = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($owner)->create();

    Livewire::actingAs($other, 'teacher')
        ->test('teacher.discipline.module.create')
        ->dispatch('teacher::create-new-lesson', disciplineId: $discipline->id)
        ->set('title', 'Aula invasora')
        ->call('save')
        ->assertNotFound();

    expect(Module::where('discipline_id', $discipline->id)->count())->toBe(0);
});

it('permite criar aula na propria disciplina', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.discipline.module.create')
        ->dispatch('teacher::create-new-lesson', disciplineId: $discipline->id)
        ->set('title', 'Aula nova')
        ->call('save')
        ->assertSuccessful();

    expect(Module::where('discipline_id', $discipline->id)->where('title', 'Aula nova')->exists())->toBeTrue();
});

it('impede editar aula de outro professor', function () {
    $owner = Teacher::factory()->create();
    $other = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($owner)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id, 'title' => 'Original']);

    Livewire::actingAs($other, 'teacher')
        ->test('teacher.discipline.module.edit', ['module' => $module])
        ->set('title', 'Invadida')
        ->call('update')
        ->assertNotFound();

    expect($module->fresh()->title)->toBe('Original');
});

it('permite editar aula do proprio professor', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id, 'title' => 'Original']);

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.discipline.module.edit', ['module' => $module])
        ->set('title', 'Editada')
        ->call('update')
        ->assertSuccessful();

    expect($module->fresh()->title)->toBe('Editada');
});
