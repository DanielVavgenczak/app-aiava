<?php

use App\Livewire\Global\TeacherSubmissions;
use App\Models\Module;
use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\ActivityNotification;
use App\Models\Teacher\Classe;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Student;
use App\Models\Teacher\StudentAnwser;
use App\Models\Teacher\Task;
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

    $pendingClasse = Classe::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'name' => 'Turma Enviadas',
    ]);
    $pending = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'classe_id' => $pendingClasse->id,
        'full_name' => 'Aluno Pendente',
    ]);
    $published = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'full_name' => 'Aluno Publicado',
    ]);

    $submission = makeTeacherSubmission($teacher, $activit, $pending);

    makeTeacherSubmission($teacher, $activit, $published, ActivityNotification::TYPE_PUBLISHED);

    $this->actingAs($teacher, 'teacher');

    $component = Livewire::test(TeacherSubmissions::class, ['teacherId' => $teacher->id])
        ->assertSee('Enviadas')
        ->assertSee('Não enviadas')
        ->assertSee('Aluno Pendente')
        ->assertSee('Atividade pendente')
        ->assertSee('Turma Enviadas')
        ->assertSee('Enviado em '.now()->format('d/m/Y'))
        ->assertDontSee('Aguardando correção')
        ->assertSeeHtml('data-submission-id="'.$submission->id.'"')
        ->assertSeeHtml('data-submission-classe="Turma Enviadas"')
        ->assertDontSeeHtml('data-student-id="'.$published->id.'"')
        ->assertSeeHtml('data-missing-student-id="'.$published->id.'"');

    expect($component->instance()->pendingCount)->toBe(1);
});

it('não exibe o uuid do título automático nas listas', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);
    $activit = ActivitTask::factory()->forDiscipline($discipline, $module, $teacher)->create();

    expect($activit->title)->toStartWith('atividade ');

    $submitted = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'full_name' => 'Aluno Com Envio',
    ]);
    Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'full_name' => 'Aluno Sem Envio',
    ]);

    makeTeacherSubmission($teacher, $activit, $submitted);

    $this->actingAs($teacher, 'teacher');

    $component = Livewire::test(TeacherSubmissions::class, ['teacherId' => $teacher->id])
        ->assertDontSee($activit->title);

    expect($component->instance()->submissions[0]['activity'])->toBe('Atividade')
        ->and(collect($component->instance()->missingSubmissions)->pluck('activity')->unique()->all())->toBe(['Atividade']);
});

it('lista apenas alunos ativos sem envio nas atividades com entregas', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $activit] = makeTeacherSubmissionsContext($teacher);
    $deadline = now()->addWeek();
    $activit->update(['end' => $deadline]);
    $task = Task::factory()->forActivitTask($activit)->create();

    $submitted = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'full_name' => 'Aluno Que Enviou',
    ]);
    $classe = Classe::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'name' => 'Turma 9A',
    ]);
    $missing = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'classe_id' => $classe->id,
        'full_name' => 'Aluno Sem Envio',
    ]);
    $inactive = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'full_name' => 'Aluno Inativo',
        'status' => 'inactive',
    ]);
    $answered = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'full_name' => 'Aluno Já Respondeu',
    ]);
    $otherDiscipline = Discipline::factory()->for($teacher)->create();
    $otherStudent = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $otherDiscipline->id,
        'full_name' => 'Aluno De Outra Disciplina',
    ]);

    makeTeacherSubmission($teacher, $activit, $submitted);

    StudentAnwser::factory()->forTask($task, $activit)->create([
        'student_id' => $answered->id,
    ]);

    $this->actingAs($teacher, 'teacher');

    Livewire::test(TeacherSubmissions::class, ['teacherId' => $teacher->id])
        ->assertSeeHtml('data-missing-student-id="'.$missing->id.'"')
        ->assertSeeHtml('data-missing-classe="Turma 9A"')
        ->assertSee('Aluno Sem Envio')
        ->assertSee('Turma 9A')
        ->assertSee('Não enviou')
        ->assertSee('Prazo: '.$deadline->format('d/m/Y'))
        ->assertDontSee('Aguardando envio')
        ->assertSeeHtml('data-missing-alert')
        ->assertDontSeeHtml('data-missing-student-id="'.$inactive->id.'"')
        ->assertDontSeeHtml('data-missing-student-id="'.$answered->id.'"')
        ->assertDontSeeHtml('data-missing-student-id="'.$otherStudent->id.'"');
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
        ->assertSeeHtml('data-submission-id="'.$notification->id.'"')
        ->assertSeeHtml('data-student-id="'.$student->id.'"')
        ->assertSeeHtml('data-discipline-id="'.$discipline->id.'"')
        ->assertSeeHtml('data-activity-id="'.$activit->id.'"')
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

    $firstNotification = makeTeacherSubmission($teacher, $activit, $first);

    $this->actingAs($teacher, 'teacher');

    $component = Livewire::test(TeacherSubmissions::class, ['teacherId' => $teacher->id])
        ->assertSeeHtml('data-submission-id="'.$firstNotification->id.'"')
        ->assertDontSeeHtml('data-student-id="'.$second->id.'"')
        ->assertSeeHtml('data-missing-student-id="'.$second->id.'"');

    $newNotification = makeTeacherSubmission($teacher, $activit, $second);

    $component
        ->call('handleRealtime', ['action' => 'created', 'type' => ActivityNotification::TYPE_DELIVERED])
        ->assertSeeHtml('data-submission-id="'.$newNotification->id.'"')
        ->assertDontSeeHtml('data-missing-student-id="'.$second->id.'"')
        ->assertDispatched('teacher-submissions:new')
        ->assertDispatched('teacher-submissions-count', count: 2);

    $newNotification->delete();

    $component
        ->call('handleRealtime', ['action' => 'removed', 'type' => ActivityNotification::TYPE_DELIVERED])
        ->assertDontSeeHtml('data-submission-id="'.$newNotification->id.'"')
        ->assertSeeHtml('data-missing-student-id="'.$second->id.'"')
        ->assertDispatched('teacher-submissions-count', count: 1);
});
