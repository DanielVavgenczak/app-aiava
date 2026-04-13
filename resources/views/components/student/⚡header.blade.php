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

    // #[Computed]
    // public function unreadNotifications()
    // {
    //     return $this->authenticated?->unreadNotifications()->count() ?? 0;
    // }

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
    class="fixed z-40 top-0 left-0 right-0 bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800 shadow-sm backdrop-blur-sm bg-opacity-95 dark:bg-opacity-95"
>
    <div class="h-20 px-4 lg:px-6 flex items-center justify-between gap-4">
        {{-- Left Section: Logo e Menu Toggle --}}
        <div class="flex items-center gap-4">
            {{-- Menu Toggle --}}
            <button
                @click="$store.expandedSidebar.toggle()"
                class="p-2 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg transition-colors hidden md:block"
                title="Toggle Menu"
            >
                <x-mary-icon name="o-bars-3" class="w-6 h-6 text-gray-600 dark:text-gray-400" />
            </button>

            {{-- Logo --}}
            <a href="{{ route($userType . '.index') }}" wire:navigate class="flex items-center gap-3 group">
                {{-- <img
                    src="{{ asset('images/logo.png') }}"
                    alt="SOS Solutions"
                    class="h-10 w-auto transition-transform group-hover:scale-105"
                /> --}}
                <div class="hidden lg:block">
                    <h1 class="text-lg font-bold text-gray-900 dark:text-white">
                        AIAVA
                    </h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $userType === 'teacher' ? 'Painel do Professor' : 'Painel do Aluno' }}
                    </p>
                </div>
            </a>
        </div>

        <div class="flex items-center gap-2">
            {{-- Search Button (Mobile) --}}
            <button
                @click="searchOpen = !searchOpen"
                class="lg:hidden p-2 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
            >
                <x-mary-icon name="o-magnifying-glass" class="w-5 h-5 text-gray-600 dark:text-gray-400" />
            </button>

            {{-- Notifications --}}
            <div class="relative" x-data="{ open: false }">
                {{-- <button
                    @click="open = !open"
                    class="relative p-2 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                    title="Notificações"
                >
                    <x-mary-icon name="o-bell" class="w-6 h-6 text-gray-600 dark:text-gray-400" />


                    @if($this->unreadNotifications > 0)
                        <span class="absolute top-1 right-1 flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                        </span>
                    @endif
                </button> --}}

                {{-- Dropdown de Notificações --}}
                <div
                    x-show="open"
                    @click.away="open = false"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 mt-2 w-80 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-gray-200 dark:border-slate-700 overflow-hidden"
                    style="display: none;"
                >
                    {{-- Header --}}
                    {{-- <div class="px-4 py-3 border-b border-gray-200 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Notificações</h3>
                            @if($this->unreadNotifications > 0)
                                <span class="px-2 py-1 text-xs font-semibold text-blue-600 bg-blue-100 dark:bg-blue-900 dark:text-blue-300 rounded-full">
                                    {{ $this->unreadNotifications }} novas
                                </span>
                            @endif
                        </div>
                    </div> --}}

                    {{-- Lista de Notificações --}}
                    {{-- <div class="max-h-96 overflow-y-auto">
                        @if($this->unreadNotifications > 0)

                            <div class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors cursor-pointer border-l-4 border-blue-500">
                                <div class="flex gap-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                            <x-mary-icon name="o-chat-bubble-left-right" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Nova resposta no fórum</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">João comentou no seu tópico</p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Há 5 minutos</p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="px-4 py-8 text-center">
                                <x-mary-icon name="o-bell-slash" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
                                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma notificação</p>
                            </div>
                        @endif
                    </div> --}}

                    {{-- Footer
                    <div class="px-4 py-3 border-t border-gray-200 dark:border-slate-700">
                        <a href="#" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                            Ver todas as notificações →
                        </a>
                    </div> --}}
                </div>
            </div>

            {{-- Theme Toggle --}}
            <button
                class="p-2 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                title="Alternar tema"
            >
                <x-mary-theme-toggle class="w-6 h-6" />
            </button>

            {{-- Divider --}}
            <div class="w-px h-8 bg-gray-200 dark:bg-slate-700 mx-2"></div>

            {{-- User Menu --}}
            <div class="relative" x-data="{ open: false }">
                <button
                    @click="open = !open"
                    class="flex items-center gap-3 px-3 py-2 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg transition-colors group"
                >
                    {{-- Avatar --}}
                    <div class="relative">
                        @if($authenticated?->profile?->avatar)
                            <img
                                src="{{ asset('storage/' . $authenticated->profile->avatar) }}"
                                alt="{{ $this->userName }}"
                                class="w-10 h-10 rounded-full object-cover border-2 border-gray-200 dark:border-slate-700 group-hover:border-blue-500 transition-colors"
                            />
                        @else
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-sm border-2 border-gray-200 dark:border-slate-700 group-hover:border-blue-500 transition-colors">
                                {{ $this->userInitials }}
                            </div>
                        @endif

                        {{-- Status Online --}}
                        <span class="absolute bottom-0 right-0 block h-3 w-3 rounded-full bg-green-400 ring-2 ring-white dark:ring-slate-900"></span>
                    </div>

                    {{-- Nome e Seta --}}
                    <div class="hidden md:flex items-center gap-2">
                        <div class="text-left">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white capitalize">
                                {{ $this->userName }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
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
                    class="absolute right-0 mt-2 w-64 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-gray-200 dark:border-slate-700 overflow-hidden"
                    style="display: none;"
                >
                    {{-- User Info --}}
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-slate-700">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white capitalize">
                            {{ $this->userName }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $authenticated?->email }}
                        </p>
                    </div>

                    {{-- Menu Items --}}
                    <div class="py-2">

                            href="{{ route($profileLink) }}"
                            wire:navigate
                            class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors"
                        >
                            <x-mary-icon name="o-user" class="w-5 h-5" />
                            <span>Meu Perfil</span>
                        </a>


                            href="#"
                            class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors"
                        >
                            <x-mary-icon name="o-cog-6-tooth" class="w-5 h-5" />
                            <span>Configurações</span>
                        </a>


                            href="#"
                            class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors"
                        >
                            <x-mary-icon name="o-question-mark-circle" class="w-5 h-5" />
                            <span>Ajuda & Suporte</span>
                        </a>
                    </div>

                    {{-- Logout --}}
                    <div class="border-t border-gray-200 dark:border-slate-700">
                        <button
                            wire:click="logout"
                            wire:loading.attr="disabled"
                            class="w-full flex items-center gap-3 px-4 py-3 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
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
        class="lg:hidden px-4 pb-4 border-t border-gray-200 dark:border-slate-800"
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
                class="w-full pl-10 pr-4 py-2 bg-gray-100 dark:bg-slate-800 border-0 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
        </div>
    </div>
</header>
