<?php

use App\Models\Lesson;
use App\Models\Module;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Teacher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('impede criar link em modulo de outro professor', function () {
    Storage::fake('private');
    $owner = Teacher::factory()->create();
    $other = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($owner)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);

    Livewire::actingAs($other, 'teacher')
        ->test('teacher.discipline.block-video.input-link', ['module_id' => $module->id])
        ->set('link', 'https://exemplo.com')
        ->call('save')
        ->assertNotFound();

    expect(Lesson::where('module_id', $module->id)->count())->toBe(0);
});

it('permite criar link no proprio modulo', function () {
    Storage::fake('private');
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.discipline.block-video.input-link', ['module_id' => $module->id])
        ->set('link', 'https://exemplo.com')
        ->call('save')
        ->assertSuccessful();

    expect(Lesson::where('module_id', $module->id)->where('type', 'link')->exists())->toBeTrue();
});

it('impede criar pdf em modulo de outro professor', function () {
    Storage::fake('private');
    $owner = Teacher::factory()->create();
    $other = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($owner)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);

    $file = UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf');

    Livewire::actingAs($other, 'teacher')
        ->test('teacher.discipline.block-video.upload-pdf', ['module_id' => $module->id])
        ->set('pdfFile', $file)
        ->assertNotFound();

    expect(Lesson::where('module_id', $module->id)->count())->toBe(0);
});

it('permite criar pdf no proprio modulo', function () {
    Storage::fake('private');
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $module = Module::factory()->create(['discipline_id' => $discipline->id]);

    $file = UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf');

    Livewire::actingAs($teacher, 'teacher')
        ->test('teacher.discipline.block-video.upload-pdf', ['module_id' => $module->id])
        ->set('pdfFile', $file)
        ->assertSuccessful();

    expect(Lesson::where('module_id', $module->id)->where('type', 'text')->exists())->toBeTrue();
});
