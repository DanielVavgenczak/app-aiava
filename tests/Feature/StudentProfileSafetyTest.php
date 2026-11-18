<?php

use App\Models\Teacher\Classe;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Student;
use App\Models\Teacher\Teacher;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('exibe no perfil apenas informações seguras da conta do aluno', function () {
    $teacher = Teacher::factory()->create([
        'first_name' => 'Ana',
        'last_name' => 'Souza',
    ]);
    $discipline = Discipline::factory()->for($teacher)->create([
        'title' => 'Matemática',
    ]);
    $classe = Classe::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
        'name' => 'Turma Segurança',
    ]);
    $student = Student::factory()->create([
        'teacher_id' => $teacher->id,
        'classe_id' => $classe->id,
        'discipline_id' => $discipline->id,
        'full_name' => 'Aluno Perfil Seguro',
        'email' => 'aluno.perfil@example.com',
        'phone' => '5511999999999',
        'temporary_password' => 'senha-temporaria-secreta',
        'must_change_password' => true,
    ]);

    $this->actingAs($student, 'student')
        ->get(route('student.profile'))
        ->assertOk()
        ->assertSee('Aluno Perfil Seguro')
        ->assertSee('aluno.perfil@example.com')
        ->assertSee('Turma Segurança')
        ->assertSee('Matemática')
        ->assertSee('Ana Souza')
        ->assertSee('Você está usando uma senha temporária')
        ->assertSee('55•••••••••99')
        ->assertDontSee('5511999999999')
        ->assertDontSee('senha-temporaria-secreta')
        ->assertDontSee($student->password);
});

it('altera a senha do aluno autenticado', function () {
    $student = Student::factory()->create([
        'password' => 'senha-atual',
        'temporary_password' => 'senha-temporaria',
        'must_change_password' => true,
    ]);

    $this->actingAs($student, 'student');

    Livewire::test('student.profile.change-password')
        ->set('currentPassword', 'senha-atual')
        ->set('newPassword', 'nova-senha-123')
        ->set('newPasswordConfirmation', 'nova-senha-123')
        ->call('changePassword')
        ->assertHasNoErrors()
        ->assertSet('open', false);

    $student->refresh();

    expect(Hash::check('nova-senha-123', $student->password))->toBeTrue()
        ->and($student->temporary_password)->toBeNull()
        ->and($student->must_change_password)->toBeFalse();
});

it('não serializa modelos nos componentes do perfil do aluno', function () {
    $student = Student::factory()->create();

    $this->actingAs($student, 'student');

    foreach (['student.profile.update', 'student.profile.change-password'] as $component) {
        expect(Livewire::test($component)->html())
            ->not->toContain('App\\\\Models\\\\Teacher\\\\Student');
    }
});

it('não mantém senhas no estado público após falha na troca de senha do aluno', function () {
    $student = Student::factory()->create([
        'password' => 'senha-atual',
    ]);

    $this->actingAs($student, 'student');

    $component = Livewire::test('student.profile.change-password')
        ->set('currentPassword', 'senha-incorreta')
        ->set('newPassword', 'nova-senha-123')
        ->set('newPasswordConfirmation', 'nova-senha-123')
        ->call('changePassword')
        ->assertHasErrors(['currentPassword'])
        ->assertSet('currentPassword', '')
        ->assertSet('newPassword', '')
        ->assertSet('newPasswordConfirmation', '');

    expect($component->html())
        ->not->toContain('senha-incorreta')
        ->not->toContain('nova-senha-123');
});
