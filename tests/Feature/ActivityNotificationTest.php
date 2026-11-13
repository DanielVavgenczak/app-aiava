<?php

use App\Events\ActivityNotificationUpdated;
use App\Models\Module;
use App\Models\Teacher\ActivityGrade;
use App\Models\Teacher\ActivityNotification;
use App\Models\Teacher\ActivitTask;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Student;
use App\Models\Teacher\StudentAnwser;
use App\Models\Teacher\Task;
use App\Models\Teacher\Teacher;
use App\Support\ActivityNotificationService;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

function makeNotificationContext(Teacher $teacher): array
{
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);
    $activit = ActivitTask::factory()->forDiscipline($discipline, $module, $teacher)->create();

    return [$discipline, $module, $activit];
}

function makeNotificationStudent(Teacher $teacher, Discipline $discipline, string $status = 'active'): Student
{
    return Student::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'status' => $status,
    ]);
}

function makeNotificationTask(ActivitTask $activit, Student $student, bool $corrected = false, string $type = 'text'): StudentAnwser
{
    $task = Task::factory()->forActivitTask($activit)->create(['type' => $type]);

    return StudentAnwser::factory()
        ->forTask($task, $activit)
        ->create([
            'student_id' => $student->id,
            'corrected' => $corrected,
        ]);
}

function makeNotificationCreateComponent(Teacher $teacher): array
{
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);

    $component = Livewire::test('pages::teacher.discipline.module.modals.create-task', [
        'lessons' => collect([$module]),
        'disciplineId' => $discipline->id,
    ]);

    return [$component, $discipline, $module];
}

it('cria notificação de publicação para alunos ativos da disciplina', function () {
    Event::fake([ActivityNotificationUpdated::class]);

    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);

    $active = makeNotificationStudent($teacher, $discipline);
    $inactive = makeNotificationStudent($teacher, $discipline, 'inactive');
    $other = Student::factory()->create();

    ActivityNotificationService::publish($activit);

    expect(ActivityNotification::where('notifiable_id', $active->id)->where('type', 'published')->exists())->toBeTrue()
        ->and(ActivityNotification::where('notifiable_id', $inactive->id)->exists())->toBeFalse()
        ->and(ActivityNotification::where('notifiable_id', $other->id)->exists())->toBeFalse();

    Event::assertDispatched(
        ActivityNotificationUpdated::class,
        fn (ActivityNotificationUpdated $event) => $event->notifiableId === (string) $active->id
            && $event->action === 'created'
    );
});

it('não duplica notificações ao publicar mais de uma vez e ignora quem já respondeu', function () {
    Event::fake([ActivityNotificationUpdated::class]);

    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);

    $pending = makeNotificationStudent($teacher, $discipline);
    $answered = makeNotificationStudent($teacher, $discipline);
    makeNotificationTask($activit, $answered);

    ActivityNotificationService::publish($activit);
    ActivityNotificationService::publish($activit);

    expect(ActivityNotification::where('activit_task_id', $activit->id)->count())->toBe(1)
        ->and(ActivityNotification::where('notifiable_id', $pending->id)->exists())->toBeTrue()
        ->and(ActivityNotification::where('notifiable_id', $answered->id)->exists())->toBeFalse();
});

it('não publica notificação para atividade já encerrada', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);
    $student = makeNotificationStudent($teacher, $discipline);

    $activit->update(['end' => now()->subDay()]);

    ActivityNotificationService::publish($activit);

    expect(ActivityNotification::where('notifiable_id', $student->id)->exists())->toBeFalse();
});

