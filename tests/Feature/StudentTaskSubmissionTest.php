<?php

use App\Models\Module;
use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\Classe;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Student;
use App\Models\Teacher\StudentAnwser;
use App\Models\Teacher\Task;
use App\Models\Teacher\TaskOptions;
use App\Models\Teacher\Teacher;
use Illuminate\Database\QueryException;
use Livewire\Livewire;

function makeSubmissionStudent(Teacher $teacher, Discipline $discipline): Student
{
    $classe = Classe::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);

    return Student::factory()->create([
        'teacher_id' => $teacher->id,
        'classe_id' => $classe->id,
        'discipline_id' => $discipline->id,
    ]);
}

function makeSubmissionContext(Teacher $teacher, string $taskType): array
{
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);
    $activit = ActivitTask::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'module_id' => $module->id,
    ]);
    $task = Task::factory()->forActivitTask($activit)->create([
        'type' => $taskType,
        'question' => 'Questao de teste',
    ]);

    return [$discipline, $activit, $task];
}

it('não duplica resposta de múltipla escolha ao reenviar', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit, $task] = makeSubmissionContext($teacher, 'multiple_choice');
    $option = TaskOptions::create([
        'task_id' => $task->id,
        'text' => 'Alternativa A',
        'correct' => true,
        'order' => 0,
    ]);
    $student = makeSubmissionStudent($teacher, $discipline);

    $this->actingAs($student, 'student');

    $submit = function () use ($activit, $task, $option) {
        Livewire::test('pages::student.discipline.lesson.show-task-lesson')
            ->dispatch('student-event-open-task', id: $activit->id)
            ->set("selectedOptions.{$task->id}", $option->id)
            ->call('submitAnswers');
    };

    $submit();
    $submit();

    expect(StudentAnwser::where('task_id', $task->id)->where('student_id', $student->id)->count())
        ->toBe(1);
});

it('não duplica resposta dissertativa ao reenviar', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit, $task] = makeSubmissionContext($teacher, 'text');
    $student = makeSubmissionStudent($teacher, $discipline);

    $this->actingAs($student, 'student');

    $submit = function () use ($activit, $task) {
        Livewire::test('pages::student.discipline.lesson.show-task-lesson')
            ->dispatch('student-event-open-task', id: $activit->id)
            ->set("dissertativeAnswers.{$task->id}", 'Minha resposta dissertativa')
            ->call('submitAnswers');
    };

    $submit();
    $submit();

    expect(StudentAnwser::where('task_id', $task->id)->where('student_id', $student->id)->count())
        ->toBe(1);
});

it('impede resposta duplicada para a mesma questao e tentativa no banco', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit, $task] = makeSubmissionContext($teacher, 'text');
    $student = makeSubmissionStudent($teacher, $discipline);

    StudentAnwser::create([
        'task_id' => $task->id,
        'student_id' => $student->id,
        'activit_task_id' => $activit->id,
        'answer_text' => 'Primeira resposta',
        'attempt_number' => 1,
    ]);

    expect(fn () => StudentAnwser::create([
        'task_id' => $task->id,
        'student_id' => $student->id,
        'activit_task_id' => $activit->id,
        'answer_text' => 'Resposta duplicada',
        'attempt_number' => 1,
    ]))->toThrow(QueryException::class);
});

it('não expõe o gabarito no estado público do componente', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit, $task] = makeSubmissionContext($teacher, 'multiple_choice');
    $correct = TaskOptions::factory()->for($task)->correct()->create(['text' => 'GABARITO_SECRETO']);
    TaskOptions::factory()->for($task)->create(['text' => 'ERRADA_PUBLICA']);
    $student = makeSubmissionStudent($teacher, $discipline);

    $this->actingAs($student, 'student');

    $component = Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id);

    // O estado público (payload enviado ao navegador) não pode conter o gabarito.
    expect($component->get('tasks'))->toBeArray();

    foreach ($component->get('tasks') as $safeTask) {
        foreach ($safeTask['options'] as $safeOption) {
            expect($safeOption)->not->toHaveKey('correct');
        }
    }

    // O teste do gabarito não pode vazar nem no estado serializado (wire:snapshot).
    expect($component->html())->not->toContain('"correct"');
});

