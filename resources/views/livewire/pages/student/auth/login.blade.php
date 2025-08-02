<?php

use Livewire\Attributes\{Layout, Title};

use App\Livewire\Forms\Student\Auth\{LoginForm};
use Livewire\Volt\Component;
use Mary\Traits\Toast;
new #[Layout('layouts.guest')] #[Title('Login Student')] class extends Component {
    use Toast;
    public LoginForm $form;
    public function mount()
    {
        if (session('error')) {
            $this->error(session('error'), icon: 'o-exclamation-triangle', css: 'alert-error');
            session()->forget('error');
            $this->js("console.log('error')");
        }
    }

    public function loginStudent()
    {
        $this->form->authenticationStudent();
        $this->redirect(route('student.index'), navigate: true);
    }
}; ?>

<div>
    <!-- Session Status-->
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <form wire:submit="loginStudent">
        <div>
            <x-mary-input label="Email" type="email" wire:model="form.email" class="!outline-none"
                placeholder="E-mail do aluno" />
        </div>
        <!-- Password -->
        <div class="mt-4">
            <x-mary-input label="Senha" type="password" wire:model="form.password" class="!outline-none"
                placeholder="******" />
        </div>
        <div class="flex items-center justify-end mt-4">
            <button class="btn btn-md capitalize tracking-widest bg-pink-600 text-gray-50 border-0">
                login
            </button>
        </div>
    </form>
</div>
