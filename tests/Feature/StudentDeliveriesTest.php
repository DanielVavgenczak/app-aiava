<?php

use App\Models\Module;
use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\ActivityGrade;
use App\Models\Teacher\ActivityNotification;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Student;
use App\Models\Teacher\StudentAnwser;
use App\Models\Teacher\Task;
use App\Models\Teacher\Teacher;
use Livewire\Livewire;

function makeDeliveriesContext(Teacher $teacher, bool $graded = true): array
{
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);
    $activit = ActivitTask::factory()->forDiscipline($discipline, $module, $teacher)->create([
        'is_avaliative' => $graded,
        'total_value' => $graded ? 100 : null,
    ]);

    return [$discipline, $activit];
}

function makeDeliveriesStudent(Teacher $teacher, Discipline $discipline): Student
{
    return Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);
}

it('monta a lista com entregas e alunos sem envio', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit] = makeDeliveriesContext($teacher);
    $task = Task::factory()->forActivitTask($activit)->create();

    $entregou = makeDeliveriesStudent($teacher, $discipline);
    $pendente = makeDeliveriesStudent($teacher, $discipline);
    $semEntrega = makeDeliveriesStudent($teacher, $discipline); // não deve aparecer

    StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $entregou->id,
        'corrected' => true,
        'grade' => 50,
    ]);
    StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $pendente->id,
        'corrected' => false,
    ]);

    $this->actingAs($teacher, 'teacher');

    $component = Livewire::test('pages::teacher.discipline.module.task.student-deliveries', ['activityId' => $activit->id]);

    $students = collect($component->get('students'));

    expect($students)->toHaveCount(3)
        ->and($students->pluck('id')->unique())->toHaveCount(3)
        ->and($students->firstWhere('id', $entregou->id)['status'])->toBe('corrected')
        ->and($students->firstWhere('id', $entregou->id)['delivered'])->toBeTrue()
        ->and($students->firstWhere('id', $pendente->id)['status'])->toBe('pending')
        ->and($students->firstWhere('id', $pendente->id)['delivered'])->toBeTrue()
        ->and($students->firstWhere('id', $semEntrega->id)['delivered'])->toBeFalse()
        ->and($students->firstWhere('id', $semEntrega->id)['status'])->toBe('pending');
});

it('exibe nome, turma, disciplina e botão abrir na listagem de entregas', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit] = makeDeliveriesContext($teacher);
    $task = Task::factory()->forActivitTask($activit)->create();

    $classe = \App\Models\Teacher\Classe::factory()->create([
        'name' => 'Turma B',
        'discipline_id' => $discipline->id,
        'teacher_id' => $teacher->id,
    ]);

    $student = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'classe_id' => $classe->id,
        'full_name' => 'Aluno Entregador',
    ]);

    StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
        'corrected' => false,
    ]);

    $this->actingAs($teacher, 'teacher');

    $component = Livewire::test('pages::teacher.discipline.module.task.student-deliveries', ['activityId' => $activit->id])
        ->assertSee('Aluno Entregador')
        ->assertSee('Turma: Turma B')
        ->assertSeeHtml('data-delivery-student-classe="Turma B"')
        ->assertSee('Disciplina: '.$discipline->title)
        ->assertSee('Abrir');

    $listed = collect($component->get('students'))->firstWhere('id', $student->id);
    expect($listed['classe'])->toBe('Turma B')
        ->and($listed['discipline'])->toBe($discipline->title)
        ->and($listed['delivered'])->toBeTrue();
});

it('exibe aluno sem entrega apenas na aba Pendentes', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit] = makeDeliveriesContext($teacher);

    $student = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'full_name' => 'Aluno Sem Entrega',
    ]);

    $this->actingAs($teacher, 'teacher');

    $component = Livewire::test('pages::teacher.discipline.module.task.student-deliveries', ['activityId' => $activit->id])
        ->assertDontSee('Aluno Sem Entrega')
        ->assertSee('Nenhuma entrega');

    expect($component->get('students'))->toHaveCount(1)
        ->and($component->get('students.0.id'))->toBe($student->id)
        ->and($component->get('students.0.delivered'))->toBeFalse();

    $component->set('filter', 'pending')
        ->assertSee('Aluno Sem Entrega')
        ->assertSee('Pendente')
        ->set('filter', 'corrected')
        ->assertDontSee('Aluno Sem Entrega')
        ->assertSee('Nenhuma entrega neste filtro.');
});


