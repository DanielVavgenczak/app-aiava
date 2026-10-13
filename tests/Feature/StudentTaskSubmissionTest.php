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
