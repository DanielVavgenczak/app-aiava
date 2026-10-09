<?php

use App\Models\Teacher\Classe;
use App\Models\Teacher\Discipline;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\Teacher\Teacher;
use Livewire\Livewire;

it('responde 404 ao professor carregar aula de disciplina de outro professor', function () {
    $owner = Teacher::factory()->create();
    $ownDiscipline = Discipline::factory()->for($owner)->create();
    $ownClasse = Classe::factory()->create([
        'teacher_id' => $owner->id,
        'discipline_id' => $ownDiscipline->id,
    ]);
    $ownModule = Module::factory()->create([
        'discipline_id' => $ownDiscipline->id,
    ]);
    $ownLesson = Lesson::factory()->create([
        'teacher_id' => $owner->id,
        'module_id' => $ownModule->id,
    ]);

    $other = Teacher::factory()->create();

    Livewire::actingAs($other, 'teacher')
        ->test('teacher.discipline.block-video.video')
        ->dispatch('load-video-block', lesson_id: $ownLesson->id)
        ->assertNotFound();
});

it('permite professor carregar aula da sua disciplina', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $classe = Classe::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);
    $module = Module::factory()->create([
        'discipline_id' => $discipline->id,
    ]);
    $lesson = Lesson::factory()->create([
        'teacher_id' => $teacher->id,
        'module_id' => $module->id,
    ]);

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.discipline.block-video.video')
        ->dispatch('load-video-block', lesson_id: $lesson->id)
        ->assertSet('lesson.id', $lesson->id)
        ->assertSuccessful();
});
