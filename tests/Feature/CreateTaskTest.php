<?php

use App\Models\Module;
use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Task;
use App\Models\Teacher\TaskOptions;
use App\Models\Teacher\Teacher;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
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

it('mantém o total puro: avaliar execedeValue não muta estado nem gera erro', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '5,00')
        ->set('questionNoteValues', [5.0]);

    $instance = $component->instance();

    expect($instance->totalValueExceded)->toBeFalse()
        ->and($instance->execedeValue())->toBeTrue()
        ->and($instance->totalValueExceded)->toBeFalse();
});

it('sinaliza quando a soma das questões atinge o valor da atividade', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '5,00')
        ->set('titleOfQuestion', 'Questao que atinge o limite')
        ->set('questionTypeSelected', 'text')
        ->set('valueOfQuestion', '5,00')
        ->call('addQuestion')
        ->assertHasErrors('totalUsedValue');
});

it('sinaliza quando a soma das questões ultrapassa o valor da atividade', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '5,00')
        ->set('titleOfQuestion', 'Primeira questao valendo tres')
        ->set('questionTypeSelected', 'text')
        ->set('valueOfQuestion', '3,00')
        ->call('addQuestion')
        ->assertHasNoErrors();

    $component
        ->set('titleOfQuestion', 'Segunda questao valendo tres')
        ->set('valueOfQuestion', '3,00')
        ->call('addQuestion')
        ->assertHasErrors('totalUsedValueExedido');
});

it('revalida o total quando o valor da atividade muda', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '10,00')
        ->set('questionNoteValues', [6.0])
        ->set('valueOfActivity', '5,00')
        ->assertHasErrors('totalUsedValueExedido');
});

it('não salva atividade avaliativa com soma de questões acima do total', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component, $discipline, $module] = createTaskComponent($teacher);

    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '5,00')
        ->set('selectedLessonId', $module->id)
        ->set('titleOfQuestion', 'Primeira questao valendo tres')
        ->set('questionTypeSelected', 'text')
        ->set('valueOfQuestion', '3,00')
        ->call('addQuestion');

    $component
        ->set('titleOfQuestion', 'Segunda questao valendo tres')
        ->set('valueOfQuestion', '3,00')
        ->call('addQuestion');

    $component->call('save')->assertHasErrors('totalUsedValueExedido');

    expect(ActivitTask::where('discipline_id', $discipline->id)->exists())->toBeFalse();
});

it('grava as opções de múltipla escolha com ordem incremental', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component, $discipline, $module] = createTaskComponent($teacher);

    $component
        ->set('questionTypeSelected', 'radio')
        ->set('newOptionText', 'Alternativa Alfa')
        ->call('addOptionRadio')
        ->set('newOptionText', 'Alternativa Beta')
        ->call('addOptionRadio')
        ->set('newOptionText', 'Alternativa Gama')
        ->call('addOptionRadio')
        ->set('titleOfQuestion', 'Questao de multipla escolha')
        ->call('addQuestion')
        ->assertHasNoErrors()
        ->call('updateCorrectOption', 0, 0)
        ->set('selectedLessonId', $module->id)
        ->call('save')
        ->assertHasNoErrors();

    $activit = ActivitTask::where('discipline_id', $discipline->id)->first();
    $task = Task::where('activit_task_id', $activit->id)->first();

    $orders = TaskOptions::where('task_id', $task->id)->orderBy('order')->pluck('order')->all();

    expect($orders)->toBe([1, 2, 3]);
});

it('bloqueia a alteração do disciplineId pelo cliente (Locked)', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $other = Discipline::factory()->create();

    $component->set('disciplineId', $other->id);
})->throws(CannotUpdateLockedPropertyException::class);

it('nega salvar atividade vinculada a módulo de outra disciplina/professor', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $otherTeacher = Teacher::factory()->create();
    $otherDiscipline = Discipline::factory()->for($otherTeacher)->create();
    $foreignModule = Module::factory()->create(['discipline_id' => $otherDiscipline->id]);

    $component->set('questions', [[
        'order' => 1,
        'title' => 'Questao valida para o teste',
        'type' => 'text',
        'value_of_question' => null,
        'options' => null,
    ]]);

    $component->set('selectedLessonId', $foreignModule->id)
        ->call('save')
        ->assertStatus(404);

    expect(ActivitTask::where('module_id', $foreignModule->id)->exists())->toBeFalse();
});

it('não vaza detalhes do banco ao falhar a persistência', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component, $discipline, $module] = createTaskComponent($teacher);

    // Força um erro de persistência (texto de opção nulo viola NOT NULL).
    $component
        ->set('questions', [[
            'order' => 1,
            'title' => 'Questao com opcao invalida',
            'type' => 'radio',
            'value_of_question' => null,
            'options' => [
                ['text' => null, 'is_correct' => true, 'order' => 0],
            ],
        ]])
        ->set('selectedLessonId', $module->id)
        ->call('save')
        ->assertHasErrors('save');

    expect($component->errors()->get('save')[0])
        ->toBe('Não foi possível salvar a atividade. Tente novamente.')
        ->and(ActivitTask::where('discipline_id', $discipline->id)->exists())->toBeFalse();
});

it('não adiciona questão de múltipla escolha sem opções suficientes', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $component
        ->set('questionTypeSelected', 'radio')
        ->set('newOptionText', 'Unica alternativa')
        ->call('addOptionRadio')
        ->set('titleOfQuestion', 'Questao com uma unica opcao')
        ->call('addQuestion')
        ->assertHasErrors('options');

    expect($component->get('questions'))->toHaveCount(0);
});

it('aceita questão de múltipla escolha com exatamente duas opções', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $component
        ->set('questionTypeSelected', 'radio')
        ->set('newOptionText', 'Alternativa A')
        ->call('addOptionRadio')
        ->set('newOptionText', 'Alternativa B')
        ->call('addOptionRadio')
        ->set('titleOfQuestion', 'Questao com duas opcoes')
        ->call('addQuestion')
        ->assertHasNoErrors();

    $questions = $component->get('questions');

    expect($questions)->toHaveCount(1)
        ->and($questions[0]['options'])->toHaveCount(2);
});

it('bloqueia salvar atividade com data final anterior à inicial', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component, $discipline, $module] = createTaskComponent($teacher);

    $component
        ->set('titleOfQuestion', 'Questao com datas invertidas')
        ->set('questionTypeSelected', 'text')
        ->call('addQuestion')
        ->set('selectedLessonId', $module->id)
        ->set('startDate', '2026-10-10')
        ->set('endDate', '2026-10-01')
        ->call('save')
        ->assertHasErrors('endDate');

    expect(ActivitTask::where('discipline_id', $discipline->id)->exists())->toBeFalse();
});

it('grava a janela da atividade cobrindo todo o dia final', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component, $discipline, $module] = createTaskComponent($teacher);

    $component
        ->set('titleOfQuestion', 'Questao com janela valida')
        ->set('questionTypeSelected', 'text')
        ->call('addQuestion')
        ->set('selectedLessonId', $module->id)
        ->set('startDate', '2026-10-01')
        ->set('endDate', '2026-10-10')
        ->call('save')
        ->assertHasNoErrors();

    $activit = ActivitTask::where('discipline_id', $discipline->id)->first();

    expect($activit->start->format('Y-m-d H:i:s'))->toBe('2026-10-01 00:00:00')
        ->and($activit->end->format('Y-m-d H:i:s'))->toBe('2026-10-10 23:59:59');
});