it('separa Todos, Pendentes e Corrigidas sem duplicar alunos', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit] = makeDeliveriesContext($teacher);
    $task = Task::factory()->forActivitTask($activit)->create();

    $emCorrecao = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'full_name' => 'Aluno Em Correcao',
    ]);
    $corrigido = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'full_name' => 'Aluno Corrigido',
    ]);
    $comNotaFinal = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'full_name' => 'Aluno Com Nota Final',
    ]);
    $semEntrega = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'full_name' => 'Aluno Sem Envio',
    ]);

    StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $emCorrecao->id,
        'corrected' => false,
    ]);
    StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $corrigido->id,
        'corrected' => true,
        'grade' => 70,
    ]);
    StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $comNotaFinal->id,
        'corrected' => false,
    ]);
    ActivityGrade::create([
        'activit_task_id' => $activit->id,
        'student_id' => $comNotaFinal->id,
        'teacher_id' => $teacher->id,
        'final_grade' => 90,
    ]);

    $this->actingAs($teacher, 'teacher');

    $component = Livewire::test('pages::teacher.discipline.module.task.student-deliveries', ['activityId' => $activit->id]);

    $html = $component->html();

    expect(substr_count($html, 'data-delivery-student='))->toBe(3)
        ->and($html)->toContain('data-delivery-student="'.$emCorrecao->id.'"')
        ->and($html)->toContain('data-delivery-student="'.$corrigido->id.'"')
        ->and($html)->toContain('data-delivery-student="'.$comNotaFinal->id.'"')
        ->and($html)->not->toContain('data-delivery-student="'.$semEntrega->id.'"');

    $pendingHtml = $component->set('filter', 'pending')->html();

    expect(substr_count($pendingHtml, 'data-delivery-student='))->toBe(1)
        ->and($pendingHtml)->toContain('data-delivery-student="'.$semEntrega->id.'"')
        ->and($pendingHtml)->not->toContain('data-delivery-student="'.$emCorrecao->id.'"')
        ->and($pendingHtml)->not->toContain('data-delivery-student="'.$corrigido->id.'"')
        ->and($pendingHtml)->not->toContain('data-delivery-student="'.$comNotaFinal->id.'"');

    $reviewedHtml = $component->set('filter', 'corrected')->html();

    expect(substr_count($reviewedHtml, 'data-delivery-student='))->toBe(2)
        ->and($reviewedHtml)->toContain('data-delivery-student="'.$corrigido->id.'"')
        ->and($reviewedHtml)->toContain('data-delivery-student="'.$comNotaFinal->id.'"')
        ->and($reviewedHtml)->not->toContain('data-delivery-student="'.$emCorrecao->id.'"')
        ->and($reviewedHtml)->not->toContain('data-delivery-student="'.$semEntrega->id.'"');
});


it('carrega as respostas do aluno selecionado', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit] = makeDeliveriesContext($teacher);
    $task = Task::factory()->forActivitTask($activit)->create([
        'type' => 'dissertative',
        'question' => 'Explique o conceito',
    ]);
    $student = makeDeliveriesStudent($teacher, $discipline);

    StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
        'answer_text' => 'Minha resposta completa',
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.student-deliveries', ['activityId' => $activit->id])
        ->dispatch('event-teacher-select-student', student_id: $student->id)
        ->assertSet('selectedStudentId', $student->id)
        ->assertSee('Questão 1')
        ->assertSee('Explique o conceito')
        ->assertSee('Resposta do aluno')
        ->assertSee('Minha resposta completa')
        ->assertSee('Feedback da correção')
        ->assertSeeHtml('data-delivery-question')
        ->assertSeeHtml('data-delivery-answer');
});

