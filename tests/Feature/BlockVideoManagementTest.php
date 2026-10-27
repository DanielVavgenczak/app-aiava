<?php

use App\Models\Lesson;
use App\Models\Module;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Teacher;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('exclui bloco de vídeo e remove o arquivo do disco', function () {
    Storage::fake('private');

    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);

    Storage::disk('private')->put('videos/lessons/bloco.mp4', 'conteudo');

    $lesson = Lesson::factory()->create([
        'module_id' => $module->id,
        'teacher_id' => $teacher->id,
        'path_file' => 'videos/lessons/bloco.mp4',
    ]);

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.discipline.block-video.video')
        ->dispatch('load-video-block', lesson_id: $lesson->id)
        ->call('deleteBlock')
        ->assertRedirect(route('teacher.discipline.show', $discipline));

    expect(Lesson::whereKey($lesson->id)->exists())->toBeFalse()
        ->and(Storage::disk('private')->exists('videos/lessons/bloco.mp4'))->toBeFalse();
});

it('mostra atualização de vídeo apenas para blocos do tipo vídeo', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);

    $video = Lesson::factory()->create([
        'module_id' => $module->id,
        'teacher_id' => $teacher->id,
        'type' => 'video',
    ]);

    $pdf = Lesson::factory()->create([
        'module_id' => $module->id,
        'teacher_id' => $teacher->id,
        'type' => 'text',
        'path_file' => 'videos/lessons/material.pdf',
        'original_name' => 'material.pdf',
    ]);

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.discipline.block-video.video')
        ->dispatch('load-video-block', lesson_id: $video->id)
        ->assertSee('Atualizar')
        ->assertSee('Excluir bloco');

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.discipline.block-video.video')
        ->dispatch('load-video-block', lesson_id: $pdf->id)
        ->assertDontSee('Atualizar')
        ->assertSee('Excluir bloco');
});
