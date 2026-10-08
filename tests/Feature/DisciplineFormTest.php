<?php

use App\Models\Teacher\Discipline;
use App\Models\Teacher\Teacher;
use Livewire\Livewire;

it('cria disciplina pelo modal com form compartilhado', function () {
    $teacher = Teacher::factory()->create();

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.discipline.modals.modal-new-discipline')
        ->set('form.title', '  Matematica  ')
        ->call('store')
        ->assertHasNoErrors('form.title');

    expect(Discipline::where('teacher_id', $teacher->id)->first()->title)->toBe('Matematica');
});

it('rejeita título duplicado', function () {
    $teacher = Teacher::factory()->create();
    Discipline::factory()->for($teacher)->create(['title' => 'Matematica']);

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.discipline.modals.modal-new-discipline')
        ->set('form.title', 'matematica')
        ->call('store')
        ->assertHasErrors('form.title');

    expect(Discipline::count())->toBe(1);
});

it('rejeita título que já foi soft-deletado (índice único global)', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create(['title' => 'Matematica']);
    $discipline->delete();

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.discipline.modals.modal-new-discipline')
        ->set('form.title', 'Matematica')
        ->call('store')
        ->assertHasErrors('form.title');

    expect(Discipline::withTrashed()->count())->toBe(1);
});

it('edita disciplina via modal usando form', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create(['title' => 'Matematica']);

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.discipline.modals.modal-edit-discipline', ['discipline' => $discipline])
        ->set('form.title', 'Geometria')
        ->call('update')
        ->assertHasNoErrors('form.title');

    expect($discipline->fresh()->title)->toBe('Geometria');
});

it('aceita manter o mesmo título ao editar (ignora o próprio id)', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create(['title' => 'Matematica']);

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.discipline.modals.modal-edit-discipline', ['discipline' => $discipline])
        ->set('form.title', 'Matematica')
        ->call('update')
        ->assertHasNoErrors('form.title');
});

it('rejeita editar para o título de outra disciplina', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create(['title' => 'Matematica']);
    Discipline::factory()->for($teacher)->create(['title' => 'Geometria']);

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.discipline.modals.modal-edit-discipline', ['discipline' => $discipline])
        ->set('form.title', 'Geometria')
        ->call('update')
        ->assertHasErrors('form.title');
});

it('nega editar disciplina de outro professor', function () {
    $owner = Teacher::factory()->create();
    $other = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($owner)->create(['title' => 'Matematica']);

    Livewire::actingAs($other, 'teacher')
        ->test('teacher.discipline.modals.modal-edit-discipline', ['discipline' => $discipline])
        ->set('form.title', 'Novo título')
        ->call('update')
        ->assertStatus(403);
});

it('aplica soft delete na disciplina ao deletar pelo modal', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create(['title' => 'Matematica']);

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.discipline.modals.modal-delete-discipline', [
            'id' => $discipline->id,
            'title' => $discipline->title,
        ])
        ->call('open', $discipline->id)
        ->call('delete');

    expect(Discipline::find($discipline->id))->toBeNull()
        ->and(Discipline::withTrashed()->find($discipline->id)->trashed())->toBeTrue();
});