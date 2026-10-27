<?php

use App\Models\Lesson;
use App\Models\Module;
use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Task;
use App\Models\Teacher\Teacher;
use Illuminate\Support\Facades\Storage;
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

it('impede deletar aula de outro professor', function () {
    $owner = Teacher::factory()->create();
    $other = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($owner)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);

    Livewire::actingAs($other, 'teacher')
        ->test('teacher.discipline.module.delete', ['module' => $module])
        ->call('deleteModule');

    expect(Module::whereKey($module->id)->exists())->toBeTrue();
});

it('deleta aula, blocos, arquivos e atividades vinculadas', function () {
    Storage::fake('private');

    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);

    Storage::disk('private')->put('videos/lessons/aula.mp4', 'conteudo');

    $lesson = Lesson::factory()->create([
        'module_id' => $module->id,
        'teacher_id' => $teacher->id,
        'path_file' => 'videos/lessons/aula.mp4',
    ]);

    $activit = ActivitTask::factory()->create([
        'module_id' => $module->id,
        'discipline_id' => $discipline->id,
        'teacher_id' => $teacher->id,
    ]);
    $task = Task::factory()->forActivitTask($activit)->create();

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.discipline.module.delete', ['module' => $module])
        ->call('deleteModule')
        ->assertRedirect(route('teacher.discipline.show', $discipline));

    expect(Module::whereKey($module->id)->exists())->toBeFalse()
        ->and(Lesson::whereKey($lesson->id)->exists())->toBeFalse()
        ->and(ActivitTask::whereKey($activit->id)->exists())->toBeFalse()
        ->and(Task::whereKey($task->id)->exists())->toBeFalse()
        ->and(Storage::disk('private')->exists('videos/lessons/aula.mp4'))->toBeFalse();
});
