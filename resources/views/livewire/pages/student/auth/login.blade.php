<?php

use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] #[Title('Login Student')] class extends Component {
    public $student_email = '';
    public $student_password = '';

    public function mount()
    {
        $this->js("console.log('foi chamando o mount de login students')");
    }
}; ?>

<div>
    <!-- Session Status-->
    {{-- <x-auth-session-status class="mb-4" :status="session('status')" /> --}}
    <form wire:submit="login">
        <div>
            <x-mary-input label="Email" wire:model="student_email" class="!outline-none" placeholder="E-mail do aluno" />
        </div>
        <!-- Password -->
        <div class="mt-4">
            <x-mary-input label="Senha" wire:model="student_password" class="!outline-none" placeholder="******" />
        </div>
        <div class="flex items-center justify-end mt-4">
            <button class="btn btn-md capitalize tracking-widest bg-pink-600 text-gray-50 border-0">
                login
            </button>
        </div>
    </form>
</div>
