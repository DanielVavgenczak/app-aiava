<?php

use App\Models\Module;
use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\StudentAnwser;
use App\Models\Teacher\Task;
use App\Models\Teacher\TaskOptions;
use App\Models\Teacher\Teacher;

function makeActivitForModel(Teacher $teacher): ActivitTask
{
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);

    return ActivitTask::factory()->forDiscipline($discipline, $module, $teacher)->create();
}

it('cria atividade não avaliativa por padrão', function () {
    $teacher = Teacher::factory()->create();
    $activit = makeActivitForModel($teacher);

    expect($activit->is_avaliative)->toBeFalse()
        ->and($activit->total_value)->toBeNull()
        ->and($activit->isOpenForAnswers())->toBeTrue();
});

it('cria atividade avaliativa com valor total', function () {
    $teacher = Teacher::factory()->create();
    $activit = makeActivitForModel($teacher);
    $activit->update(['is_avaliative' => true, 'total_value' => 100]);

    expect($activit->fresh()->is_avaliative)->toBeTrue()
        ->and((float) $activit->fresh()->total_value)->toBe(100.0);
});

it('atualiza metadados da atividade', function () {
    $teacher = Teacher::factory()->create();
    $activit = makeActivitForModel($teacher);

    $activit->update([
        'title' => 'Avaliação Bimestral',
        'is_avaliative' => true,
        'total_value' => 60,
        'start' => '2026-03-01 00:00:00',
        'end' => '2026-03-10 23:59:59',
    ]);

    $activit->refresh();

    expect($activit->title)->toBe('Avaliação Bimestral')
        ->and($activit->is_avaliative)->toBeTrue()
        ->and((float) $activit->total_value)->toBe(60.0)
        ->and($activit->start->format('Y-m-d'))->toBe('2026-03-01');
});

it('deleta a atividade em cascata, removendo questões e opções', function () {
    $teacher = Teacher::factory()->create();
    $activit = makeActivitForModel($teacher);
    $task = Task::factory()->forActivitTask($activit)->create(['type' => 'multiple_choice']);
    TaskOptions::factory()->for($task)->correct()->create();

    $activitId = $activit->id;
    $taskId = $task->id;

    $activit->delete();

    expect(ActivitTask::find($activitId))->toBeNull()
        ->and(Task::find($taskId))->toBeNull()
        ->and(TaskOptions::where('task_id', $taskId)->count())->toBe(0);
});

it('deleta em cascata as respostas dos alunos ao remover a atividade', function () {
    $teacher = Teacher::factory()->create();
    $activit = makeActivitForModel($teacher);
    $task = Task::factory()->forActivitTask($activit)->create(['type' => 'dissertative']);

    $discipline = Discipline::factory()->for($teacher)->create();
    $student = \App\Models\Teacher\Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);

    $answer = StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
    ]);
    $answerId = $answer->id;

    $activit->delete();

    expect(StudentAnwser::find($answerId))->toBeNull();
});