it('rejeita aluno de outra disciplina ao selecionar', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit] = makeDeliveriesContext($teacher);
    Task::factory()->forActivitTask($activit)->create();

    $outraDiscipline = Discipline::factory()->for($teacher)->create();
    $alunoDeFora = makeDeliveriesStudent($teacher, $outraDiscipline);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.student-deliveries', ['activityId' => $activit->id])
        ->dispatch('event-teacher-select-student', student_id: $alunoDeFora->id)
        ->assertSet('selectedStudentId', null);
});

it('corrige uma questão respeitando o teto de valor', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit] = makeDeliveriesContext($teacher);
    $task = Task::factory()->forActivitTask($activit)->create(['value' => 10]);
    $student = makeDeliveriesStudent($teacher, $discipline);
    $answer = StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.student-deliveries', ['activityId' => $activit->id])
        ->dispatch('event-teacher-select-student', student_id: $student->id)
        ->set("feedbacks.{$answer->id}", 'Feedback válido para o aluno')
        ->set("grades.{$answer->id}", '11')
        ->call('sendFeedback', $answer->id)
        ->assertHasErrors("grades.{$answer->id}")
        ->assertSet("feedbacks.{$answer->id}", 'Feedback válido para o aluno');

    expect($answer->fresh()->corrected)->toBeFalse();
});

it('usa o novo placeholder, limpa o feedback salvo e preserva em caso de erro', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit] = makeDeliveriesContext($teacher);
    $task = Task::factory()->forActivitTask($activit)->create(['value' => 10]);
    $student = makeDeliveriesStudent($teacher, $discipline);
    $answer = StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
    ]);

    $this->actingAs($teacher, 'teacher');

    $component = Livewire::test('pages::teacher.discipline.module.task.student-deliveries', ['activityId' => $activit->id])
        ->dispatch('event-teacher-select-student', student_id: $student->id)
        ->assertSee('Write feedback')
        ->set("feedbacks.{$answer->id}", 'curto')
        ->set("grades.{$answer->id}", '8')
        ->call('sendFeedback', $answer->id)
        ->assertHasErrors("feedbacks.{$answer->id}")
        ->assertSet("feedbacks.{$answer->id}", 'curto')
        ->set("feedbacks.{$answer->id}", 'Feedback válido para o aluno')
        ->call('sendFeedback', $answer->id)
        ->assertHasNoErrors()
        ->assertSet("feedbacks.{$answer->id}", '');

    expect($answer->fresh()->corrected)->toBeTrue()
        ->and((float) $answer->fresh()->grade)->toBe(8.0);
});

it('salva a nota final da atividade do aluno', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit] = makeDeliveriesContext($teacher);
    $task = Task::factory()->forActivitTask($activit)->create(['value' => 100]);
    $student = makeDeliveriesStudent($teacher, $discipline);
    StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.student-deliveries', ['activityId' => $activit->id])
        ->dispatch('event-teacher-select-student', student_id: $student->id)
        ->set('finalGrade', '85')
        ->call('saveFinalGrade')
        ->assertHasNoErrors();

    $grade = ActivityGrade::where('activit_task_id', $activit->id)
        ->where('student_id', $student->id)
        ->first();

    expect($grade)->not->toBeNull()
        ->and((float) $grade->final_grade)->toBe(85.0)
        ->and(ActivityNotification::query()
            ->where('notifiable_type', Student::class)
            ->where('notifiable_id', $student->id)
            ->where('type', ActivityNotification::TYPE_REVIEWED)
            ->exists())->toBeTrue();
});

it('rejeita nota final acima do valor da atividade', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit] = makeDeliveriesContext($teacher); // total 100
    $task = Task::factory()->forActivitTask($activit)->create(['value' => 100]);
    $student = makeDeliveriesStudent($teacher, $discipline);
    StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.student-deliveries', ['activityId' => $activit->id])
        ->dispatch('event-teacher-select-student', student_id: $student->id)
        ->set('finalGrade', '150')
        ->call('saveFinalGrade')
        ->assertHasErrors('finalGrade');

    expect(ActivityGrade::count())->toBe(0);
});

