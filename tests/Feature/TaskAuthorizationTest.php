<?php

use App\Models\Module;
use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\QuestionFeedback;
use App\Models\Teacher\Student;
use App\Models\Teacher\StudentAnwser;
use App\Models\Teacher\Task;
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