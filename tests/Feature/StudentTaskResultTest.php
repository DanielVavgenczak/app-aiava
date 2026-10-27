<?php

use App\Models\Module;
use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\Classe;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\QuestionFeedback;
use App\Models\Teacher\Student;
use App\Models\Teacher\StudentAnwser;
use App\Models\Teacher\Task;
use App\Models\Teacher\Teacher;
use Livewire\Livewire;

function makeResultContext(Teacher $teacher, Discipline $discipline): array
{
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);
    $activit = ActivitTask::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'module_id' => $module->id,
    ]);
    $task = Task::factory()->forActivitTask($activit)->create([
        'type' => 'text',
        'question' => 'Explique o conceito',
    ]);

    return [$activit, $task];
}

function makeResultStudent(Teacher $teacher, Discipline $discipline): Student
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

it('mostra a nota e o feedback do aluno para a atividade respondida', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    [$activit, $task] = makeResultContext($teacher, $discipline);
    $student = makeResultStudent($teacher, $discipline);

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
        'feedback' => 'Boa explicação, aprofunde os exemplos.',
    ]);

    $this->actingAs($student, 'student');

    Livewire::test('pages::student.discipline.lesson.task-result')
        ->dispatch('student-event-open-task-result', id: $activit->id)
        ->assertSet('show', true)
        ->assertSet('results.0.corrected', true)
        ->assertSet('results.0.feedback', 'Boa explicação, aprofunde os exemplos.')
        ->assertSee('85')
        ->assertSee('Boa explicação, aprofunde os exemplos.');
});

it('marca como aguardando correção quando a resposta não foi corrigida', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    [$activit, $task] = makeResultContext($teacher, $discipline);
    $student = makeResultStudent($teacher, $discipline);

    StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
        'corrected' => false,
        'grade' => null,
    ]);

    $this->actingAs($student, 'student');

    Livewire::test('pages::student.discipline.lesson.task-result')
        ->dispatch('student-event-open-task-result', id: $activit->id)
        ->assertSet('show', true)
        ->assertSet('results.0.corrected', false)
        ->assertSet('results.0.grade', null)
        ->assertSee('Aguardando correção');
});

it('não expõe resultado de atividade de outra disciplina', function () {
    $owner = Teacher::factory()->create();
    $ownerDiscipline = Discipline::factory()->for($owner)->create();
    [$activit, $task] = makeResultContext($owner, $ownerDiscipline);

    $other = Teacher::factory()->create();
    $otherDiscipline = Discipline::factory()->for($other)->create();
    $intruder = makeResultStudent($other, $otherDiscipline);

    StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $intruder->id,
    ]);

    $this->actingAs($intruder, 'student');

    Livewire::test('pages::student.discipline.lesson.task-result')
        ->dispatch('student-event-open-task-result', id: $activit->id)
        ->assertSet('show', false)
        ->assertSet('results', []);
});

it('não mistura respostas de outros alunos', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    [$activit, $task] = makeResultContext($teacher, $discipline);

    $studentA = makeResultStudent($teacher, $discipline);
    $studentB = makeResultStudent($teacher, $discipline);

    StudentAnwser::factory()->forTask($task, $activit)->create(['student_id' => $studentA->id]);
    StudentAnwser::factory()->forTask($task, $activit)->create(['student_id' => $studentB->id]);

    $this->actingAs($studentA, 'student');

    Livewire::test('pages::student.discipline.lesson.task-result')
        ->dispatch('student-event-open-task-result', id: $activit->id)
        ->assertSet('show', true)
        ->assertCount('results', 1);
});

it('mostra a nota final da atividade ao aluno', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    [$activit, $task] = makeResultContext($teacher, $discipline);
    $student = makeResultStudent($teacher, $discipline);

    StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
        'corrected' => true,
        'grade' => 80,
    ]);

    \App\Models\Teacher\ActivityGrade::create([
        'activit_task_id' => $activit->id,
        'student_id' => $student->id,
        'teacher_id' => $teacher->id,
        'final_grade' => 92,
    ]);

    $this->actingAs($student, 'student');

    Livewire::test('pages::student.discipline.lesson.task-result')
        ->dispatch('student-event-open-task-result', id: $activit->id)
        ->assertSet('finalGrade', '92')
        ->assertSee('Nota final da atividade')
        ->assertSee('92');
});
