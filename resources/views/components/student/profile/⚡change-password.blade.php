<?php

use Livewire\Component;
use Livewire\Attributes\{On, Validate};
use Illuminate\Support\Facades\Hash;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    public bool $open = false;
    public bool $forceChange = false;
    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';
    public $user;
    public function mount(): void
    {
        $this->user = Auth::guard('student')->user();

        if (session('must_change_password')) {
            $this->forceChange = true;
            $this->open = true;
        }
    }

    #[On('student::open-change-password')]
    public function openModal($force = false): void
    {
        $this->forceChange = $force;
        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->open = true;
    }

    public function changePassword(): void
    {
        $this->validate([
            'currentPassword' => 'required',
            'newPassword' => 'required|min:8|same:newPasswordConfirmation',
            'newPasswordConfirmation' => 'required',
        ]);

        if (!Hash::check($this->currentPassword, $this->user->password)) {
            $this->addError('currentPassword', 'A senha atual não confere.');
            return;
        }

        $this->user->update([
            'password' => Hash::make($this->newPassword),
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
                Você está usando a senha padrão. Crie uma senha forte para proteger sua conta.
            </div>
        @endif
    </div>
    <form wire:submit="changePassword" class="flex flex-col gap-4">
        <div>
            <x-mary-input label="Senha atual" type="password" wire:model="currentPassword"
                icon="o-lock-closed" />
        </div>
        <div>
            <x-mary-input label="Nova senha" type="password" wire:model="newPassword"
                icon="o-lock-closed" />
        </div>
        <div>
            <x-mary-input label="Confirmar nova senha" type="password" wire:model="newPasswordConfirmation"
                icon="o-lock-closed" />
        </div>
        <div class="flex justify-end gap-2 mt-4">
            <x-mary-button label="Cancelar" wire:click="$set('open', false)" class="btn-ghost" />
            <x-mary-button label="Salvar" type="submit" class="bg-blue-400 text-gray-50" />
        </div>
    </form>
</x-mary-modal>
