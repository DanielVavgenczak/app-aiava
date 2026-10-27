<?php

use App\Models\Module;
use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\ActivityAccess;
use App\Models\Teacher\Classe;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Student;
use App\Models\Teacher\StudentAnwser;
use App\Models\Teacher\Task;
use App\Models\Teacher\Teacher;
use App\Support\ActivityProgress;
use Illuminate\Support\Carbon;

function makeProgressStudent(Teacher $teacher, Discipline $discipline): Student
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

function makeProgressActivity(Teacher $teacher, Discipline $discipline, ?string $start = null, ?string $end = null): array
{
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);

    $activit = ActivitTask::factory()
        ->forDiscipline($discipline, $module, $teacher)
        ->create([
            'start' => $start,
            'end' => $end,
        ]);

    $task = Task::factory()->forActivitTask($activit)->create([
        'type' => 'text',
        'question' => 'Questão de teste',
    ]);

    return [$activit, $task];
}

it('classifica atividade sem acesso como não acessada', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $student = makeProgressStudent($teacher, $discipline);
    [$activit] = makeProgressActivity($teacher, $discipline);

    $progress = ActivityProgress::forStudent($student, collect([$activit]))[$activit->id];

    expect($progress['status'])->toBe(ActivityProgress::NOT_ACCESSED)
        ->and($progress['answered'])->toBeFalse()
        ->and($progress['first_accessed_at'])->toBeNull()
        ->and(ActivityProgress::label($progress['status']))->toBe('Não acessada');
});

it('classifica como pendente quando acessou mas não respondeu', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $student = makeProgressStudent($teacher, $discipline);
    [$activit] = makeProgressActivity($teacher, $discipline);

    ActivityAccess::factory()->forActivity($activit, $student)->create([
        'first_accessed_at' => now(),
    ]);

    $progress = ActivityProgress::forStudent($student, collect([$activit]))[$activit->id];

    expect($progress['status'])->toBe(ActivityProgress::PENDING)
        ->and($progress['first_accessed_at'])->not->toBeNull()
        ->and(ActivityProgress::label($progress['status']))->toBe('Pendente');
});

it('classifica como respondida quando existe resposta', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $student = makeProgressStudent($teacher, $discipline);
    [$activit, $task] = makeProgressActivity($teacher, $discipline);

    StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
        'answer_text' => 'Minha resposta',
    ]);

    $progress = ActivityProgress::forStudent($student, collect([$activit]))[$activit->id];

    expect($progress['status'])->toBe(ActivityProgress::ANSWERED)
        ->and($progress['answered'])->toBeTrue()
        ->and($progress['late'])->toBeFalse()
        ->and($progress['answered_at'])->not->toBeNull()
        ->and(ActivityProgress::label($progress['status']))->toBe('Respondida');
});

it('classifica como fora do prazo quando o prazo passou sem resposta', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $student = makeProgressStudent($teacher, $discipline);

    $this->travelTo(Carbon::parse('2026-01-01 00:00:00'));
    [$activit] = makeProgressActivity($teacher, $discipline, '2026-01-01 00:00:00', '2026-01-10 23:59:59');

    $this->travelTo(Carbon::parse('2026-01-20 12:00:00'));

    $progress = ActivityProgress::forStudent($student, collect([$activit]))[$activit->id];

    expect($progress['status'])->toBe(ActivityProgress::OVERDUE)
        ->and(ActivityProgress::label($progress['status']))->toBe('Fora do prazo');
});

it('não marca como atrasada resposta enviada dentro do prazo mesmo consultando depois', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $student = makeProgressStudent($teacher, $discipline);

    $this->travelTo(Carbon::parse('2026-01-01 00:00:00'));
    [$activit, $task] = makeProgressActivity($teacher, $discipline, '2026-01-01 00:00:00', '2026-01-10 23:59:59');

    $this->travelTo(Carbon::parse('2026-01-05 10:00:00'));

    StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
        'answer_text' => 'Respondi no prazo',
    ]);

    $this->travelTo(Carbon::parse('2026-02-01 12:00:00'));

    $progress = ActivityProgress::forStudent($student, collect([$activit]))[$activit->id];

    expect($progress['status'])->toBe(ActivityProgress::ANSWERED)
        ->and($progress['late'])->toBeFalse();
});

it('diferencia resposta enviada em atraso', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $student = makeProgressStudent($teacher, $discipline);

    $this->travelTo(Carbon::parse('2026-01-01 00:00:00'));
    [$activit, $task] = makeProgressActivity($teacher, $discipline, '2026-01-01 00:00:00', '2026-01-10 23:59:59');

    $this->travelTo(Carbon::parse('2026-01-15 10:00:00'));

    StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $student->id,
        'answer_text' => 'Respondi atrasado',
    ]);

    $progress = ActivityProgress::forStudent($student, collect([$activit]))[$activit->id];

    expect($progress['status'])->toBe(ActivityProgress::ANSWERED)
        ->and($progress['late'])->toBeTrue()
        ->and(ActivityProgress::label($progress['status'], $progress['late']))->toBe('Respondida em atraso');
});

it('não aplica vencimento quando a atividade não tem prazo', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $student = makeProgressStudent($teacher, $discipline);

    $this->travelTo(Carbon::parse('2030-01-01 12:00:00'));
    [$activit] = makeProgressActivity($teacher, $discipline, null, null);

    ActivityAccess::factory()->forActivity($activit, $student)->create([
        'first_accessed_at' => now(),
    ]);

    $progress = ActivityProgress::forStudent($student, collect([$activit]))[$activit->id];

    expect($progress['status'])->toBe(ActivityProgress::PENDING)
        ->and($progress['deadline'])->toBeNull();
});
