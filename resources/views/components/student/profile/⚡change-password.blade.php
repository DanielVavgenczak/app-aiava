<?php

use App\Models\Teacher\Student;
use Livewire\Component;
use Livewire\Attributes\{On, Validate};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    public bool $open = false;
    public bool $forceChange = false;
    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';

    private function student(): ?Student
    {
        return Auth::guard('student')->user();
    }

    public function mount(): void
    {
        $student = $this->student();

        if ($student && $student->must_change_password) {
            $this->forceChange = true;
            $this->open = true;
        }
    }

    #[On('student::open-change-password')]
    public function openModal($force = false): void
    {
        $this->resetValidation();
        $this->forceChange = $force;
        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->open = true;
    }

    public function closeModal(): void
    {
        if ($this->forceChange) {
            return;
        }

        $this->resetValidation();
        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->open = false;
    }

    public function changePassword(): void
    {
        try {
            $this->validate([
                'currentPassword' => 'required',
                'newPassword' => 'required|min:8|same:newPasswordConfirmation|different:currentPassword',
                'newPasswordConfirmation' => 'required',
            ], [
                'newPassword.different' => 'A nova senha deve ser diferente da senha atual.',
                'newPassword.min' => 'A nova senha deve ter pelo menos 8 caracteres.',
                'newPassword.same' => 'A confirmação da nova senha não confere.',
            ]);
        } catch (ValidationException $exception) {
            $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
            throw $exception;
        }

        $student = $this->student();
        if (! $student) {
            $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
            $this->redirect(route('student.login'));

            return;
        }

        if (! Hash::check($this->currentPassword, $student->password)) {
            $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
            $this->addError('currentPassword', 'A senha atual não confere.');

            return;
        }

        $student->update([
            'password' => Hash::make($this->newPassword),
            'temporary_password' => null,
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);

        session()->forget('must_change_password');

        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->open = false;
        $this->success('Senha alterada com sucesso!');
    }
}; ?>

<x-mary-modal wire:model="open" title="Alterar senha" subtitle="Mantenha sua conta segura">
    <div class="mb-4">
        @if ($forceChange)
            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800">
                <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5 inline mr-1" />
                Você está usando uma senha temporária. Crie uma senha forte para proteger sua conta.
            </div>
        @endif
    </div>

    <form wire:submit="changePassword" class="flex flex-col gap-4">
        <div>
            <x-mary-input label="Senha atual" type="password" wire:model.defer="currentPassword"
                autocomplete="current-password" icon="o-lock-closed" />
        </div>
        <div>
            <x-mary-input label="Nova senha" type="password" wire:model.defer="newPassword"
                autocomplete="new-password" icon="o-lock-closed" />
        </div>
        <div>
            <x-mary-input label="Confirmar nova senha" type="password" wire:model.defer="newPasswordConfirmation"
                autocomplete="new-password" icon="o-lock-closed" />
        </div>
        <div class="flex justify-end gap-2 mt-4">
            @if(!$forceChange)
                <x-mary-button label="Cancelar" wire:click="closeModal" class="btn-ghost" />
            @endif
            <x-mary-button label="Salvar" type="submit" class="bg-blue-400 text-gray-50" />
        </div>
    </form>
</x-mary-modal>