it('remove a notificação do aluno e cria a do professor ao responder', function () {
    Event::fake([ActivityNotificationUpdated::class]);

    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);
    $student = makeNotificationStudent($teacher, $discipline);

    ActivityNotificationService::publish($activit);
    expect(ActivityNotification::where('notifiable_id', $student->id)->exists())->toBeTrue();

    ActivityNotificationService::activityAnswered($activit, $student);

    $teacherNotification = ActivityNotification::query()
        ->where('notifiable_type', Teacher::class)
        ->where('notifiable_id', $teacher->id)
        ->where('type', 'delivered')
        ->first();

    expect(ActivityNotification::where('notifiable_id', $student->id)->exists())->toBeFalse()
        ->and($teacherNotification)->not->toBeNull()
        ->and($teacherNotification->student_id)->toBe($student->id);

    Event::assertDispatched(
        ActivityNotificationUpdated::class,
        fn (ActivityNotificationUpdated $event) => $event->notifiableId === (string) $teacher->id
            && $event->action === 'created'
    );
});

it('não duplica a notificação do professor em reenvios', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);
    $student = makeNotificationStudent($teacher, $discipline);

    ActivityNotificationService::activityAnswered($activit, $student);
    ActivityNotificationService::activityAnswered($activit, $student);

    expect(ActivityNotification::query()
        ->where('notifiable_type', Teacher::class)
        ->where('notifiable_id', $teacher->id)
        ->where('student_id', $student->id)
        ->count())->toBe(1);
});

it('mantém a notificação do professor enquanto houver questão pendente', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);
    $student = makeNotificationStudent($teacher, $discipline);

    $first = makeNotificationTask($activit, $student);
    $second = makeNotificationTask($activit, $student);

    ActivityNotificationService::activityAnswered($activit, $student);

    $first->update(['corrected' => true]);
    ActivityNotificationService::correctionCompleted($activit, $student->id);

    expect(ActivityNotification::query()
        ->where('notifiable_type', Teacher::class)
        ->where('notifiable_id', $teacher->id)
        ->where('student_id', $student->id)
        ->exists())->toBeTrue()
        ->and(ActivityNotification::query()
            ->where('notifiable_type', Student::class)
            ->where('notifiable_id', $student->id)
            ->where('type', ActivityNotification::TYPE_REVIEWED)
            ->exists())->toBeFalse();

    $second->update(['corrected' => true]);
    ActivityNotificationService::correctionCompleted($activit, $student->id);

    expect(ActivityNotification::query()
        ->where('notifiable_type', Teacher::class)
        ->where('notifiable_id', $teacher->id)
        ->where('student_id', $student->id)
        ->exists())->toBeFalse()
        ->and(ActivityNotification::query()
            ->where('notifiable_type', Student::class)
            ->where('notifiable_id', $student->id)
            ->where('type', ActivityNotification::TYPE_REVIEWED)
            ->exists())->toBeTrue();
});

it('notifica o aluno quando a atividade é corrigida', function () {
    Event::fake([ActivityNotificationUpdated::class]);

    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);
    $student = makeNotificationStudent($teacher, $discipline);

    makeNotificationTask($activit, $student, corrected: true);
    ActivityNotificationService::activityAnswered($activit, $student);

    ActivityNotificationService::correctionCompleted($activit, $student->id);

    $notification = ActivityNotification::query()
        ->where('notifiable_type', Student::class)
        ->where('notifiable_id', $student->id)
        ->where('type', ActivityNotification::TYPE_REVIEWED)
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->activit_task_id)->toBe($activit->id)
        ->and($notification->student_id)->toBe($student->id);

    Event::assertDispatched(
        ActivityNotificationUpdated::class,
        fn (ActivityNotificationUpdated $event) => $event->notifiableId === (string) $student->id
            && $event->action === 'created'
            && $event->type === ActivityNotification::TYPE_REVIEWED
            && $event->toast['kind'] === ActivityNotification::TYPE_REVIEWED
            && $event->toast['title'] === 'Atividade corrigida'
            && str_contains($event->toast['description'], $activit->title)
            && $event->toast['url'] === ActivityNotificationService::studentUrl($activit)
    );
});

