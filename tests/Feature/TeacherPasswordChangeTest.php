<?php

use App\Models\Teacher\Teacher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('altera a senha do professor autenticado', function () {
    $teacher = Teacher::factory()->create(['password' => 'senha-atual']);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('teacher.profile.change-password')
        ->set('currentPassword', 'senha-atual')
        ->set('newPassword', 'nova-senha-123')
        ->set('newPasswordConfirmation', 'nova-senha-123')
        ->call('changePassword')
        ->assertHasNoErrors()
        ->assertSet('open', false);

    expect(Hash::check('nova-senha-123', $teacher->fresh()->password))->toBeTrue()
        ->and(Hash::check('senha-atual', $teacher->fresh()->password))->toBeFalse();

    Auth::guard('teacher')->logout();

    expect(Auth::guard('teacher')->attempt([
        'email' => $teacher->email,
        'password' => 'nova-senha-123',
    ]))->toBeTrue();
});

it('rejeita a alteração quando a senha atual não confere', function () {
    $teacher = Teacher::factory()->create(['password' => 'senha-atual']);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('teacher.profile.change-password')
        ->set('currentPassword', 'senha-incorreta')
        ->set('newPassword', 'nova-senha-123')
        ->set('newPasswordConfirmation', 'nova-senha-123')
        ->call('changePassword')
        ->assertHasErrors(['currentPassword']);

    expect(Hash::check('senha-atual', $teacher->fresh()->password))->toBeTrue();
});

it('valida a confirmação e o tamanho da nova senha', function () {
    $teacher = Teacher::factory()->create(['password' => 'senha-atual']);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('teacher.profile.change-password')
        ->set('currentPassword', 'senha-atual')
        ->set('newPassword', 'curta')
        ->set('newPasswordConfirmation', 'diferente')
        ->call('changePassword')
        ->assertHasErrors([
            'newPassword' => ['min', 'same'],
        ]);

    expect(Hash::check('senha-atual', $teacher->fresh()->password))->toBeTrue();
});

it('exige que a nova senha seja diferente da atual', function () {
    $teacher = Teacher::factory()->create(['password' => 'senha-atual']);

    $this->actingAs($teacher, 'teacher');

    Livewire::test('teacher.profile.change-password')
        ->set('currentPassword', 'senha-atual')
        ->set('newPassword', 'senha-atual')
        ->set('newPasswordConfirmation', 'senha-atual')
        ->call('changePassword')
        ->assertHasErrors(['newPassword' => 'different']);

    expect(Hash::check('senha-atual', $teacher->fresh()->password))->toBeTrue();
});
