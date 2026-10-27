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

it('aceita questão inteira menor que o valor da atividade', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '10')
        ->set('titleOfQuestion', 'Questao valendo dois')
        ->set('questionTypeSelected', 'text')
        ->set('valueOfQuestion', '2')
        ->call('addQuestion')
        ->assertHasNoErrors();

    expect($component->get('questions'))->toHaveCount(1);
});

it('persiste atividade avaliativa com valores numéricos inteiros', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component, $discipline, $module] = createTaskComponent($teacher);

    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '50')
        ->set('titleOfQuestion', 'Pergunta dissertativa valida')
        ->set('questionTypeSelected', 'text')
        ->set('valueOfQuestion', '25')
        ->call('addQuestion')
        ->assertHasNoErrors()
        ->set('moduleId', $module->id)
        ->call('save')
        ->assertHasNoErrors();

    $activit = ActivitTask::where('discipline_id', $discipline->id)->first();

    expect($activit)->not->toBeNull()
        ->and((bool) $activit->is_avaliative)->toBeTrue()
        ->and((float) $activit->total_value)->toBe(50.0);

    $task = Task::where('activit_task_id', $activit->id)->first();

    expect($task)->not->toBeNull()
        ->and((float) $task->value)->toBe(25.0)
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
        ->set('moduleId', $module->id)
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
        ->set('valueOfActivity', '50')
        ->set('titleOfQuestion', 'Questao que atinge o limite')
        ->set('questionTypeSelected', 'text')
        ->set('valueOfQuestion', '50')
        ->call('addQuestion')
        ->assertHasErrors('totalUsedValue');
});

it('bloqueia a segunda questão que excede o restante da atividade', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '50')
        ->set('titleOfQuestion', 'Primeira questao valendo trinta')
        ->set('questionTypeSelected', 'text')
        ->set('valueOfQuestion', '30')
        ->call('addQuestion')
        ->assertHasNoErrors();

    // Restam 20; a segunda questão com 30 excede o restante disponível.
    $component
        ->set('titleOfQuestion', 'Segunda questao valendo trinta')
        ->set('valueOfQuestion', '30')
        ->call('addQuestion')
        ->assertHasErrors('valueOfQuestion');

    expect($component->get('questions'))->toHaveCount(1);
});

it('aceita a segunda questão que cabe exatamente no restante', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '50')
        ->set('titleOfQuestion', 'Primeira questao valendo trinta')
        ->set('questionTypeSelected', 'text')
        ->set('valueOfQuestion', '30')
        ->call('addQuestion')
        ->assertHasNoErrors();

    $component
        ->set('titleOfQuestion', 'Segunda questao valendo vinte')
        ->set('valueOfQuestion', '20')
        ->call('addQuestion')
        ->assertHasErrors('totalUsedValue');

    expect($component->get('questions'))->toHaveCount(2);
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

    // Estado forjado contornando o limite do formulário: a guarda do save()
    // precisa rejeitar a soma acima do total da atividade.
    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '50')
        ->set('moduleId', $module->id)
        ->set('questions', [
            [
                'order' => 1,
                'title' => 'Primeira questao valendo trinta',
                'type' => 'text',
                'value_of_question' => 30,
                'options' => null,
            ],
            [
                'order' => 2,
                'title' => 'Segunda questao valendo trinta',
                'type' => 'text',
                'value_of_question' => 30,
                'options' => null,
            ],
        ])
        ->set('questionNoteValues', [30.0, 30.0]);

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
        ->call('setCorrectOption', 0)
        ->set('titleOfQuestion', 'Questao de multipla escolha')
        ->call('addQuestion')
        ->assertHasNoErrors()
        ->set('moduleId', $module->id)
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

    $component->set('moduleId', $foreignModule->id)
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
        ->set('moduleId', $module->id)
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
        ->call('setCorrectOption', 1)
        ->set('titleOfQuestion', 'Questao com duas opcoes')
        ->call('addQuestion')
        ->assertHasNoErrors();

    $questions = $component->get('questions');

    expect($questions)->toHaveCount(1)
        ->and($questions[0]['options'])->toHaveCount(2);
});

it('bloqueia criar questão de múltipla escolha sem alternativa correta', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $component
        ->set('questionTypeSelected', 'radio')
        ->set('newOptionText', 'Alternativa A')
        ->call('addOptionRadio')
        ->set('newOptionText', 'Alternativa B')
        ->call('addOptionRadio')
        ->set('titleOfQuestion', 'Questao sem alternativa correta')
        ->call('addQuestion')
        ->assertHasErrors('is_correct_alternative');

    expect($component->get('questions'))->toHaveCount(0);
});