it('não abre atividade antes da data de início', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit] = makeSubmissionContext($teacher, 'text');
    $activit->update(['start' => now()->addDay(), 'end' => now()->addDays(2)]);
    $student = makeSubmissionStudent($teacher, $discipline);

    $this->actingAs($student, 'student');

    Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id)
        ->assertSet('show', false);
});

it('não abre atividade após a data final', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit] = makeSubmissionContext($teacher, 'text');
    $activit->update(['start' => now()->subDays(2), 'end' => now()->subDay()]);
    $student = makeSubmissionStudent($teacher, $discipline);

    $this->actingAs($student, 'student');

    Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id)
        ->assertSet('show', false);
});

it('abre atividade dentro da janela de datas', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit] = makeSubmissionContext($teacher, 'text');
    $activit->update(['start' => now()->subDay(), 'end' => now()->addDay()]);
    $student = makeSubmissionStudent($teacher, $discipline);

    $this->actingAs($student, 'student');

    Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id)
        ->assertSet('show', true);
});

it('bloqueia submissão quando a janela fecha durante a sessão', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit, $task] = makeSubmissionContext($teacher, 'text');
    $activit->update(['end' => now()->addHour()]);
    $student = makeSubmissionStudent($teacher, $discipline);

    $this->actingAs($student, 'student');

    $component = Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id)
        ->assertSet('show', true);

    $this->travel(2)->hours();

    $component->set("dissertativeAnswers.{$task->id}", 'Resposta atrasada')
        ->call('submitAnswers');

    expect(StudentAnwser::where('task_id', $task->id)->exists())->toBeFalse();
});

it('marca a resposta de múltipla escolha conforme a alternativa selecionada', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit, $task] = makeSubmissionContext($teacher, 'multiple_choice');
    $correct = TaskOptions::factory()->for($task)->correct()->create();
    $wrong = TaskOptions::factory()->for($task)->create();
    $student = makeSubmissionStudent($teacher, $discipline);

    $this->actingAs($student, 'student');

    Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id)
        ->set("selectedOptions.{$task->id}", $correct->id)
        ->call('submitAnswers');

    $answer = StudentAnwser::where('task_id', $task->id)->first();

    expect($answer)->not->toBeNull()
        ->and($answer->is_correct)->toBeTrue()
        ->and($answer->answer_option_id)->toBe($correct->id);

    Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id)
        ->set("selectedOptions.{$task->id}", $wrong->id)
        ->call('submitAnswers');

    expect($answer->fresh()->is_correct)->toBeFalse()
        ->and($answer->fresh()->answer_option_id)->toBe($wrong->id);
});

it('exige resposta de todas as questões antes de enviar', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit, $task] = makeSubmissionContext($teacher, 'text');
    $student = makeSubmissionStudent($teacher, $discipline);

    $this->actingAs($student, 'student');

    Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id)
        ->call('submitAnswers')
        ->assertHasErrors("dissertativeAnswers.{$task->id}");

    expect(StudentAnwser::where('task_id', $task->id)->exists())->toBeFalse();
});

it('pré-carrega a resposta existente ao reabrir a atividade', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit, $task] = makeSubmissionContext($teacher, 'text');
    $student = makeSubmissionStudent($teacher, $discipline);

    StudentAnwser::create([
        'task_id' => $task->id,
        'student_id' => $student->id,
        'activit_task_id' => $activit->id,
        'answer_text' => 'Resposta anterior',
        'attempt_number' => 1,
    ]);

    $this->actingAs($student, 'student');

    Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id)
        ->assertSet("dissertativeAnswers.{$task->id}", 'Resposta anterior');
});

