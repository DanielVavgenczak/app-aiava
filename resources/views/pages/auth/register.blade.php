<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Forms\Auth\Register;

new #[Layout('layouts.guest')] class extends Component {

    public Register $form;
    public function register(){
        $this->form->store();
        return $this->redirect(route('login'));
    }
}; ?>

<div>
    <div class="w-full text-center">
            <img src="/images/aiava-logo.png" class="w-32 mx-auto" />
            <h5 class="text-xl font-bold text-gray-600">Registrar</h5>
    </div>
    <form wire:submit="register">
        <!-- Name -->
        <div>
            <x-mary-input label="Nome" placeholder="Nome" for="first_name" :value="__('Nome')" wire:model="form.first_name"
                class="!outline-none" />

        </div>
        <div class="mt-4">
            <x-mary-input label="Sobrenome" placeholder="Sobrenome" for="last_name" :value="__('Sobrenome')"
                wire:model="form.last_name" class="!outline-none" />
        </div>
        <!-- Email Address -->
        <div class="mt-4">
            <x-mary-input label="E-mail" placeholder="Seu email" for="email" :value="__('Email')" wire:model="form.email"
                type="email" class="!outline-none" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-mary-input label="Senha" placeholder="******" wire:model="form.password" type="password"
                class="!outline-none" />
        </div>
        <div class="mt-4">
            <x-mary-input label="Confirme a senha" placeholder="******" wire:model="form.password_confirmation"
                type="password" class="!outline-none" />

        </div>

        <div class="flex items-center justify-between mt-4">
            <a class="text-sm text-gray-700 underline rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                href="{{ route('login') }}" wire:navigate>
                {{ __('Já possui uma conta?') }}
            </a>
            <button type="submit" class="btn btn-sm bg-blue-400 capitalize text-gray-50 tracking-wide">
                registrar
            </button>
        </div>
    </form>
</div>
