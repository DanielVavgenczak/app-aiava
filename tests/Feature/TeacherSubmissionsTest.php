<?php

use App\Livewire\Global\TeacherSubmissions;
use App\Models\Module;
use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\ActivityNotification;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Student;
use App\Models\Teacher\Teacher;
use App\Support\ActivityNotificationService;
use Livewire\Livewire;

function makeTeacherSubmissionsContext(Teacher $teacher): array
{
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);
    $activit = ActivitTask::factory()->forDiscipline($discipline, $module, $teacher)->create();
    $activit->update(['title' => 'Atividade pendente']);

    return [$discipline, $activit];
}

function makeTeacherSubmission(
    Teacher $teacher,
    ActivitTask $activit,
    Student $student,
    string $type = ActivityNotification::TYPE_DELIVERED
): ActivityNotification {
    return ActivityNotification::create([
        'notifiable_type' => Teacher::class,
        'notifiable_id' => $teacher->id,
        'dedupe_key' => 'test-'.$teacher->id.'-'.$activit->id.'-'.$student->id.'-'.$type,
        'type' => $type,
        'activit_task_id' => $activit->id,
        'student_id' => $student->id,
    ]);
}

it('lista apenas entregas pendentes do professor autenticado', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit] = makeTeacherSubmissionsContext($teacher);

    $pending = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'full_name' => 'Aluno Pendente',
    ]);
    $published = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'full_name' => 'Aluno Publicado',
    ]);

    makeTeacherSubmission($teacher, $activit, $pending);

    makeTeacherSubmission($teacher, $activit, $published, ActivityNotification::TYPE_PUBLISHED);

    $this->actingAs($teacher, 'teacher');

    $component = Livewire::test(TeacherSubmissions::class, ['teacherId' => $teacher->id])
        ->assertSee('Aluno Pendente')
        ->assertSee('Atividade pendente')
        ->assertDontSee('Aluno Publicado');

    expect($component->instance()->pendingCount)->toBe(1);
});

it('ignora notificações de outro professor', function () {
    $teacher = Teacher::factory()->create();
    $otherTeacher = Teacher::factory()->create();
    [, $activit] = makeTeacherSubmissionsContext($otherTeacher);
    $student = Student::factory()->create([
        'teacher_id' => $otherTeacher->id,
        'discipline_id' => $activit->discipline_id,
        'full_name' => 'Aluno de Outro Professor',
    ]);

    makeTeacherSubmission($otherTeacher, $activit, $student);

    $this->actingAs($teacher, 'teacher');

    Livewire::test(TeacherSubmissions::class, ['teacherId' => $teacher->id])
        ->assertDontSee('Aluno de Outro Professor')
        ->assertSee('Tudo em dia');
});

it('atualiza a contagem e redireciona para a aba de entregas', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit] = makeTeacherSubmissionsContext($teacher);
    $student = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);
    $notification = makeTeacherSubmission($teacher, $activit, $student);

    $this->actingAs($teacher, 'teacher');

    Livewire::test(TeacherSubmissions::class, ['teacherId' => $teacher->id])
        ->dispatch('activity-notifications-updated')
        ->assertDispatched('teacher-submissions-count', count: 1)
        ->call('openSubmission', $notification->id)
        ->assertRedirect(ActivityNotificationService::teacherUrl($activit));
});

it('atualiza a lista ao receber eventos em tempo real', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit] = makeTeacherSubmissionsContext($teacher);
    $first = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'full_name' => 'Aluno Primeiro',
    ]);
    $second = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'full_name' => 'Aluno Segundo',
    ]);

    makeTeacherSubmission($teacher, $activit, $first);

    $this->actingAs($teacher, 'teacher');

    $component = Livewire::test(TeacherSubmissions::class, ['teacherId' => $teacher->id])
        ->assertSee('Aluno Primeiro')
        ->assertDontSee('Aluno Segundo');

    $newNotification = makeTeacherSubmission($teacher, $activit, $second);

    $component
        ->call('handleRealtime', ['action' => 'created', 'type' => ActivityNotification::TYPE_DELIVERED])
        ->assertSee('Aluno Segundo')
        ->assertDispatched('teacher-submissions:new')
        ->assertDispatched('teacher-submissions-count', count: 2);

    $newNotification->delete();

    $component
        ->call('handleRealtime', ['action' => 'removed', 'type' => ActivityNotification::TYPE_DELIVERED])
        ->assertDontSee('Aluno Segundo')
        ->assertDispatched('teacher-submissions-count', count: 1);
});