it('trava questão já corrigida e não apaga nota ao reenviar', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit, $task] = makeSubmissionContext($teacher, 'text');
    $student = makeSubmissionStudent($teacher, $discipline);

    $answer = StudentAnwser::create([
        'task_id' => $task->id,
        'student_id' => $student->id,
        'activit_task_id' => $activit->id,
        'answer_text' => 'Resposta corrigida',
        'attempt_number' => 1,
        'corrected' => true,
        'grade' => 8,
    ]);

    $this->actingAs($student, 'student');

    Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id)
        ->assertSet('lockedTaskIds', [(string) $task->id])
        ->set("dissertativeAnswers.{$task->id}", 'Tentativa de alterar após correção')
        ->call('submitAnswers');

    expect($answer->fresh()->answer_text)->toBe('Resposta corrigida')
        ->and((float) $answer->fresh()->grade)->toBe(8.0)
        ->and($answer->fresh()->corrected)->toBeTrue();
});

it('inicializa as respostas com uma chave por questão', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit, $textTask] = makeSubmissionContext($teacher, 'text');
    $choiceTask = Task::factory()->forActivitTask($activit)->create([
        'type' => 'multiple_choice',
        'question' => 'Segunda questao de teste',
    ]);
    $student = makeSubmissionStudent($teacher, $discipline);

    $this->actingAs($student, 'student');

    $component = Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id);

    expect($component->get('dissertativeAnswers'))->toBe([(string) $textTask->id => ''])
        ->and($component->get('selectedOptions'))->toBe([(string) $choiceTask->id => '']);
});

it('envia atividade com uma única questão dissertativa', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit, $task] = makeSubmissionContext($teacher, 'text');
    $student = makeSubmissionStudent($teacher, $discipline);

    $this->actingAs($student, 'student');

    Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id)
        ->assertSet("dissertativeAnswers.{$task->id}", '')
        ->set("dissertativeAnswers.{$task->id}", 'Resposta única')
        ->call('submitAnswers')
        ->assertHasNoErrors()
        ->assertSet('show', false);

    $answer = StudentAnwser::where('task_id', $task->id)->where('student_id', $student->id)->first();

    expect($answer)->not->toBeNull()
        ->and($answer->answer_text)->toBe('Resposta única');
});

it('exige todas as questões e salva somente após completar', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit, $textTask] = makeSubmissionContext($teacher, 'text');
    $choiceTask = Task::factory()->forActivitTask($activit)->create([
        'type' => 'multiple_choice',
        'question' => 'Segunda questao de teste',
    ]);
    $option = TaskOptions::factory()->for($choiceTask)->correct()->create();
    $student = makeSubmissionStudent($teacher, $discipline);

    $this->actingAs($student, 'student');

    $component = Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id)
        ->set("dissertativeAnswers.{$textTask->id}", 'Resposta dissertativa')
        ->call('submitAnswers')
        ->assertHasErrors("selectedOptions.{$choiceTask->id}")
        ->assertSet('show', true);

    expect(StudentAnwser::where('activit_task_id', $activit->id)->count())->toBe(0);

    $component
        ->set("selectedOptions.{$choiceTask->id}", $option->id)
        ->call('submitAnswers')
        ->assertHasNoErrors()
        ->assertSet('show', false);

    expect(StudentAnwser::where('activit_task_id', $activit->id)->count())->toBe(2)
        ->and(StudentAnwser::where('task_id', $choiceTask->id)->first()->is_correct)->toBeTrue();
});


it('aceita e recarrega resposta dissertativa igual a zero', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit, $task] = makeSubmissionContext($teacher, 'text');
    $student = makeSubmissionStudent($teacher, $discipline);

    $this->actingAs($student, 'student');

    Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id)
        ->set("dissertativeAnswers.{$task->id}", '0')
        ->call('submitAnswers')
        ->assertHasNoErrors()
        ->assertSet('show', false);

    expect(StudentAnwser::where('task_id', $task->id)->first()?->answer_text)->toBe('0');

    Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id)
        ->assertSet("dissertativeAnswers.{$task->id}", '0');
});
