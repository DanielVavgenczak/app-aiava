<?php

use Livewire\Component;
use Livewire\Attributes\Computed;

new class extends Component {
    public $authenticated;
    public $profileLink;
    public $userType; // 'teacher' ou 'student'

    public function mount(): void
    {
        $this->authenticatedUser();
    }

    public function logout(): void
    {
        $guard = $this->userType === 'teacher' ? 'teacher' : 'student';
        auth()->guard($guard)->logout();

        session()->invalidate();
        session()->regenerateToken();

        $this->redirect(route('login'), navigate: true);
    }

    private function authenticatedUser(): void
    {
        $isTeacher = auth()->guard('teacher')->check() ? auth()->guard('teacher')->user() : null;
        $isStudent = auth()->guard('student')->check() ? auth()->guard('student')->user() : null;

        $this->authenticated = $isTeacher ?: $isStudent;
        $this->userType = $isTeacher ? 'teacher' : 'student';
        $this->profileLink = $isTeacher ? 'teacher.profile' : 'student.profile';
    }


    #[Computed]
    public function userName()
    {
        return $this->authenticated?->full_name ?? $this->authenticated?->first_name ?? 'Usuário';
    }

    #[Computed]
    public function userInitials()
    {
        $name = $this->userName;
        $words = explode(' ', $name);

        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[count($words) - 1], 0, 1));
        }

        return strtoupper(substr($name, 0, 1));
    }
};
?>

{{-- Header Moderno --}}
<header
    x-data="{
        searchOpen: false,
        notificationsOpen: false
    }"
    class="fixed z-40 top-0 left-0 right-0 bg-white border-b border-gray-200 shadow-sm backdrop-blur-sm bg-opacity-95"
>
    @php
        $mustChange = session('must_change_password');
    @endphp
    @if ($mustChange && $userType === 'student')
        <div class="bg-yellow-50 border-b border-yellow-200 px-4 py-2 text-sm text-yellow-800 flex items-center justify-center gap-2">
            <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5 inline" />
            <span>Você está usando a senha padrão. <a href="{{ route('student.profile') }}" wire:navigate class="underline font-semibold">Clique aqui</a> para alterar sua senha.</span>
        </div>
    @endif
    <div class="h-20 px-4 lg:px-6 flex items-center justify-between gap-4">
        {{-- Left Section: Logo e Menu Toggle --}}
        <div class="flex items-center gap-4">
            {{-- Menu Toggle --}}
            <button
                @click="$store.expandedSidebar.toggle()"
                class="p-2 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg transition-colors hidden md:block"
                title="Toggle Menu"
            >
                <x-mary-icon name="o-bars-3" class="w-6 h-6 text-gray-600" />
            </button>

            {{-- Logo --}}
            <a href="{{ route($userType . '.discipline') }}" wire:navigate class="flex items-center gap-3 group">

                <div class="hidden lg:block">
                     <img
                    src="{{ asset('images/aiava-logo.png') }}"
                    alt="SOS Solutions"
                    class="h-10 w-auto transition-transform group-hover:scale-105"
                />
                    <p class="text-xs text-gray-500">
                        {{ $userType === 'teacher' ? 'Painel do Professor' : 'Painel do Aluno' }}
                    </p>
                </div>
            </a>
        </div>

        <div class="flex items-center gap-2">
            {{-- Search Button (Mobile) --}}
            <button
                @click="searchOpen = !searchOpen"
                class="lg:hidden p-2 hover:bg-gray-100 rounded-lg transition-colors"
            >
                <x-mary-icon name="o-magnifying-glass" class="w-5 h-5 text-gray-600" />
            </button>

            {{-- Notifications --}}
            <livewire:global.notification-bell :user-type="$userType" :notifiable-id="$authenticated?->id" />


            {{-- Divider --}}
            <div class="w-px h-8 bg-gray-200 mx-2"></div>

            {{-- User Menu --}}
            <div class="relative" x-data="{ open: false }">
                <button
                    @click="open = !open"
                    class="flex items-center gap-3 px-3 py-2 hover:bg-gray-100 rounded-lg transition-colors group"
                >
                    {{-- Avatar --}}
                    <div class="relative">
                        @if ($authenticated?->hasAvatar())
                            <img
                                src="{{ $authenticated->avatar_url }}"
                                alt="{{ $this->userName }}"
                                class="w-10 h-10 rounded-full object-cover border-[1px] border-gray-200  group-hover:border-blue-500 transition-colors"
                            />
                        @else
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-purple-400 rounded-full flex items-center justify-center text-white font-bold text-sm border-[1px] border-gray-200 group-hover:border-blue-500 transition-colors">
                                {{ $this->userInitials }}
                            </div>
                        @endif

                    </div>

                    {{-- Nome e Seta --}}
                    <div class="hidden md:flex items-center gap-2">
                        <div class="text-left">
                            <p class="text-sm font-semibold text-gray-900 capitalize">
                                {{ $this->userName }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $userType === 'teacher' ? 'Professor' : 'Aluno' }}
                            </p>
                        </div>

                        <x-mary-icon
                            name="o-chevron-down"
                            class="w-4 h-4 text-gray-400 transition-transform"
                            ::class="open ? 'rotate-180' : ''"
                        />
                    </div>
                </button>

                {{-- Dropdown Menu --}}
                <div
                    x-show="open"
                    @click.away="open = false"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl border border-gray-200  overflow-hidden"
                    style="display: none;"
                >
                    {{-- User Info --}}
                    <div class="px-4 py-3 border-b border-gray-200">
                        <p class="text-sm font-semibold text-gray-900 capitalize">
                            {{ $this->userName }}
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ $authenticated?->email }}
                        </p>
                    </div>

                    {{-- Menu Items --}}
                    <div class="py-2">

                        <a  href="{{ route($profileLink) }}"
                            wire:navigate
                            class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                        >
                            <span>Meu Perfil</span>
                        </a>
                    </div>

                    {{-- Logout --}}
                    <div class="border-t border-gray-200">
                        <button
                            wire:click="logout"
                            wire:loading.attr="disabled"
                            class="w-full flex items-center gap-3 px-4 py-3 text-sm text-red-600 hover:bg-red-50  transition-colors"
                        >
                            <x-mary-icon name="o-arrow-left-on-rectangle" class="w-5 h-5" />
                            <span wire:loading.remove wire:target="logout">Sair</span>
                            <span wire:loading wire:target="logout">Saindo...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile Search Bar (quando aberto) --}}
    <div
        x-show="searchOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="lg:hidden px-4 pb-4 border-t border-gray-200 "
        style="display: none;"
    >
        <div class="relative">
            <x-mary-icon
                name="o-magnifying-glass"
                class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
            />
            <input
                type="text"
                placeholder="Buscar..."
                class="w-full pl-10 pr-4 py-2 bg-gray-100 border-0 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
        </div>
    </div>
</header>