it('nega abrir entregas de atividade de outro professor', function () {
    $owner = Teacher::factory()->create();
    [, $activit] = makeDeliveriesContext($owner);

    $other = Teacher::factory()->create();
    $this->actingAs($other, 'teacher');

    $component = Livewire::test('pages::teacher.discipline.module.task.student-deliveries', ['activityId' => $activit->id]);

    expect($component->get('students'))->toBeEmpty()
        ->and($component->get('activitTaskId'))->toBe('');
});

it('marca a resposta de multipla escolha como correta na correcao', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit] = makeDeliveriesContext($teacher);

    $task = Task::factory()->forActivitTask($activit)->create([
        'type' => 'multiple_choice',
        'value' => 10,
    ]);

    $option = \App\Models\Teacher\TaskOptions::factory()->for($task)->correct()->create([
        'text' => 'Alternativa correta',
    ]);

    $student = makeDeliveriesStudent($teacher, $discipline);

    StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
        'answer_option_id' => $option->id,
        'is_correct' => true,
        'corrected' => true,
    ]);

    $this->actingAs($teacher, 'teacher');

    $component = Livewire::test('pages::teacher.discipline.module.task.student-deliveries', ['activityId' => $activit->id])
        ->dispatch('event-teacher-select-student', student_id: $student->id)
        ->assertSee('Alternativa correta')
        ->assertSee('Correta')
        ->assertSee('bg-green-100');

    $answer = collect($component->get('answers'))->first();
    expect($answer['type'])->toBe('multiple_choice')
        ->and($answer['is_correct'])->toBeTrue();
});

it('marca a resposta de multipla escolha como incorreta na correcao', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit] = makeDeliveriesContext($teacher);

    $task = Task::factory()->forActivitTask($activit)->create([
        'type' => 'multiple_choice',
        'value' => 10,
    ]);

    $wrongOption = \App\Models\Teacher\TaskOptions::factory()->for($task)->create([
        'text' => 'Alternativa errada',
    ]);

    $student = makeDeliveriesStudent($teacher, $discipline);

    StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
        'answer_option_id' => $wrongOption->id,
        'is_correct' => false,
        'corrected' => true,
    ]);

    $this->actingAs($teacher, 'teacher');

    $component = Livewire::test('pages::teacher.discipline.module.task.student-deliveries', ['activityId' => $activit->id])
        ->dispatch('event-teacher-select-student', student_id: $student->id)
        ->assertSee('Alternativa errada')
        ->assertSee('Incorreta')
        ->assertSee('bg-red-100');

    $answer = collect($component->get('answers'))->first();
    expect($answer['is_correct'])->toBeFalse();
});


it('oculta notas ao corrigir atividade não avaliativa', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit] = makeDeliveriesContext($teacher, false);
    $task = Task::factory()->forActivitTask($activit)->create(['value' => 10]);
    $student = makeDeliveriesStudent($teacher, $discipline);
    $answer = StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
    ]);

    $this->actingAs($teacher, 'teacher');

    $component = Livewire::test('pages::teacher.discipline.module.task.student-deliveries', ['activityId' => $activit->id])
        ->dispatch('event-teacher-select-student', student_id: $student->id)
        ->assertSet('isGraded', false)
        ->assertSee('Write feedback')
        ->assertDontSee('Nota da questão (ex: 8)')
        ->assertDontSee('Nota final da atividade')
        ->set("feedbacks.{$answer->id}", 'Feedback sem nota para o aluno')
        ->set("grades.{$answer->id}", '9')
        ->call('sendFeedback', $answer->id)
        ->assertHasNoErrors();

    $answer->refresh();

    expect($answer->corrected)->toBeTrue()
        ->and($answer->grade)->toBeNull();

    $component->set('finalGrade', '9')->call('saveFinalGrade');

    expect(ActivityGrade::count())->toBe(0);
});
