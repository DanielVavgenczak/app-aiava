<?php

use App\Models\Module;
use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Task;
use App\Models\Teacher\Teacher;
use Livewire\Livewire;

function createTaskComponent(Teacher $teacher): array
{
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);

    $component = Livewire::test('pages::teacher.discipline.module.modals.create-task', [
        'lessons' => collect([$module]),
        'disciplineId' => $discipline->id,
    ]);

    return [$component, $discipline, $module];
}

it('não bloqueia questão decimal menor que o valor da atividade (regressão vírgula)', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '10,00')
        ->set('titleOfQuestion', 'Questao valendo dois e meio')
        ->set('questionTypeSelected', 'text')
        ->set('valueOfQuestion', '2,5')
        ->call('addQuestion')
        ->assertHasNoErrors();

    expect($component->get('questions'))->toHaveCount(1);
});

it('persiste atividade avaliativa com valores numéricos decimais', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component, $discipline, $module] = createTaskComponent($teacher);

    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '5,00')
        ->set('titleOfQuestion', 'Pergunta dissertativa valida')
        ->set('questionTypeSelected', 'text')
        ->set('valueOfQuestion', '2,5')
        ->call('addQuestion')
        ->assertHasNoErrors()
        ->set('selectedLessonId', $module->id)
        ->call('save')
        ->assertHasNoErrors();

    $activit = ActivitTask::where('discipline_id', $discipline->id)->first();

    expect($activit)->not->toBeNull()
        ->and((bool) $activit->is_avaliative)->toBeTrue()
        ->and((float) $activit->total_value)->toBe(5.0);

    $task = Task::where('activit_task_id', $activit->id)->first();

    expect($task)->not->toBeNull()
        ->and((float) $task->value)->toBe(2.5)
        ->and($task->type)->toBe('dissertative');
});

it('persiste atividade não avaliativa sem nota e vinculada à disciplina', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component, $discipline, $module] = createTaskComponent($teacher);

    $component
        ->set('isAvaliative', false)
        ->set('titleOfQuestion', 'Questao nao avaliativa')
        ->set('questionTypeSelected', 'text')
        ->call('addQuestion')
        ->assertHasNoErrors()
        ->set('selectedLessonId', $module->id)
        ->call('save')
        ->assertHasNoErrors();

    $activit = ActivitTask::where('discipline_id', $discipline->id)->first();

    expect($activit)->not->toBeNull()
        ->and((bool) $activit->is_avaliative)->toBeFalse()
        ->and($activit->total_value)->toBeNull();

    $task = Task::where('activit_task_id', $activit->id)->first();

    expect($task)->not->toBeNull()
        ->and($task->value)->toBeNull();
});
