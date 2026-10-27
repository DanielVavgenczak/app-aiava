<?php

use App\Models\Module;
use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\QuestionFeedback;
use App\Models\Teacher\Student;
use App\Models\Teacher\StudentAnwser;
use App\Models\Teacher\Task;
use App\Models\Teacher\TaskOptions;
use App\Models\Teacher\Teacher;
use Illuminate\Support\Str;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

function makeTaskActivity(Teacher $teacher): array
{
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);
    $activit = ActivitTask::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'module_id' => $module->id,
    ]);

    return [$discipline, $module, $activit];
}

function makeTaskStudent(Teacher $teacher, Discipline $discipline): Student
{
    return Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);
}

it('cria as factories de atividade, tarefa e resposta', function () {
    $teacher = Teacher::factory()->create();
    [, , $activit] = makeTaskActivity($teacher);
    $task = Task::factory()->forActivitTask($activit)->create();
    $answer = StudentAnwser::factory()->forTask($task, $activit)->create();

    expect($activit->id)->not->toBeNull()
        ->and($task->id)->not->toBeNull()
        ->and($answer->id)->not->toBeNull()
        ->and($task->activit_task_id)->toBe($activit->id);
});

it('abre o show-task apenas para o professor dono da atividade', function () {
    $teacher = Teacher::factory()->create();
    [, , $activit] = makeTaskActivity($teacher);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.show-task')
        ->dispatch('task-show', activit_id: $activit->id)
        ->assertSet('showTaskModule', true)
        ->assertSet('activitTask.id', $activit->id);
});

it('notifica o pai quando o drawer da atividade é fechado pelo MaryUI', function () {
    $teacher = Teacher::factory()->create();
    [, , $activit] = makeTaskActivity($teacher);

    $this->actingAs($teacher, 'teacher');

    // Fechar o drawer pelo overlay/ESC altera `showTaskModule` sem passar por
    // `cancelMakeTask`; o hook `updatedShowTaskModule` deve avisar o pai.
    Livewire::test('pages::teacher.discipline.module.task.show-task')
        ->dispatch('task-show', activit_id: $activit->id)
        ->set('showTaskModule', false)
        ->assertDispatched('close-task-show');

    // Fechar novamente explicitamente também notifica.
    Livewire::test('pages::teacher.discipline.module.task.show-task')
        ->dispatch('task-show', activit_id: $activit->id)
        ->call('cancelMakeTask')
        ->assertSet('showTaskModule', false)
        ->assertDispatched('close-task-show');
});

it('nega show-task para professor que não é dono da atividade', function () {
    $owner = Teacher::factory()->create();
    [, , $activit] = makeTaskActivity($owner);

    $other = Teacher::factory()->create();
    $this->actingAs($other, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.show-task')
        ->dispatch('task-show', activit_id: $activit->id)
        ->assertSet('showTaskModule', false)
        ->assertSet('activitTask', null);
});

it('show-task não quebra com id inexistente (open null-safe)', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.show-task')
        ->dispatch('task-show', activit_id: (string) Str::uuid())
        ->assertSet('showTaskModule', false)
        ->assertSet('activitTask', null)
        ->assertSet('taskId', '')
        ->assertStatus(200);
});

it('bloqueia a edição de questão/opção sem atividade carregada', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.show-task')
        ->call('saveQuestionEdit')
        ->call('saveOptionEdit')
        ->assertSet('editingQuestionIndex', null)
        ->assertSet('editingOptionIndex', null);
});

it('remove uma questão sem respostas e atualiza a listagem', function () {
    $teacher = Teacher::factory()->create();
    [, , $activit] = makeTaskActivity($teacher);
    $taskA = Task::factory()->forActivitTask($activit)->create(['order' => 1]);
    $taskB = Task::factory()->forActivitTask($activit)->create(['order' => 2]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.show-task')
        ->dispatch('task-show', activit_id: $activit->id)
        ->call('removeQuestion', 0)
        ->assertHasNoErrors()
        ->assertOk();

    expect(Task::whereKey($taskA->id)->exists())->toBeFalse()
        ->and(Task::whereKey($taskB->id)->exists())->toBeTrue();
});

it('não remove questão que já possui respostas de alunos', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeTaskActivity($teacher);
    $task = Task::factory()->forActivitTask($activit)->create(['order' => 1]);
    $student = makeTaskStudent($teacher, $discipline);
    $answer = StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.show-task')
        ->dispatch('task-show', activit_id: $activit->id)
        ->call('removeQuestion', 0);

    expect(Task::whereKey($task->id)->exists())->toBeTrue()
        ->and(StudentAnwser::whereKey($answer->id)->exists())->toBeTrue();
});