it('permite criar questão de múltipla escolha após marcar a correta', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $component
        ->set('questionTypeSelected', 'radio')
        ->set('newOptionText', 'Alternativa A')
        ->call('addOptionRadio')
        ->set('newOptionText', 'Alternativa B')
        ->call('addOptionRadio')
        ->set('titleOfQuestion', 'Questao com alternativa correta')
        ->call('addQuestion')
        ->assertHasErrors('is_correct_alternative')
        ->call('setCorrectOption', 1)
        ->call('addQuestion')
        ->assertHasNoErrors();

    $questions = $component->get('questions');

    expect($questions)->toHaveCount(1);

    $correct = collect($questions[0]['options'])->where('is_correct', true);

    expect($correct)->toHaveCount(1)
        ->and($correct->first()['text'])->toBe('Alternativa B');
});

it('não exige alternativa correta para questões dissertativas', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $component
        ->set('questionTypeSelected', 'text')
        ->set('titleOfQuestion', 'Questao dissertativa valida')
        ->call('addQuestion')
        ->assertHasNoErrors();

    expect($component->get('questions'))->toHaveCount(1);
});

it('remove a opção correta e volta a exigir nova seleção', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $component
        ->set('questionTypeSelected', 'radio')
        ->set('newOptionText', 'Alternativa A')
        ->call('addOptionRadio')
        ->set('newOptionText', 'Alternativa B')
        ->call('addOptionRadio')
        ->set('newOptionText', 'Alternativa C')
        ->call('addOptionRadio')
        ->call('setCorrectOption', 1)
        ->call('removeOption', 1)
        ->set('titleOfQuestion', 'Questao apos remover correta')
        ->call('addQuestion')
        ->assertHasErrors('is_correct_alternative');

    expect($component->get('questions'))->toHaveCount(0);
});

it('bloqueia salvar atividade com data final anterior à inicial', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component, $discipline, $module] = createTaskComponent($teacher);

    $component
        ->set('titleOfQuestion', 'Questao com datas invertidas')
        ->set('questionTypeSelected', 'text')
        ->call('addQuestion')
        ->set('moduleId', $module->id)
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
        ->set('moduleId', $module->id)
        ->set('startDate', '2026-10-01')
        ->set('endDate', '2026-10-10')
        ->call('save')
        ->assertHasNoErrors();

    $activit = ActivitTask::where('discipline_id', $discipline->id)->first();

    expect($activit->start->format('Y-m-d H:i:s'))->toBe('2026-10-01 00:00:00')
        ->and($activit->end->format('Y-m-d H:i:s'))->toBe('2026-10-10 23:59:59');
});

it('sinaliza nota da atividade acima de 100', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '2000000000000')
        ->assertHasErrors('valueOfActivity');
});

it('bloqueia questão com nota acima de 100', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '100')
        ->set('titleOfQuestion', 'Questao com nota exagerada')
        ->set('questionTypeSelected', 'text')
        ->set('valueOfQuestion', '150')
        ->call('addQuestion')
        ->assertHasErrors('valueOfQuestion');

    expect($component->get('questions'))->toHaveCount(0);
});

it('bloqueia questão com nota maior que a da atividade', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '10')
        ->set('titleOfQuestion', 'Questao maior que a atividade')
        ->set('questionTypeSelected', 'text')
        ->set('valueOfQuestion', '11')
        ->call('addQuestion')
        ->assertHasErrors('valueOfQuestion');

    expect($component->get('questions'))->toHaveCount(0);
});

it('bloqueia criar questão avaliativa sem informar a nota', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '50')
        ->set('titleOfQuestion', 'Questao sem nota informada')
        ->set('questionTypeSelected', 'text')
        ->call('addQuestion')
        ->assertHasErrors('valueOfQuestion');

    expect($component->get('questions'))->toHaveCount(0);
});

it('bloqueia criar questão quando a nota é esvaziada pela máscara', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    // A máscara deixa o campo vazio (não injeta "0"); mesmo com a flag marcada
    // pela interação, o required barra a criação.
    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '50')
        ->set('titleOfQuestion', 'Questao com nota esvaziada')
        ->set('questionTypeSelected', 'text')
        ->set('valueOfQuestion', '')
        ->call('addQuestion')
        ->assertHasErrors('valueOfQuestion');

    expect($component->get('questions'))->toHaveCount(0);
});

it('permite criar questão com nota 0 quando informada explicitamente', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '50')
        ->set('titleOfQuestion', 'Questao valendo zero mesmo')
        ->set('questionTypeSelected', 'text')
        ->set('valueOfQuestion', '0')
        ->call('addQuestion')
        ->assertHasNoErrors();

    expect($component->get('questions'))->toHaveCount(1);
});