it('notifica apenas o aluno corrigido quando há mais de um aluno na atividade', function () {
    Event::fake([ActivityNotificationUpdated::class]);

    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);
    $reviewedStudent = makeNotificationStudent($teacher, $discipline);
    $otherStudent = makeNotificationStudent($teacher, $discipline);

    makeNotificationTask($activit, $reviewedStudent, corrected: true);
    makeNotificationTask($activit, $otherStudent, corrected: true);
    ActivityNotificationService::activityAnswered($activit, $reviewedStudent);
    ActivityNotificationService::activityAnswered($activit, $otherStudent);

    ActivityNotificationService::correctionCompleted($activit, $reviewedStudent->id);

    expect(ActivityNotification::query()
        ->where('notifiable_type', Student::class)
        ->where('notifiable_id', $reviewedStudent->id)
        ->where('type', ActivityNotification::TYPE_REVIEWED)
        ->exists())->toBeTrue()
        ->and(ActivityNotification::query()
            ->where('notifiable_type', Student::class)
            ->where('notifiable_id', $otherStudent->id)
            ->where('type', ActivityNotification::TYPE_REVIEWED)
            ->exists())->toBeFalse();

    Event::assertDispatched(
        ActivityNotificationUpdated::class,
        fn (ActivityNotificationUpdated $event) => $event->notifiableId === (string) $reviewedStudent->id
            && $event->action === 'created'
            && $event->type === ActivityNotification::TYPE_REVIEWED
    );

    Event::assertNotDispatched(
        ActivityNotificationUpdated::class,
        fn (ActivityNotificationUpdated $event) => $event->notifiableId === (string) $otherStudent->id
            && $event->type === ActivityNotification::TYPE_REVIEWED
    );
});

it('não duplica a notificação de atividade corrigida', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);
    $student = makeNotificationStudent($teacher, $discipline);

    makeNotificationTask($activit, $student, corrected: true);
    ActivityNotificationService::activityAnswered($activit, $student);

    ActivityNotificationService::correctionCompleted($activit, $student->id);
    ActivityNotificationService::correctionCompleted($activit, $student->id);

    expect(ActivityNotification::query()
        ->where('notifiable_type', Student::class)
        ->where('notifiable_id', $student->id)
        ->where('type', ActivityNotification::TYPE_REVIEWED)
        ->count())->toBe(1);
});

it('remove a notificação do professor ao salvar a nota final', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);
    $student = makeNotificationStudent($teacher, $discipline);

    makeNotificationTask($activit, $student);
    ActivityNotificationService::activityAnswered($activit, $student);

    ActivityGrade::create([
        'activit_task_id' => $activit->id,
        'student_id' => $student->id,
        'teacher_id' => $teacher->id,
        'final_grade' => 10,
        'registered_at' => now(),
    ]);

    ActivityNotificationService::correctionCompleted($activit, $student->id);

    expect(ActivityNotification::query()
        ->where('notifiable_type', Teacher::class)
        ->where('notifiable_id', $teacher->id)
        ->where('student_id', $student->id)
        ->exists())->toBeFalse()
        ->and(ActivityNotification::query()
            ->where('notifiable_type', Student::class)
            ->where('notifiable_id', $student->id)
            ->where('type', ActivityNotification::TYPE_REVIEWED)
            ->exists())->toBeTrue();
});

it('remove todas as notificações ao deletar a atividade', function () {
    Event::fake([ActivityNotificationUpdated::class]);

    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);
    $student = makeNotificationStudent($teacher, $discipline);

    ActivityNotificationService::publish($activit);
    ActivityNotificationService::activityAnswered($activit, $student);

    ActivityNotificationService::forgetActivity($activit);

    expect(ActivityNotification::where('activit_task_id', $activit->id)->count())->toBe(0);

    Event::assertDispatched(
        ActivityNotificationUpdated::class,
        fn (ActivityNotificationUpdated $event) => $event->action === 'removed'
    );
});

