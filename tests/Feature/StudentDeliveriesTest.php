<?php

use App\Models\Module;
use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\ActivityGrade;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Student;
use App\Models\Teacher\StudentAnwser;
use App\Models\Teacher\Task;
use App\Models\Teacher\Teacher;
use Livewire\Livewire;

function makeDeliveriesContext(Teacher $teacher): array
{
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);
    $activit = ActivitTask::factory()->forDiscipline($discipline, $module, $teacher)->create([
        'is_avaliative' => true,
        'total_value' => 100,
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

it('lista apenas os alunos que entregaram a atividade', function () {
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

    expect($students)->toHaveCount(2)
        ->and($students->firstWhere('id', $entregou->id)['status'])->toBe('corrected')
        ->and($students->firstWhere('id', $pendente->id)['status'])->toBe('pending')
        ->and($students->firstWhere('id', $semEntrega->id))->toBeNull();
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
        ->assertSee('Disciplina: '.$discipline->title)
        ->assertSee('Abrir');

    $listed = collect($component->get('students'))->firstWhere('id', $student->id);
    expect($listed['classe'])->toBe('Turma B')
        ->and($listed['discipline'])->toBe($discipline->title)
        ->and($listed['delivered'])->toBeTrue();
});

it('não lista aluno que não entregou a atividade', function () {
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

    expect($component->get('students'))->toBeEmpty();
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
        ->assertSee('Minha resposta completa');
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
        ->assertHasErrors("grades.{$answer->id}");

    expect($answer->fresh()->corrected)->toBeFalse();
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
        ->and((float) $grade->final_grade)->toBe(85.0);
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
