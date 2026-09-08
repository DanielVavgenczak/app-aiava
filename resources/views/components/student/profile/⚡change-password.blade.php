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

        if ($this->user && $this->user->must_change_password) {
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
            'newPassword' => 'required|min:8|same:newPasswordConfirmation|different:currentPassword',
            'newPasswordConfirmation' => 'required',
        ], [
            'newPassword.different' => 'A nova senha deve ser diferente da senha temporária.',
            'newPassword.min' => 'A nova senha deve ter pelo menos 8 caracteres.',
            'newPassword.same' => 'A confirmação da nova senha não confere.',
        ]);

        // Verifica se a senha atual (temporária) está correta
        if (!Hash::check($this->currentPassword, $this->user->password)) {
            $this->addError('currentPassword', 'A senha atual não confere.');
            return;
        }

        // Atualiza a senha e marca como alterada
        $this->user->update([
            'password' => Hash::make($this->newPassword),
            'temporary_password' => null, // Limpa a senha temporária
            'must_change_password' => false, // Marca como alterada
            'password_changed_at' => now(), // Registra quando foi alterada
        ]);

        // Remove a flag da sessão
        session()->forget('must_change_password');

        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->open = false;
        $this->success('Senha alterada com sucesso!');

        // // Se era forçado, redireciona para o dashboard
        // if ($this->forceChange) {
        //     return redirect()->route('student.dashboard');
        // }
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

    {{-- @if($forceChange)
        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
            <p class="font-semibold mb-1">Suas credenciais de acesso:</p>
            <p class="text-sm">Email: <strong>{{ $user->email }}</strong></p>
            <p class="text-sm">Senha temporária: <strong>{{ $user->temporary_password }}</strong></p>
        </div>
    @endif --}}

    <form wire:submit="changePassword" class="flex flex-col gap-4">
        <div>
            <x-mary-input label="Senha atual (temporária)" type="password" wire:model="currentPassword"
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
            @if(!$forceChange)
                <x-mary-button label="Cancelar" wire:click="$set('open', false)" class="btn-ghost" />
            @endif
            <x-mary-button label="Salvar" type="submit" class="bg-blue-400 text-gray-50" />
        </div>
    </form>
</x-mary-modal>