it('limpa notificações de aluno já respondidas ou de atividades encerradas no polling', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);
    $student = makeNotificationStudent($teacher, $discipline);

    ActivityNotificationService::publish($activit);

    $closed = ActivitTask::factory()->forDiscipline($discipline, Module::factory()->create(['discipline_id' => $discipline->id]), $teacher)->create([
        'end' => now()->subDay(),
    ]);
    ActivityNotification::create([
        'notifiable_type' => Student::class,
        'notifiable_id' => $student->id,
        'dedupe_key' => 'closed-'.$closed->id,
        'type' => 'published',
        'activit_task_id' => $closed->id,
        'student_id' => $student->id,
    ]);

    makeNotificationTask($activit, $student);

    $this->actingAs($student, 'student');

    Livewire::test('global.notification-bell', [
        'userType' => 'student',
        'notifiableId' => $student->id,
    ])->call('pollNotifications');

    expect(ActivityNotification::where('notifiable_id', $student->id)->count())->toBe(0);
});

it('bell exibe apenas as notificações do usuário autenticado', function () {
    $teacher = Teacher::factory()->create();
    [$disciplineA, , $activitA] = makeNotificationContext($teacher);
    [$disciplineB, , $activitB] = makeNotificationContext($teacher);

    $studentA = makeNotificationStudent($teacher, $disciplineA);
    $studentB = makeNotificationStudent($teacher, $disciplineB);

    ActivityNotification::create([
        'notifiable_type' => Student::class,
        'notifiable_id' => $studentA->id,
        'dedupe_key' => 'a-'.$activitA->id,
        'type' => 'published',
        'activit_task_id' => $activitA->id,
        'student_id' => $studentA->id,
    ]);
    ActivityNotification::create([
        'notifiable_type' => Student::class,
        'notifiable_id' => $studentB->id,
        'dedupe_key' => 'b-'.$activitB->id,
        'type' => 'published',
        'activit_task_id' => $activitB->id,
        'student_id' => $studentB->id,
    ]);

    $this->actingAs($studentA, 'student');

    Livewire::test('global.notification-bell', [
        'userType' => 'student',
        'notifiableId' => $studentA->id,
    ])
        ->assertSee('Nova atividade')
        ->assertSee($activitA->title)
        ->assertDontSee($activitB->title)
        ->assertSee('1 pendente');
});

it('bell exibe a entrega para o professor', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);
    $student = makeNotificationStudent($teacher, $discipline);

    makeNotificationTask($activit, $student);
    ActivityNotificationService::activityAnswered($activit, $student);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('global.notification-bell', [
        'userType' => 'teacher',
        'notifiableId' => $teacher->id,
    ])
        ->assertSee('Nova entrega')
        ->assertSee($student->full_name)
        ->assertSee('1 pendente');
});

it('bell redireciona o aluno para a atividade destacada', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);
    $student = makeNotificationStudent($teacher, $discipline);

    $notification = ActivityNotification::create([
        'notifiable_type' => Student::class,
        'notifiable_id' => $student->id,
        'dedupe_key' => 'redirect-'.$activit->id,
        'type' => 'published',
        'activit_task_id' => $activit->id,
        'student_id' => $student->id,
    ]);

    $this->actingAs($student, 'student');

    $url = route('student.lessons', [
        'discipline_id' => $discipline->id,
        'activity' => $activit->id,
    ]).'#open-activity-'.$activit->id;

    Livewire::test('global.notification-bell', [
        'userType' => 'student',
        'notifiableId' => $student->id,
    ])
        ->call('openNotification', $notification->id)
        ->assertRedirect($url);
});

it('bell exibe e redireciona a notificação de atividade corrigida', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);
    $student = makeNotificationStudent($teacher, $discipline);

    $notification = ActivityNotification::create([
        'notifiable_type' => Student::class,
        'notifiable_id' => $student->id,
        'dedupe_key' => 'reviewed-'.$activit->id.'-'.$student->id,
        'type' => ActivityNotification::TYPE_REVIEWED,
        'activit_task_id' => $activit->id,
        'student_id' => $student->id,
    ]);

    $this->actingAs($student, 'student');

    Livewire::test('global.notification-bell', [
        'userType' => 'student',
        'notifiableId' => $student->id,
    ])
        ->assertSee('Atividade corrigida')
        ->assertSee($activit->title)
        ->assertSee('1 pendente')
        ->call('openNotification', $notification->id)
        ->assertRedirect(ActivityNotificationService::studentUrl($activit));
});

