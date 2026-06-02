<?php

use Livewire\Component;
use Livewire\Attributes\{On, Validate};
use Illuminate\Support\Facades\Hash;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    public bool $open = false;
    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';

    public function mount(): void
    {
        $this->user = Auth::guard('teacher')->user();
    }

    #[On('teacher::open-change-password')]
    public function openModal(): void
    {
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

        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->open = false;
        $this->success('Senha alterada com sucesso!');
    }
}; ?>

<x-mary-modal wire:model="open" title="Alterar senha" subtitle="Mantenha sua conta segura">
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
            <x-mary-button label="Salvar" type="submit" class="bg-pink-600 text-gray-50" />
        </div>
    </form>
</x-mary-modal>