it('impede que o cliente manipule propriedades locked do show-task', function () {
    $teacher = Teacher::factory()->create();
    [, , $activit] = makeTaskActivity($teacher);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.show-task')
        ->dispatch('task-show', activit_id: $activit->id)
        ->set('taskId', 'trocada');
})->throws(CannotUpdateLockedPropertyException::class);

it('abre as respostas de tarefa para o professor dono', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeTaskActivity($teacher);
    $task = Task::factory()->forActivitTask($activit)->create();
    $student = makeTaskStudent($teacher, $discipline);
    $answer = StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.student-anwsers')
        ->dispatch('event-teacher-show-student-answer', task_id: $task->id)
        ->assertSet('show', true)
        ->assertSet('task_id', $task->id)
        ->assertSet('studentAnswer.0.id', $answer->id);
});

it('expõe o acionamento das entregas a partir do show-task', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeTaskActivity($teacher);
    $task = Task::factory()->forActivitTask($activit)->create([
        'type' => 'multiple_choice',
    ]);
    $student = makeTaskStudent($teacher, $discipline);
    StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
    ]);

    $this->actingAs($teacher, 'teacher');

    // O componente de correção precisa estar montado e o gatilho de entregas
    // precisa existir para o professor alcançar a correção pela UI.
    Livewire::test('pages::teacher.discipline.module.task.show-task')
        ->dispatch('task-show', activit_id: $activit->id)
        ->assertSee('event-teacher-show-student-answer', false)
        ->assertSeeLivewire('pages::teacher.discipline.module.task.student-anwsers');
});

it('não abre respostas para professor que não é dono da tarefa', function () {
    $owner = Teacher::factory()->create();
    [$discipline, , $activit] = makeTaskActivity($owner);
    $task = Task::factory()->forActivitTask($activit)->create();
    $student = makeTaskStudent($owner, $discipline);
    StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
    ]);

    $other = Teacher::factory()->create();
    $this->actingAs($other, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.student-anwsers')
        ->dispatch('event-teacher-show-student-answer', task_id: $task->id)
        ->assertSet('show', false)
        ->assertSet('task_id', '')
        ->assertSet('studentAnswer', null);
});

it('envia feedback derivando student_id da resposta no banco', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeTaskActivity($teacher);
    $task = Task::factory()->forActivitTask($activit)->create();
    $student = makeTaskStudent($teacher, $discipline);
    $answer = StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.student-anwsers')
        ->dispatch('event-teacher-show-student-answer', task_id: $task->id)
        ->set("feedbacks.{$answer->id}", 'Feedback muito bem detalhado!')
        ->call('sendFeedback', $answer->id)
        ->assertHasNoErrors();

    $feedback = QuestionFeedback::where('student_anwser_id', $answer->id)->first();

    expect($feedback)->not->toBeNull()
        ->and($feedback->student_id)->toBe($student->id)
        ->and($feedback->task_id)->toBe($task->id)
        ->and($answer->fresh()->corrected)->toBeTruthy();
});

it('não sobrescreve a nota de outra resposta da mesma tarefa ao corrigir', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeTaskActivity($teacher);
    $task = Task::factory()->forActivitTask($activit)->create();

    $studentA = makeTaskStudent($teacher, $discipline);
    $studentB = makeTaskStudent($teacher, $discipline);

    $answerA = StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $studentA->id,
    ]);
    $answerB = StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $studentB->id,
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.student-anwsers')
        ->dispatch('event-teacher-show-student-answer', task_id: $task->id)
        ->set("feedbacks.{$answerA->id}", 'Feedback para o aluno A!')
        ->set("grades.{$answerA->id}", '80')
        ->call('sendFeedback', $answerA->id)
        ->assertHasNoErrors();

    expect((float) $answerA->fresh()->grade)->toBe(80.0)
        ->and($answerA->fresh()->corrected)->toBeTrue()
        ->and($answerB->fresh()->grade)->toBeNull()
        ->and($answerB->fresh()->corrected)->toBeFalse();
});

