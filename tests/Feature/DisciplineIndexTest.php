<?php

use App\Models\Teacher\Discipline;
use App\Models\Teacher\Teacher;
use Livewire\Livewire;

it('lista apenas as disciplinas do professor autenticado', function () {
    $teacher = Teacher::factory()->create();
    $other = Teacher::factory()->create();

    Discipline::factory()->for($teacher)->create(['title' => 'Matemática']);
    Discipline::factory()->for($other)->create(['title' => 'Física']);

    Livewire::actingAs($teacher, 'teacher')
        ->test('pages::teacher.discipline.index')
        ->assertSee('Matemática')
        ->assertDontSee('Física');
});

it('busca disciplinas pelo título de forma case-insensitive', function () {
    $teacher = Teacher::factory()->create();

    Discipline::factory()->for($teacher)->create(['title' => 'Matemática']);
    Discipline::factory()->for($teacher)->create(['title' => 'História']);

    Livewire::actingAs($teacher, 'teacher')
        ->test('pages::teacher.discipline.index')
        ->set('search', 'MATEMÁTICA')
        ->assertSee('Matemática')
        ->assertDontSee('História');
});

it('limpa a busca e restaura a ordenação padrão', function () {
    $teacher = Teacher::factory()->create();

    Discipline::factory()->for($teacher)->create(['title' => 'Matemática']);
    Discipline::factory()->for($teacher)->create(['title' => 'História']);

    Livewire::actingAs($teacher, 'teacher')
        ->test('pages::teacher.discipline.index')
        ->set('search', 'matemática')
        ->set('sortBy', 'name')
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('sortBy', 'recent')
        ->assertSee('Matemática')
        ->assertSee('História');
});
