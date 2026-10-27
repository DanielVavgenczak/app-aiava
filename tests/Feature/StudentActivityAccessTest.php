<?php

use App\Models\Module;
use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\ActivityAccess;
use App\Models\Teacher\Classe;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Student;
use App\Models\Teacher\Task;
use App\Models\Teacher\Teacher;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

function makeAccessStudent(Teacher $teacher, Discipline $discipline): Student
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

function makeAccessActivity(Teacher $teacher, Discipline $discipline, ?string $start = null, ?string $end = null): ActivitTask
{
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);

    $activit = ActivitTask::factory()
        ->forDiscipline($discipline, $module, $teacher)
        ->create([
            'start' => $start,
            'end' => $end,
        ]);

    Task::factory()->forActivitTask($activit)->create(['type' => 'text']);

    return $activit;
}

it('registra o primeiro acesso ao abrir a atividade', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $student = makeAccessStudent($teacher, $discipline);
    $activit = makeAccessActivity($teacher, $discipline);

    $this->travelTo(Carbon::parse('2026-01-05 09:30:00'));
    $this->actingAs($student, 'student');

    Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id)
        ->assertSet('show', true);

    $access = ActivityAccess::where('activit_task_id', $activit->id)
        ->where('student_id', $student->id)
        ->first();

    expect($access)->not->toBeNull()
        ->and($access->first_accessed_at->toDateTimeString())->toBe('2026-01-05 09:30:00');
});

it('não sobrescreve o primeiro acesso ao abrir novamente', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $student = makeAccessStudent($teacher, $discipline);
    $activit = makeAccessActivity($teacher, $discipline);

    $this->travelTo(Carbon::parse('2026-01-05 09:30:00'));
    $this->actingAs($student, 'student');

    Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id);

    $this->travelTo(Carbon::parse('2026-01-08 15:00:00'));

    Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id);

    $accesses = ActivityAccess::where('activit_task_id', $activit->id)
        ->where('student_id', $student->id)
        ->get();

    expect($accesses)->toHaveCount(1)
        ->and($accesses->first()->first_accessed_at->toDateTimeString())->toBe('2026-01-05 09:30:00');
});

it('não registra acesso quando a atividade está fora do prazo', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $student = makeAccessStudent($teacher, $discipline);

    $this->travelTo(Carbon::parse('2026-01-01 00:00:00'));
    $activit = makeAccessActivity($teacher, $discipline, '2026-01-01 00:00:00', '2026-01-10 23:59:59');

    $this->travelTo(Carbon::parse('2026-01-20 12:00:00'));
    $this->actingAs($student, 'student');

    Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id)
        ->assertSet('show', false);

    expect(ActivityAccess::count())->toBe(0);
});

it('avisa quando a atividade ainda não começou', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $student = makeAccessStudent($teacher, $discipline);

    $this->travelTo(Carbon::parse('2026-01-05 09:30:00'));
    $activit = makeAccessActivity($teacher, $discipline, '2026-01-10 00:00:00', '2026-01-15 23:59:59');

    $this->actingAs($student, 'student');

    Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id)
        ->assertSet('show', false);

    expect(ActivityAccess::count())->toBe(0);
});

it('avisa quando o prazo da atividade terminou', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $student = makeAccessStudent($teacher, $discipline);

    $this->travelTo(Carbon::parse('2026-01-20 12:00:00'));
    $activit = makeAccessActivity($teacher, $discipline, '2026-01-01 00:00:00', '2026-01-10 23:59:59');

    $this->actingAs($student, 'student');

    Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id)
        ->assertSet('show', false);

    expect(ActivityAccess::count())->toBe(0);
});

it('não registra acesso para atividade de outra disciplina', function () {
    $owner = Teacher::factory()->create();
    $ownerDiscipline = Discipline::factory()->for($owner)->create();
    $activit = makeAccessActivity($owner, $ownerDiscipline);

    $otherTeacher = Teacher::factory()->create();
    $otherDiscipline = Discipline::factory()->for($otherTeacher)->create();
    $student = makeAccessStudent($otherTeacher, $otherDiscipline);

    $this->actingAs($student, 'student');

    Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id)
        ->assertSet('show', false);

    expect(ActivityAccess::count())->toBe(0);
});