it('exibe a alternativa escolhida pelo aluno em múltipla escolha', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeTaskActivity($teacher);
    $task = Task::factory()->forActivitTask($activit)->create(['type' => 'multiple_choice']);
    $option = TaskOptions::factory()->for($task)->create(['text' => 'Alternativa escolhida pelo aluno']);
    $student = makeTaskStudent($teacher, $discipline);
    StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
        'answer_text' => null,
        'answer_option_id' => $option->id,
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.student-anwsers')
        ->dispatch('event-teacher-show-student-answer', task_id: $task->id)
        ->assertSee('Alternativa escolhida pelo aluno');
});

it('exibe nota e feedback na aba de corrigidas', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeTaskActivity($teacher);
    $task = Task::factory()->forActivitTask($activit)->create();
    $student = makeTaskStudent($teacher, $discipline);
    $answer = StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
        'corrected' => true,
        'grade' => 85,
    ]);
    QuestionFeedback::create([
        'student_anwser_id' => $answer->id,
        'task_id' => $task->id,
        'student_id' => $student->id,
        'teacher_id' => $teacher->id,
        'feedback' => 'Feedback já registrado na aba corrigidas.',
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.student-anwsers')
        ->dispatch('event-teacher-show-student-answer', task_id: $task->id)
        ->assertSee('85')
        ->assertSee('Feedback já registrado na aba corrigidas.');
});

it('preserva nota digitada ao reabrir a mesma tarefa', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeTaskActivity($teacher);
    $task = Task::factory()->forActivitTask($activit)->create();
    $student = makeTaskStudent($teacher, $discipline);
    $answer = StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.student-anwsers')
        ->dispatch('event-teacher-show-student-answer', task_id: $task->id)
        ->set("grades.{$answer->id}", '70')
        ->dispatch('event-teacher-show-student-answer', task_id: $task->id)
        ->assertSet("grades.{$answer->id}", '70');
});

it('descarta nota digitada ao trocar de tarefa', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeTaskActivity($teacher);
    $taskA = Task::factory()->forActivitTask($activit)->create();
    $taskB = Task::factory()->forActivitTask($activit)->create();
    $student = makeTaskStudent($teacher, $discipline);
    $answerA = StudentAnwser::factory()->forTask($taskA, $activit)->create([
        'student_id' => $student->id,
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.student-anwsers')
        ->dispatch('event-teacher-show-student-answer', task_id: $taskA->id)
        ->set("grades.{$answerA->id}", '70')
        ->dispatch('event-teacher-show-student-answer', task_id: $taskB->id)
        ->assertSet('grades', []);
});

it('rejeita nota de correção acima do valor da questão', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeTaskActivity($teacher);
    $task = Task::factory()->forActivitTask($activit)->create(['value' => 10]);
    $student = makeTaskStudent($teacher, $discipline);
    $answer = StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.student-anwsers')
        ->dispatch('event-teacher-show-student-answer', task_id: $task->id)
        ->set("feedbacks.{$answer->id}", 'Feedback válido para o aluno')
        ->set("grades.{$answer->id}", '11')
        ->call('sendFeedback', $answer->id)
        ->assertHasErrors("grades.{$answer->id}");

    expect($answer->fresh()->corrected)->toBeFalse();
});

it('aceita nota de correção igual ao valor da questão', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeTaskActivity($teacher);
    $task = Task::factory()->forActivitTask($activit)->create(['value' => 10]);
    $student = makeTaskStudent($teacher, $discipline);
    $answer = StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.student-anwsers')
        ->dispatch('event-teacher-show-student-answer', task_id: $task->id)
        ->set("feedbacks.{$answer->id}", 'Feedback válido para o aluno')
        ->set("grades.{$answer->id}", '10')
        ->call('sendFeedback', $answer->id)
        ->assertHasNoErrors();

    expect((float) $answer->fresh()->grade)->toBe(10.0);
});

