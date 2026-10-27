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
use Illuminate\Support\Carbon;
use Livewire\Livewire;

function makePanelStudent(Teacher $teacher, Discipline $discipline): Student
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

function makePanelActivity(Teacher $teacher, Discipline $discipline, Module $module, ?Carbon $end = null, string $type = 'text'): array
{
    $activit = ActivitTask::factory()
        ->forDiscipline($discipline, $module, $teacher)
        ->create(['end' => $end]);

    $task = Task::factory()->forActivitTask($activit)->create([
        'type' => $type,
        'question' => 'Questão',
    ]);

    return [$activit, $task];
}

it('exibe a situação de cada atividade no painel do aluno', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);
    $student = makePanelStudent($teacher, $discipline);

    [$naoAcessada] = makePanelActivity($teacher, $discipline, $module);

    [$pendente] = makePanelActivity($teacher, $discipline, $module);
    ActivityAccess::factory()->forActivity($pendente, $student)->create(['first_accessed_at' => now()]);

    [$respondida, $task] = makePanelActivity($teacher, $discipline, $module);
    StudentAnwser::factory()->forTask($task, $respondida)->create([
        'student_id' => $student->id,
        'answer_text' => 'Resposta',
    ]);

    $this->travelTo(Carbon::parse('2026-01-20 12:00:00'));
    [$foraDoPrazo] = makePanelActivity($teacher, $discipline, $module, Carbon::parse('2026-01-10 23:59:59'));

    $this->actingAs($student, 'student')
        ->get(route('student.lessons', $discipline->id))
        ->assertOk()
        ->assertSee('Não acessada')
        ->assertSee('Pendente')
        ->assertSee('Respondida')
        ->assertSee('Fora do prazo')
        ->assertSee('Prazo:');
});

it('lista as atividades do aluno com datas e situação na modal do professor', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);
    $student = makePanelStudent($teacher, $discipline);

    $this->travelTo(Carbon::parse('2026-01-01 08:00:00'));
    [$naoAcessada] = makePanelActivity($teacher, $discipline, $module);

    [$pendente] = makePanelActivity($teacher, $discipline, $module);
    ActivityAccess::factory()->forActivity($pendente, $student)->create([
        'first_accessed_at' => Carbon::parse('2026-01-02 09:00:00'),
    ]);

    [$respondida, $task] = makePanelActivity($teacher, $discipline, $module);
    StudentAnwser::factory()->forTask($task, $respondida)->create([
        'student_id' => $student->id,
        'answer_text' => 'Resposta',
    ]);

    $this->travelTo(Carbon::parse('2026-01-20 12:00:00'));
    [$foraDoPrazo] = makePanelActivity($teacher, $discipline, $module, Carbon::parse('2026-01-10 23:59:59'));

    $component = Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.classe.student-detail')
        ->dispatch('teacher::view-student', id: $student->id)
        ->assertSet('open', true)
        ->assertSee('Atividades')
        ->assertSee('Não acessada')
        ->assertSee('Pendente')
        ->assertSee('Respondida')
        ->assertSee('Fora do prazo')
        ->assertSee('Sem prazo')
        ->assertSee('02/01/2026 09:00');

    $activities = collect($component->get('activities'));
    $statusById = $activities->pluck('status', 'id');

    expect($activities)->toHaveCount(4)
        ->and($statusById[$naoAcessada->id])->toBe('not_accessed')
        ->and($statusById[$pendente->id])->toBe('pending')
        ->and($statusById[$respondida->id])->toBe('answered')
        ->and($statusById[$foraDoPrazo->id])->toBe('overdue');
});

it('mostra a situação da atividade no drawer de resultado do aluno', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);
    $student = makePanelStudent($teacher, $discipline);
    [$activit] = makePanelActivity($teacher, $discipline, $module);

    $this->actingAs($student, 'student');

    Livewire::test('pages::student.discipline.lesson.task-result')
        ->dispatch('student-event-open-task-result', id: $activit->id)
        ->assertSet('show', true)
        ->assertSet('statusLabel', 'Não acessada')
        ->assertSee('Não acessada');
});

it('não lista na modal do professor atividades de outra disciplina', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);
    $student = makePanelStudent($teacher, $discipline);

    [$ownActivity] = makePanelActivity($teacher, $discipline, $module);

    $otherDiscipline = Discipline::factory()->for($teacher)->create();
    $otherModule = Module::factory()->create(['discipline_id' => $otherDiscipline->id]);
    [$otherActivity] = makePanelActivity($teacher, $otherDiscipline, $otherModule);

    $component = Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.classe.student-detail')
        ->dispatch('teacher::view-student', id: $student->id);

    $ids = collect($component->get('activities'))->pluck('id');

    expect($ids)->toContain($ownActivity->id)
        ->and($ids)->not->toContain($otherActivity->id);
});