it('bell redireciona o professor para a aba de entregas', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);
    $student = makeNotificationStudent($teacher, $discipline);

    makeNotificationTask($activit, $student);
    ActivityNotificationService::activityAnswered($activit, $student);

    $notification = ActivityNotification::query()
        ->where('notifiable_type', Teacher::class)
        ->where('notifiable_id', $teacher->id)
        ->first();

    $this->actingAs($teacher, 'teacher');

    $url = route('teacher.discipline.show', [
        'id' => $discipline->id,
        'activity' => $activit->id,
        'tab' => 'deliveries-tab',
    ]);

    Livewire::test('global.notification-bell', [
        'userType' => 'teacher',
        'notifiableId' => $teacher->id,
    ])
        ->call('openNotification', $notification->id)
        ->assertRedirect($url);
});

it('abre a atividade e a aba correta no deep link do professor', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);

    $this->actingAs($teacher, 'teacher');

    Livewire::withQueryParams([
        'activity' => $activit->id,
        'tab' => 'deliveries-tab',
    ])->test('pages::teacher.discipline.show', ['id' => $discipline->id])
        ->assertSet('activity', $activit->id)
        ->assertSet('tab', 'deliveries-tab')
        ->assertSet('openedTaskShow', true);
});

it('expande o módulo da atividade no deep link do aluno', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, $module, $activit] = makeNotificationContext($teacher);
    $student = makeNotificationStudent($teacher, $discipline);

    $this->actingAs($student, 'student');

    Livewire::withQueryParams(['activity' => $activit->id])
        ->test('pages::student.discipline.show', ['discipline_id' => $discipline->id])
        ->assertSet('activity', $activit->id)
        ->assertSet('highlightModuleId', $module->id);
});

it('mostra a disponibilidade futura e bloqueia a atividade na lista do aluno', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);
    $student = makeNotificationStudent($teacher, $discipline);

    $activit->update([
        'start' => now()->addDay(),
        'end' => now()->addDays(2),
    ]);

    $this->actingAs($student, 'student');

    Livewire::test('pages::student.discipline.show', ['discipline_id' => $discipline->id])
        ->assertSee('Disponível em '.$activit->start->format('d/m/Y H:i'))
        ->assertSeeHtml('cursor-not-allowed');
});

it('publica notificações ao criar atividade pelo formulário', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');

    [$component, $discipline, $module] = makeNotificationCreateComponent($teacher);
    $student = makeNotificationStudent($teacher, $discipline);

    $component
        ->set('isAvaliative', false)
        ->set('titleOfQuestion', 'Pergunta da atividade')
        ->set('questionTypeSelected', 'text')
        ->call('addQuestion')
        ->assertHasNoErrors()
        ->set('moduleId', $module->id)
        ->call('save')
        ->assertHasNoErrors();

    $activit = ActivitTask::where('discipline_id', $discipline->id)->first();

    expect($activit)->not->toBeNull()
        ->and(ActivityNotification::where('activit_task_id', $activit->id)
            ->where('notifiable_id', $student->id)
            ->where('type', 'published')
            ->exists())->toBeTrue();
});