it('exibe o total somado das notas do aluno na atividade', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeTaskActivity($teacher);
    $activit->update(['is_avaliative' => true, 'total_value' => 100]);

    $taskA = Task::factory()->forActivitTask($activit)->create(['value' => 60]);
    $taskB = Task::factory()->forActivitTask($activit)->create(['value' => 40]);
    $student = makeTaskStudent($teacher, $discipline);

    StudentAnwser::factory()->forTask($taskA, $activit)->create([
        'student_id' => $student->id,
        'corrected' => true,
        'grade' => 50,
    ]);
    StudentAnwser::factory()->forTask($taskB, $activit)->create([
        'student_id' => $student->id,
        'corrected' => true,
        'grade' => 30,
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.student-anwsers')
        ->dispatch('event-teacher-show-student-answer', task_id: $taskA->id)
        ->assertSet('activityTotalValue', '100.00')
        ->assertSee('Total do aluno')
        ->assertSee('80');
});

it('painel de entregas mostra quantos alunos responderam', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeTaskActivity($teacher);
    $task = Task::factory()->forActivitTask($activit)->create();

    $alunoA = makeTaskStudent($teacher, $discipline);
    $alunoB = makeTaskStudent($teacher, $discipline);
    makeTaskStudent($teacher, $discipline); // aluno C ainda não respondeu

    StudentAnwser::factory()->forTask($task, $activit)->create(['student_id' => $alunoA->id]);
    StudentAnwser::factory()->forTask($task, $activit)->create(['student_id' => $alunoB->id]);

    $this->actingAs($teacher, 'teacher');

    $component = Livewire::test('pages::teacher.discipline.module.task.show-task')
        ->dispatch('task-show', activit_id: $activit->id);

    expect($component->instance()->answeredStudents)->toBe(2)
        ->and($component->instance()->totalStudents)->toBe(3);

    $component->assertSee('2/3 responderam');
});

it('rejeita feedback forjado (resposta de outra tarefa)', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeTaskActivity($teacher);
    $task = Task::factory()->forActivitTask($activit)->create();
    $student = makeTaskStudent($teacher, $discipline);
    StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
    ]);

    $otherTeacher = Teacher::factory()->create();
    [$otherDiscipline, , $otherActivit] = makeTaskActivity($otherTeacher);
    $otherTask = Task::factory()->forActivitTask($otherActivit)->create();
    $otherAnswer = StudentAnwser::factory()->forTask($otherTask, $otherActivit)->create([
        'student_id' => Student::factory()->create([
            'teacher_id' => $otherTeacher->id,
            'discipline_id' => $otherDiscipline->id,
        ])->id,
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.student-anwsers')
        ->dispatch('event-teacher-show-student-answer', task_id: $task->id)
        ->set("feedbacks.{$otherAnswer->id}", 'Feedback para resposta alheia')
        ->call('sendFeedback', $otherAnswer->id)
        ->assertHasErrors("feedbacks.{$otherAnswer->id}");

    expect(QuestionFeedback::where('student_anwser_id', $otherAnswer->id)->exists())
        ->toBeFalse()
        ->and($otherAnswer->fresh()->corrected)->toBeFalsy();
});

it('monta e permite deletar só a atividade do dono', function () {
    $teacher = Teacher::factory()->create();
    [, , $activit] = makeTaskActivity($teacher);

    $this->actingAs($teacher, 'teacher');

    $component = Livewire::test(
        'pages::teacher.discipline.module.task.delete-task',
        ['activittask' => $activit],
    );

    $activitId = $activit->id;

    $component->call('delete')
        ->assertDispatched('task-deleted');

    expect(ActivitTask::find($activitId))->toBeNull();
});

it('nega montagem do delete-task para professor de fora do escopo', function () {
    $owner = Teacher::factory()->create();
    [, , $activit] = makeTaskActivity($owner);

    $other = Teacher::factory()->create();
    $this->actingAs($other, 'teacher');

    expect(fn () => Livewire::mount(
        'pages::teacher.discipline.module.task.delete-task',
        ['activittask' => $activit],
    ))->toThrow(NotFoundHttpException::class);
});