it('bloqueia edição de questão com nota acima de 100', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '50')
        ->set('titleOfQuestion', 'Questao inicial valida')
        ->set('questionTypeSelected', 'text')
        ->set('valueOfQuestion', '20')
        ->call('addQuestion')
        ->assertHasNoErrors();

    $component
        ->call('startEditingQuestion', 0)
        ->set('editingQuestionValue', '2000000000000')
        ->call('saveQuestionEdit')
        ->assertHasErrors('editingQuestionValue');

    expect($component->get('questions.0.value_of_question'))->toBe('20');
});

it('ao editar, considera o restante descontando a própria questão', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component] = createTaskComponent($teacher);

    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '100')
        ->set('titleOfQuestion', 'Primeira questao valendo sessenta')
        ->set('questionTypeSelected', 'text')
        ->set('valueOfQuestion', '60')
        ->call('addQuestion')
        ->assertHasNoErrors();

    $component
        ->set('titleOfQuestion', 'Segunda questao valendo trinta')
        ->set('valueOfQuestion', '30')
        ->call('addQuestion');

    // Restante para a primeira questão = 100 − 30 = 70. Editar para 80 excede.
    $component
        ->call('startEditingQuestion', 0)
        ->set('editingQuestionValue', '80')
        ->call('saveQuestionEdit')
        ->assertHasErrors('editingQuestionValue');

    expect($component->get('questions.0.value_of_question'))->toBe('60');

    // Editar para 70 (o próprio valor é descontado do cálculo) é aceito.
    $component
        ->call('startEditingQuestion', 0)
        ->set('editingQuestionValue', '70')
        ->call('saveQuestionEdit')
        ->assertHasNoErrors('editingQuestionValue');

    expect($component->get('questions.0.value_of_question'))->toBe('70');
});

it('não salva atividade avaliativa com nota forjada acima de 100', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component, $discipline, $module] = createTaskComponent($teacher);

    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '100')
        ->set('questions', [[
            'order' => 1,
            'title' => 'Questao com nota forjada',
            'type' => 'text',
            'value_of_question' => '2000000000000',
            'options' => null,
        ]])
        ->set('moduleId', $module->id)
        ->call('save')
        ->assertHasErrors('questions.0.value_of_question');

    expect(ActivitTask::where('discipline_id', $discipline->id)->exists())->toBeFalse();
});

it('aceita a nota máxima 100 na atividade', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component, $discipline, $module] = createTaskComponent($teacher);

    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '100')
        ->set('titleOfQuestion', 'Questao valendo o maximo')
        ->set('questionTypeSelected', 'text')
        ->set('valueOfQuestion', '90')
        ->call('addQuestion')
        ->assertHasNoErrors()
        ->set('moduleId', $module->id)
        ->call('save')
        ->assertHasNoErrors();

    $activit = ActivitTask::where('discipline_id', $discipline->id)->first();

    expect($activit)->not->toBeNull()
        ->and((float) $activit->total_value)->toBe(100.0);
});

it('persiste atividade com múltiplas questões de tipos diferentes', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');
    [$component, $discipline, $module] = createTaskComponent($teacher);

    $component
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '100')
        ->set('questionTypeSelected', 'radio')
        ->set('newOptionText', 'Alternativa A')
        ->call('addOptionRadio')
        ->set('newOptionText', 'Alternativa B')
        ->call('addOptionRadio')
        ->call('setCorrectOption', 1)
        ->set('titleOfQuestion', 'Questão de múltipla escolha')
        ->set('valueOfQuestion', '30')
        ->call('addQuestion')
        ->assertHasNoErrors();

    $component
        ->set('questionTypeSelected', 'text')
        ->set('titleOfQuestion', 'Questão dissertativa')
        ->set('valueOfQuestion', '50')
        ->call('addQuestion')
        ->assertHasNoErrors()
        ->set('moduleId', $module->id)
        ->call('save')
        ->assertHasNoErrors();

    $activit = ActivitTask::where('discipline_id', $discipline->id)->first();
    $tasks = Task::where('activit_task_id', $activit->id)->orderBy('order')->get();

    expect($tasks)->toHaveCount(2)
        ->and($tasks[0]->type)->toBe('multiple_choice')
        ->and($tasks[0]->question)->toBe('Questão de múltipla escolha')
        ->and((float) $tasks[0]->value)->toBe(30.0)
        ->and($tasks[1]->type)->toBe('dissertative')
        ->and($tasks[1]->question)->toBe('Questão dissertativa')
        ->and((float) $tasks[1]->value)->toBe(50.0);

    $options = TaskOptions::where('task_id', $tasks[0]->id)->orderBy('order')->get();

    expect($options)->toHaveCount(2)
        ->and($options[0]->text)->toBe('Alternativa A')
        ->and((bool) $options[0]->correct)->toBeFalse()
        ->and($options[1]->text)->toBe('Alternativa B')
        ->and((bool) $options[1]->correct)->toBeTrue();
});