it('notifica o professor quando o aluno envia respostas', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);
    $student = makeNotificationStudent($teacher, $discipline);
    $task = Task::factory()->forActivitTask($activit)->create(['type' => 'text']);

    ActivityNotificationService::publish($activit);

    $this->actingAs($student, 'student');

    Livewire::test('pages::student.discipline.lesson.show-task-lesson')
        ->dispatch('student-event-open-task', id: $activit->id)
        ->set("dissertativeAnswers.{$task->id}", 'Minha resposta')
        ->call('submitAnswers')
        ->assertHasNoErrors();

    expect(ActivityNotification::query()
        ->where('notifiable_type', Teacher::class)
        ->where('notifiable_id', $teacher->id)
        ->where('student_id', $student->id)
        ->where('type', 'delivered')
        ->exists())->toBeTrue()
        ->and(ActivityNotification::query()
            ->where('notifiable_type', Student::class)
            ->where('notifiable_id', $student->id)
            ->exists())->toBeFalse();
});

it('abre a aba de entregas no show-task pelo deep link', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.show-task', [
        'initialActivityId' => $activit->id,
        'initialTab' => 'deliveries-tab',
    ])
        ->assertSet('selectedTab', 'deliveries-tab')
        ->assertSet('showTaskModule', true);
});

it('faz backfill de notificações ao editar a atividade', function () {
    $teacher = Teacher::factory()->create();
    $this->actingAs($teacher, 'teacher');

    [$discipline, , $activit] = makeNotificationContext($teacher);
    $student = makeNotificationStudent($teacher, $discipline);

    Livewire::test('pages::teacher.discipline.module.modals.edit-task')
        ->dispatch('open-edit-task', id: $activit->id)
        ->set('title', 'Atividade editada')
        ->call('save')
        ->assertHasNoErrors();

    expect(ActivityNotification::where('activit_task_id', $activit->id)
        ->where('notifiable_id', $student->id)
        ->where('type', 'published')
        ->exists())->toBeTrue();
});

it('remove a notificação ao corrigir a última resposta na tela de entregas', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);
    $student = makeNotificationStudent($teacher, $discipline);
    $answer = makeNotificationTask($activit, $student);

    ActivityNotificationService::activityAnswered($activit, $student);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('pages::teacher.discipline.module.task.student-deliveries', ['activityId' => $activit->id])
        ->call('selectStudent', $student->id)
        ->set("feedbacks.{$answer->id}", 'Bom trabalho')
        ->set("grades.{$answer->id}", '10')
        ->call('sendFeedback', $answer->id)
        ->assertHasNoErrors();

    expect(ActivityNotification::query()
        ->where('notifiable_type', Teacher::class)
        ->where('notifiable_id', $teacher->id)
        ->where('student_id', $student->id)
        ->exists())->toBeFalse();
});

it('inclui toast de nova atividade no evento de publicação', function () {
    Event::fake([ActivityNotificationUpdated::class]);

    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);
    $student = makeNotificationStudent($teacher, $discipline);

    $activit->update(['end' => now()->addWeek()]);
    $activit->refresh();

    ActivityNotificationService::publish($activit);

    $notification = ActivityNotification::query()
        ->where('notifiable_id', $student->id)
        ->where('type', ActivityNotification::TYPE_PUBLISHED)
        ->first();

    expect($notification)->not->toBeNull();

    Event::assertDispatched(
        ActivityNotificationUpdated::class,
        fn (ActivityNotificationUpdated $event) => $event->notifiableId === (string) $student->id
            && $event->action === 'created'
            && $event->notificationId === (string) $notification->id
            && $event->dedupeKey === $notification->dedupe_key
            && $event->toast['id'] === (string) $notification->id
            && $event->toast['key'] === $notification->dedupe_key
            && $event->toast['kind'] === 'published'
            && str_contains($event->toast['title'], $teacher->fullName())
            && str_contains($event->toast['description'], $discipline->title)
            && str_contains($event->toast['description'], $activit->title)
            && str_contains($event->toast['description'], 'Entrega até')
            && $event->toast['url'] === ActivityNotificationService::studentUrl($activit)
            && ! array_key_exists('modal', $event->toast)
    );
});

