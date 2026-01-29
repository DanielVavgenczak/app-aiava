<?php

use App\Models\Teacher\Teacher;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . Teacher::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        event(new Registered(Teacher::create($validated)));

        //Auth::login($user);

        $this->redirect(route('teacher.login'));
    }
}; ?>

<div>
    <form wire:submit="register">
        <!-- Name -->
        <div>

            <x-mary-input label="Nome" placeholder="Nome" for="first_name" :value="__('Nome')" wire:model="first_name"
                class="!outline-none" />

        </div>
        <div class="mt-4">

            <x-mary-input label="Sobrenome" placeholder="Sobrenome" for="last_name" :value="__('Sobrenome')"
                wire:model="last_name" class="!outline-none" />

        </div>
        <!-- Email Address -->
        <div class="mt-4">
            <x-mary-input label="E-mail" placeholder="Seu email" for="email" :value="__('Email')" wire:model="email"
                type="email" class="!outline-none" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-mary-input label="Senha" placeholder="******" wire:model="password" type="password"
                class="!outline-none" />
            {{-- <x-input-label for="password" :value="__('Senha')" />
            <x-text-input wire:model="password" id="password"
                class="px-3 py-2 border-[1px] focus:border-pink-500 focus:outline focus:outline-pink-50 block w-full mt-1"
                type="password" name="password" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" /> --}}
        </div>
        <!-- Confirm Password -->
        <div class="mt-4">
            <x-mary-input label="Confirme a senha" placeholder="******" wire:model="password_confirmation"
                type="password" class="!outline-none" />
            {{-- <x-input-label for="password_confirmation" :value="__('Confirme a senha')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation"
                class="px-3 py-2 border-[1px]focus:border-pink-500 focus:outline focus:outline-pink-50 block w-full mt-1"
                type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" /> --}}
        </div>

        <div class="flex items-center justify-between mt-4">
            <a class="text-sm text-gray-600 underline rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                href="{{ route('teacher.login') }}" wire:navigate>
                {{ __('Já possue uma conta?') }}
            </a>
            <button class="btn btn-sm bg-pink-600 capitalize text-gray-50 tracking-wide">
                registrar
            </button>
        </div>
    </form>
</div>
