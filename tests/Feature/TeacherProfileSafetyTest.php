<?php

use App\Models\Teacher\Classe;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Teacher;
use Livewire\Livewire;

it('exibe no perfil apenas informações seguras da conta do professor', function () {
    $teacher = Teacher::factory()->create([
        'first_name' => 'Ana',
        'last_name' => 'Souza',
        'email' => 'ana.perfil@example.com',
        'email_verified_at' => now(),
    ]);
    $discipline = Discipline::factory()->for($teacher)->create([
        'title' => 'Matemática',
    ]);
    Classe::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'name' => 'Turma Perfil',
    ]);

    $this->actingAs($teacher, 'teacher')
        ->get(route('teacher.profile'))
        ->assertOk()
        ->assertSee('Ana Souza')
        ->assertSee('ana.perfil@example.com')
        ->assertSee('Professor')
        ->assertSee('Membro desde')
        ->assertSee('E-mail verificado')
        ->assertSee('Verificado')
        ->assertSee('Disciplinas')
        ->assertSee('Turmas')
        ->assertDontSee($teacher->password)
        ->assertDontSee('remember_token');
});

it('não serializa modelos nos componentes do perfil do professor', function () {
    $teacher = Teacher::factory()->create();

    $this->actingAs($teacher, 'teacher');

    foreach (['teacher.profile.update', 'teacher.profile.change-password'] as $component) {
        expect(Livewire::test($component)->html())
            ->not->toContain('App\\\\Models\\\\Teacher\\\\Teacher');
    }
});