it('sinaliza no toast quando a atividade ainda não começou', function () {
    Event::fake([ActivityNotificationUpdated::class]);

    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);
    makeNotificationStudent($teacher, $discipline);

    $activit->update([
        'start' => now()->addDay(),
        'end' => now()->addDays(2),
    ]);
    $activit->refresh();

    ActivityNotificationService::publish($activit);

    Event::assertDispatched(
        ActivityNotificationUpdated::class,
        fn (ActivityNotificationUpdated $event) => str_contains($event->toast['title'], 'agendada')
            && str_contains($event->toast['description'], 'Disponível a partir de')
            && ! array_key_exists('modal', $event->toast)
    );
});

it('inclui toast de entrega no evento do professor', function () {
    Event::fake([ActivityNotificationUpdated::class]);

    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);
    $student = makeNotificationStudent($teacher, $discipline);

    ActivityNotificationService::activityAnswered($activit, $student);

    $notification = ActivityNotification::query()
        ->where('notifiable_type', Teacher::class)
        ->where('notifiable_id', $teacher->id)
        ->where('student_id', $student->id)
        ->first();

    expect($notification)->not->toBeNull();

    Event::assertDispatched(
        ActivityNotificationUpdated::class,
        fn (ActivityNotificationUpdated $event) => $event->notifiableId === (string) $teacher->id
            && $event->action === 'created'
            && $event->notificationId === (string) $notification->id
            && $event->toast['id'] === (string) $notification->id
            && $event->toast['key'] === $notification->dedupe_key
            && $event->toast['kind'] === 'delivered'
            && str_contains($event->toast['title'], $student->full_name)
            && str_contains($event->toast['description'], $activit->title)
            && str_contains($event->toast['description'], $discipline->title)
            && $event->toast['url'] === ActivityNotificationService::teacherUrl($activit)
    );
});

it('dispara toast apenas para evento realtime de criação válido', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);
    $student = makeNotificationStudent($teacher, $discipline);

    $notification = ActivityNotification::create([
        'notifiable_type' => Student::class,
        'notifiable_id' => $student->id,
        'dedupe_key' => 'toast-'.$activit->id,
        'type' => ActivityNotification::TYPE_PUBLISHED,
        'activit_task_id' => $activit->id,
        'student_id' => $student->id,
    ]);

    $toast = [
        'id' => (string) $notification->id,
        'key' => $notification->dedupe_key,
        'kind' => 'published',
        'title' => 'Nova atividade',
        'description' => 'Descrição',
        'url' => ActivityNotificationService::studentUrl($activit),
        'timeout' => 8000,
    ];

    $this->actingAs($student, 'student');

    $component = Livewire::test('global.notification-bell', [
        'userType' => 'student',
        'notifiableId' => $student->id,
    ])
        ->call('pollNotifications')
        ->assertDispatched('activity-notifications-count')
        ->assertNotDispatched('activity-toast');

    $component
        ->call('handleRealtime', [
            'action' => 'created',
            'notification_id' => (string) $notification->id,
            'toast' => $toast,
        ])
        ->assertDispatched('activity-notifications-count')
        ->assertDispatched('activity-toast', fn ($name, $params) => $params['toast']['id'] === (string) $notification->id);

    $component
        ->call('handleRealtime', [
            'action' => 'removed',
            'notification_id' => (string) $notification->id,
            'toast' => $toast,
        ])
        ->assertNotDispatched('activity-toast');
});

it('não dispara toast quando a notificação já não existe', function () {
    $teacher = Teacher::factory()->create();
    [$discipline, , $activit] = makeNotificationContext($teacher);
    $student = makeNotificationStudent($teacher, $discipline);

    $this->actingAs($student, 'student');

    Livewire::test('global.notification-bell', [
        'userType' => 'student',
        'notifiableId' => $student->id,
    ])
        ->call('handleRealtime', [
            'action' => 'created',
            'notification_id' => (string) $activit->id,
            'toast' => ['id' => 'missing', 'title' => 'Nova atividade'],
        ])
        ->assertNotDispatched('activity-toast');
});
