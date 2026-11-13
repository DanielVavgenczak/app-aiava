<?php

use App\Livewire\Global\StudentActivities;
use App\Models\Module;
use App\Models\Teacher\ActivityGrade;
use App\Models\Teacher\ActivityNotification;
use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\QuestionFeedback;
use App\Models\Teacher\Student;
use App\Models\Teacher\StudentAnwser;
use App\Models\Teacher\Task;
use App\Models\Teacher\Teacher;
use App\Support\ActivityNotificationService;
use Livewire\Livewire;

function makeReviewedActivitiesTask(Teacher $teacher, Discipline $discipline, Module $module, string $title, array $attributes = []): ActivitTask
{
    $activity = ActivitTask::factory()
        ->forDiscipline($discipline, $module, $teacher)
        ->create($attributes);

    $activity->update(['title' => $title]);

    return $activity;
}

function makeReviewedActivitiesAnswer(ActivitTask $activity, Student $student, bool $corrected = false): array
{
    $task = Task::factory()->forActivitTask($activity)->create(['type' => 'dissertative']);
    $answer = StudentAnwser::factory()
        ->forTask($task, $activity)
        ->create([
            'student_id' => $student->id,
            'answer_text' => 'Resposta enviada',
            'corrected' => $corrected,
            'grade' => $corrected ? 8 : null,
        ]);

    return [$task, $answer];
}

it('lista somente atividades enviadas e corrigidas do aluno autenticado', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);
    $student = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);

    $pending = makeReviewedActivitiesTask($teacher, $discipline, $module, 'Atividade ainda pendente');
    $submitted = makeReviewedActivitiesTask($teacher, $discipline, $module, 'Atividade apenas enviada');
    makeReviewedActivitiesAnswer($submitted, $student);

    $corrected = makeReviewedActivitiesTask($teacher, $discipline, $module, 'Atividade corrigida');
    makeReviewedActivitiesAnswer($corrected, $student, corrected: true);

    $withFeedback = makeReviewedActivitiesTask($teacher, $discipline, $module, 'Atividade com feedback');
    [$feedbackTask, $feedbackAnswer] = makeReviewedActivitiesAnswer($withFeedback, $student);
    QuestionFeedback::create([
        'feedback' => 'Bom trabalho',
        'student_anwser_id' => $feedbackAnswer->id,
        'task_id' => $feedbackTask->id,
        'student_id' => $student->id,
        'teacher_id' => $teacher->id,
        'registered_at' => now(),
    ]);

    $withFinalGrade = makeReviewedActivitiesTask($teacher, $discipline, $module, 'Atividade com nota final');
    makeReviewedActivitiesAnswer($withFinalGrade, $student);
    ActivityGrade::create([
        'activit_task_id' => $withFinalGrade->id,
        'student_id' => $student->id,
        'teacher_id' => $teacher->id,
        'final_grade' => 7,
        'feedback' => 'Nota final registrada',
        'registered_at' => now(),
    ]);

    $otherDiscipline = Discipline::factory()->for($teacher)->create();
    $otherModule = Module::factory()->create(['discipline_id' => $otherDiscipline->id]);
    $otherDisciplineActivity = makeReviewedActivitiesTask($teacher, $otherDiscipline, $otherModule, 'Atividade de outra disciplina');
    makeReviewedActivitiesAnswer($otherDisciplineActivity, $student, corrected: true);

    $otherStudent = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);
    $otherStudentActivity = makeReviewedActivitiesTask($teacher, $discipline, $module, 'Atividade de outro aluno');
    makeReviewedActivitiesAnswer($otherStudentActivity, $otherStudent, corrected: true);

    $this->actingAs($student, 'student');

    $component = Livewire::test(StudentActivities::class, ['studentId' => $student->id])
        ->assertSee($corrected->title)
        ->assertSee($withFeedback->title)
        ->assertSee($withFinalGrade->title)
        ->assertDontSee($submitted->title)
        ->assertDontSee($otherDisciplineActivity->title)
        ->assertSee('Corrigidas')
        ->assertSeeHtml('data-activities-tab="reviewed"')
        ->assertSeeHtml('data-student-reviewed-activity-id="'.$corrected->id.'"')
        ->assertSeeHtml('data-student-reviewed-activity-id="'.$withFeedback->id.'"')
        ->assertSeeHtml('data-student-reviewed-activity-id="'.$withFinalGrade->id.'"')
        ->assertDontSeeHtml('data-student-reviewed-activity-id="'.$pending->id.'"')
        ->assertDontSeeHtml('data-student-reviewed-activity-id="'.$submitted->id.'"')
        ->assertDontSeeHtml('data-student-reviewed-activity-id="'.$otherDisciplineActivity->id.'"')
        ->assertDontSeeHtml('data-student-reviewed-activity-id="'.$otherStudentActivity->id.'"');

    expect($component->instance()->reviewedCount)->toBe(3);
});

it('não expõe nem abre atividade corrigida de outro aluno', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);
    $student = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);
    $otherStudent = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);

    $activity = makeReviewedActivitiesTask($teacher, $discipline, $module, 'Atividade corrigida do outro aluno');
    makeReviewedActivitiesAnswer($activity, $student);
    makeReviewedActivitiesAnswer($activity, $otherStudent, corrected: true);

    $this->actingAs($student, 'student');

    $component = Livewire::test(StudentActivities::class, ['studentId' => $student->id])
        ->assertDontSee($activity->title)
        ->assertDontSeeHtml('data-student-reviewed-activity-id="'.$activity->id.'"')
        ->call('openReviewedActivity', $activity->id)
        ->assertNoRedirect();

    expect($component->instance()->reviewedCount)->toBe(0);
});

it('atualiza a lista ao receber a correção em tempo real', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);
    $student = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);

    $activity = makeReviewedActivitiesTask($teacher, $discipline, $module, 'Atividade corrigida em tempo real');
    [, $answer] = makeReviewedActivitiesAnswer($activity, $student);

    $this->actingAs($student, 'student');

    $component = Livewire::test(StudentActivities::class, ['studentId' => $student->id])
        ->assertDontSee($activity->title);

    expect($component->instance()->reviewedCount)->toBe(0);

    $answer->update([
        'corrected' => true,
        'grade' => 9,
    ]);

    $component
        ->call('handleRealtime', ['action' => 'created', 'type' => ActivityNotification::TYPE_REVIEWED])
        ->assertSee($activity->title)
        ->assertSeeHtml('data-student-reviewed-activity-id="'.$activity->id.'"')
        ->assertDispatched('student-activities:reviewed')
        ->assertDispatched('student-activities-reviewed-count', count: 1)
        ->call('handleRealtime', ['action' => 'removed', 'type' => ActivityNotification::TYPE_REVIEWED])
        ->assertSee($activity->title)
        ->assertNotDispatched('student-activities:reviewed')
        ->call('openReviewedActivity', $activity->id)
        ->assertRedirect(ActivityNotificationService::studentUrl($activity));

    expect($component->instance()->reviewedCount)->toBe(1);
});