it('abre a edição da atividade do dono com os dados preenchidos', function () {
    $teacher = Teacher::factory()->create();
    [, , $activit] = makeTaskActivity($teacher);
    $activit->update([
        'title' => 'Prova de História',
        'is_avaliative' => true,
        'total_value' => 100,
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.modals.edit-task')
        ->dispatch('open-edit-task', id: $activit->id)
        ->assertSet('openEdit', true)
        ->assertSet('title', 'Prova de História')
        ->assertSet('isAvaliative', true)
        ->assertSet('hasAnswers', false);
});

it('atualiza título, valor e datas da atividade sem respostas', function () {
    $teacher = Teacher::factory()->create();
    [, , $activit] = makeTaskActivity($teacher);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.modals.edit-task')
        ->dispatch('open-edit-task', id: $activit->id)
        ->set('title', 'Atividade renomeada')
        ->set('isAvaliative', true)
        ->set('valueOfActivity', '80')
        ->set('startDate', '2026-01-01')
        ->set('endDate', '2026-01-31')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('task-updated-success');

    $activit->refresh();

    expect($activit->title)->toBe('Atividade renomeada')
        ->and($activit->is_avaliative)->toBeTrue()
        ->and((float) $activit->total_value)->toBe(80.0)
        ->and($activit->start->format('Y-m-d'))->toBe('2026-01-01')
        ->and($activit->end->format('Y-m-d'))->toBe('2026-01-31');
});

it('não permite alterar estrutura avaliativa quando já existem respostas', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeTaskActivity($teacher);
    $activit->update(['is_avaliative' => true, 'total_value' => 100]);
    $task = Task::factory()->forActivitTask($activit)->create();
    $student = makeTaskStudent($teacher, $discipline);
    StudentAnwser::factory()->forTask($task, $activit)->create(['student_id' => $student->id]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.modals.edit-task')
        ->dispatch('open-edit-task', id: $activit->id)
        ->assertSet('hasAnswers', true)
        ->set('title', 'Novo título permitido')
        ->set('isAvaliative', false)
        ->set('valueOfActivity', '')
        ->call('save')
        ->assertHasNoErrors();

    $activit->refresh();

    expect($activit->title)->toBe('Novo título permitido')
        ->and($activit->is_avaliative)->toBeTrue()
        ->and((float) $activit->total_value)->toBe(100.0);
});

it('nega edição da atividade para professor que não é dono', function () {
    $owner = Teacher::factory()->create();
    [, , $activit] = makeTaskActivity($owner);

    $other = Teacher::factory()->create();
    $this->actingAs($other, 'teacher');

    Livewire::test('pages::teacher.discipline.module.modals.edit-task')
        ->dispatch('open-edit-task', id: $activit->id)
        ->assertSet('openEdit', false);
});

it('valida data final anterior à inicial ao editar', function () {
    $teacher = Teacher::factory()->create();
    [, , $activit] = makeTaskActivity($teacher);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.modals.edit-task')
        ->dispatch('open-edit-task', id: $activit->id)
        ->set('startDate', '2026-02-10')
        ->set('endDate', '2026-02-01')
        ->call('save')
        ->assertHasErrors('endDate');
});

it('atualiza a alternativa correta da questão', function () {
    $teacher = Teacher::factory()->create();
    [, , $activit] = makeTaskActivity($teacher);
    $task = Task::factory()->forActivitTask($activit)->create(['type' => 'multiple_choice']);

    $first = TaskOptions::create([
        'task_id' => $task->id,
        'text' => 'Alternativa A',
        'correct' => true,
        'order' => 0,
    ]);
    $second = TaskOptions::create([
        'task_id' => $task->id,
        'text' => 'Alternativa B',
        'correct' => false,
        'order' => 1,
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.show-task')
        ->dispatch('task-show', activit_id: $activit->id)
        ->call('updateCorrectOption', 0, 1)
        ->assertHasNoErrors();

    expect($first->fresh()->correct)->toBeFalsy()
        ->and($second->fresh()->correct)->toBeTruthy();
});

it('não altera alternativa quando a atividade não pertence ao professor', function () {
    $owner = Teacher::factory()->create();
    [, , $activit] = makeTaskActivity($owner);
    $task = Task::factory()->forActivitTask($activit)->create(['type' => 'multiple_choice']);
    $option = TaskOptions::create([
        'task_id' => $task->id,
        'text' => 'Alternativa A',
        'correct' => false,
        'order' => 0,
    ]);

    $other = Teacher::factory()->create();
    $this->actingAs($other, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.show-task')
        ->dispatch('task-show', activit_id: $activit->id)
        ->call('updateCorrectOption', 0, 0)
        ->assertSet('activitTask', null);

    expect($option->fresh()->correct)->toBeFalsy();
});
it('escopa atividades e tarefas pelo professor dono via scopeOwnedByTeacher', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeTaskActivity($teacher);
    $task = Task::factory()->forActivitTask($activit)->create();

    $other = Teacher::factory()->create();
    [, , $otherActivit] = makeTaskActivity($other);
    $otherTask = Task::factory()->forActivitTask($otherActivit)->create();

    expect(ActivitTask::ownedByTeacher($teacher)->pluck('id'))
        ->toContain($activit->id)
        ->not->toContain($otherActivit->id)
        ->and(Task::ownedByTeacher($teacher)->pluck('id'))
        ->toContain($task->id)
        ->not->toContain($otherTask->id);
});

it('nega qualquer atividade/tarefa quando não há professor autenticado', function () {
    $teacher = Teacher::factory()->create();
    [, , $activit] = makeTaskActivity($teacher);
    Task::factory()->forActivitTask($activit)->create();

    expect(ActivitTask::ownedByTeacher(null)->count())->toBe(0)
        ->and(Task::ownedByTeacher(null)->count())->toBe(0);
});

it('salva a nota informada pelo professor no envio do feedback', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeTaskActivity($teacher);
    $task = Task::factory()->forActivitTask($activit)->create();
    $student = makeTaskStudent($teacher, $discipline);
    $answer = StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.student-anwsers')
        ->dispatch('event-teacher-show-student-answer', task_id: $task->id)
        ->set("feedbacks.{$answer->id}", 'Bom trabalho, continue assim!')
        ->set("grades.{$answer->id}", '15')
        ->call('sendFeedback', $answer->id)
        ->assertHasNoErrors();

    expect((float) $answer->fresh()->grade)->toBe(15.0)
        ->and($answer->fresh()->corrected)->toBeTrue();
});

it('rejeita nota inválida e não persiste a correção', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeTaskActivity($teacher);
    $task = Task::factory()->forActivitTask($activit)->create();
    $student = makeTaskStudent($teacher, $discipline);
    $answer = StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.student-anwsers')
        ->dispatch('event-teacher-show-student-answer', task_id: $task->id)
        ->set("feedbacks.{$answer->id}", 'Bom trabalho, continue assim!')
        ->set("grades.{$answer->id}", 'abc')
        ->call('sendFeedback', $answer->id)
        ->assertHasErrors("grades.{$answer->id}");

    $fresh = $answer->fresh();

    expect($fresh->grade)->toBeNull()
        ->and($fresh->corrected)->toBeFalsy()
        ->and(QuestionFeedback::where('student_anwser_id', $answer->id)->exists())->toBeFalse();
});

it('rejeita nota acima de 100 no feedback', function (string $grade) {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeTaskActivity($teacher);
    $task = Task::factory()->forActivitTask($activit)->create();
    $student = makeTaskStudent($teacher, $discipline);
    $answer = StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.student-anwsers')
        ->dispatch('event-teacher-show-student-answer', task_id: $task->id)
        ->set("feedbacks.{$answer->id}", 'Bom trabalho, continue assim!')
        ->set("grades.{$answer->id}", $grade)
        ->call('sendFeedback', $answer->id)
        ->assertHasErrors("grades.{$answer->id}");

    $fresh = $answer->fresh();

    expect($fresh->grade)->toBeNull()
        ->and($fresh->corrected)->toBeFalsy()
        ->and(QuestionFeedback::where('student_anwser_id', $answer->id)->exists())->toBeFalse();
})->with(['150', '101', '2000000000000', '8,5']);

it('aceita a nota máxima 100 no feedback', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeTaskActivity($teacher);
    $task = Task::factory()->forActivitTask($activit)->create();
    $student = makeTaskStudent($teacher, $discipline);
    $answer = StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.student-anwsers')
        ->dispatch('event-teacher-show-student-answer', task_id: $task->id)
        ->set("feedbacks.{$answer->id}", 'Bom trabalho, continue assim!')
        ->set("grades.{$answer->id}", '100')
        ->call('sendFeedback', $answer->id)
        ->assertHasNoErrors();

    $fresh = $answer->fresh();

    expect((float) $fresh->grade)->toBe(100.0)
        ->and($fresh->corrected)->toBeTrue();
});
