<?php

use App\Models\Module;
use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Teacher;
use Illuminate\Support\Str;
use Livewire\Livewire;

it('abre a página de conteúdo para o dono da disciplina', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();

    $this->actingAs($teacher, 'teacher')
        ->get(route('teacher.discipline.show', $discipline->id))
        ->assertOk();
});

it('renderiza o drawer show-task no BO da disciplina', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();

    $this->actingAs($teacher, 'teacher')
        ->get(route('teacher.discipline.show', $discipline->id))
        ->assertOk()
        ->assertSee('pages::teacher.discipline.module.task.show-task', false);
});

it('dispara task-show ao abrir uma atividade do BO e fecha no close-task-show', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);
    $activit = ActivitTask::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'module_id' => $module->id,
        'is_avaliative' => false,
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.show', ['id' => $discipline->id])
        ->call('openTaskShow', $activit->id)
        ->assertSet('openedTaskShow', true)
        ->assertDispatched('task-show', activit_id: $activit->id)
        ->dispatch('close-task-show')
        ->assertSet('openedTaskShow', false);
});

it('responde 404 para disciplina inexistente', function () {
    $teacher = Teacher::factory()->create();

    $this->actingAs($teacher, 'teacher')
        ->get(route('teacher.discipline.show', (string) Str::uuid()))
        ->assertNotFound();
});

it('responde 403 para outro professor', function () {
    $owner = Teacher::factory()->create();
    $other = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($owner)->create();

    $this->actingAs($other, 'teacher')
        ->get(route('teacher.discipline.show', $discipline->id))
        ->assertForbidden();
});