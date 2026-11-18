<?php

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    public bool $open = false;
    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';

    #[On('teacher::open-change-password')]
    public function openModal(): void
    {
        $this->resetValidation();
        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->open = true;
    }

    public function closeModal(): void
    {
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
                'currentPassword.required' => 'Informe sua senha atual.',
                'newPassword.required' => 'Informe a nova senha.',
                'newPassword.min' => 'A nova senha deve ter pelo menos 8 caracteres.',
                'newPassword.same' => 'A confirmação da nova senha não confere.',
                'newPassword.different' => 'A nova senha deve ser diferente da senha atual.',
                'newPasswordConfirmation.required' => 'Confirme a nova senha.',
            ]);
        } catch (ValidationException $exception) {
            $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
            throw $exception;
        }

        $teacher = Auth::guard('teacher')->user();

        if (! $teacher) {
            $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
            $this->redirect(route('teacher.login'));

            return;
        }

        if (! Hash::check($this->currentPassword, $teacher->password)) {
            $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
            $this->addError('currentPassword', 'A senha atual não confere.');

            return;
        }

        $teacher->update([
            'password' => Hash::make($this->newPassword),
        ]);

        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->open = false;
        $this->success('Senha alterada com sucesso!');
    }
}; ?>

<x-mary-modal wire:model="open" title="Alterar senha" subtitle="Mantenha sua conta segura">
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
            <x-mary-button label="Cancelar" wire:click="closeModal" class="btn-ghost" />
            <x-mary-button label="Salvar" type="submit" class="bg-blue-400 text-gray-50" />
        </div>
    </form>
</x-mary-modal>
