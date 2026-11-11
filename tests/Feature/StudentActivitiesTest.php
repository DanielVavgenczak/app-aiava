<?php

use App\Livewire\Global\StudentActivities;
use App\Models\Module;
use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\ActivityNotification;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Student;
use App\Models\Teacher\StudentAnwser;
use App\Models\Teacher\Task;
use App\Models\Teacher\Teacher;
use App\Support\ActivityNotificationService;
use Livewire\Livewire;

function makeStudentActivitiesContext(Teacher $teacher): array
{
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);
    $activit = ActivitTask::factory()->forDiscipline($discipline, $module, $teacher)->create();
    $activit->update(['title' => 'Atividade pendente']);

    return [$discipline, $module, $activit];
}

function makeStudentActivity(
    Teacher $teacher,
    Discipline $discipline,
    Module $module,
    string $title,
    array $attributes = []
): ActivitTask {
    $activity = ActivitTask::factory()
        ->forDiscipline($discipline, $module, $teacher)
        ->create($attributes);

    $activity->update(['title' => $title]);

    return $activity;
}

it('lista todas as atividades pendentes da disciplina do aluno', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $module, $pending] = makeStudentActivitiesContext($teacher);

    $answered = makeStudentActivity($teacher, $discipline, $module, 'Atividade respondida');
    $answeredTask = Task::factory()->forActivitTask($answered)->create(['type' => 'dissertative']);

    $otherDiscipline = Discipline::factory()->for($teacher)->create();
    $otherModule = Module::factory()->create(['discipline_id' => $otherDiscipline->id]);
    makeStudentActivity($teacher, $otherDiscipline, $otherModule, 'Atividade de outra disciplina');

    makeStudentActivity($teacher, $discipline, $module, 'Atividade encerrada', [
        'end' => now()->subDay(),
    ]);

    makeStudentActivity($teacher, $discipline, $module, 'Atividade futura', [
        'start' => now()->addDay(),
    ]);

    $student = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);

    StudentAnwser::factory()
        ->forTask($answeredTask, $answered)
        ->create([
            'student_id' => $student->id,
            'answer_text' => 'Resposta enviada',
        ]);

    $this->actingAs($student, 'student');

    $component = Livewire::test(StudentActivities::class, ['studentId' => $student->id])
        ->assertSee($pending->title)
        ->assertSee('Atividade futura')
        ->assertSee($discipline->title)
        ->assertDontSee('Atividade respondida')
        ->assertDontSee('Atividade de outra disciplina')
        ->assertDontSee('Atividade encerrada');

    expect($component->instance()->pendingCount)->toBe(2);
});

it('atualiza a contagem e redireciona para a atividade pendente', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeStudentActivitiesContext($teacher);
    $student = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);

    $this->actingAs($student, 'student');

    Livewire::test(StudentActivities::class, ['studentId' => $student->id])
        ->dispatch('activity-notifications-updated')
        ->assertDispatched('student-activities-count', count: 1)
        ->assertSeeHtml('data-student-activity-id="'.$activit->id.'"')
        ->assertSeeHtml('data-activity-id="'.$activit->id.'"')
        ->assertSeeHtml('data-discipline-id="'.$discipline->id.'"')
        ->assertSeeHtml('data-activity-title="'.$activit->title.'"')
        ->call('openActivity', $activit->id)
        ->assertRedirect(ActivityNotificationService::studentUrl($activit));
});

it('atualiza a lista e a contagem ao receber eventos em tempo real', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $module, $first] = makeStudentActivitiesContext($teacher);
    $second = makeStudentActivity($teacher, $discipline, $module, 'Segunda atividade');
    $secondTask = Task::factory()->forActivitTask($second)->create(['type' => 'dissertative']);

    $student = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);

    $this->actingAs($student, 'student');

    $component = Livewire::test(StudentActivities::class, ['studentId' => $student->id])
        ->assertSee($first->title)
        ->assertSee('Segunda atividade');

    StudentAnwser::factory()
        ->forTask($secondTask, $second)
        ->create([
            'student_id' => $student->id,
            'answer_text' => 'Resposta enviada',
        ]);

    $component
        ->call('handleRealtime', ['action' => 'removed', 'type' => ActivityNotification::TYPE_PUBLISHED])
        ->assertDontSee('Segunda atividade')
        ->assertDispatched('student-activities-count', count: 1)
        ->assertNotDispatched('student-activities:new');

    $third = makeStudentActivity($teacher, $discipline, $module, 'Terceira atividade');

    $component
        ->call('handleRealtime', ['action' => 'created', 'type' => ActivityNotification::TYPE_PUBLISHED])
        ->assertSee('Terceira atividade')
        ->assertDispatched('student-activities:new')
        ->assertDispatched('student-activities-count', count: 2)
        ->assertSeeHtml('data-student-activity-id="'.$third->id.'"');
});

it('remove da lista atividades já respondidas no polling', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeStudentActivitiesContext($teacher);
    $student = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);
    $task = Task::factory()->forActivitTask($activit)->create(['type' => 'dissertative']);

    StudentAnwser::factory()
        ->forTask($task, $activit)
        ->create([
            'student_id' => $student->id,
            'answer_text' => 'Resposta enviada',
        ]);

    $this->actingAs($student, 'student');

    $component = Livewire::test(StudentActivities::class, ['studentId' => $student->id])
        ->call('pollActivities')
        ->assertDontSee($activit->title)
        ->assertDispatched('student-activities-count', count: 0);

    expect($component->instance()->pendingCount)->toBe(0);
});
