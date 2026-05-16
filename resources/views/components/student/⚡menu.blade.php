<?php

use Livewire\Component;
use Livewire\Attributes\Computed;

new class extends Component {
    #[Computed]
    public function menuItems()
    {
        return [
            [
                'icon' => 'o-academic-cap',
                'route' => route('student.discipline'),
                'label' => 'Conteúdos',
                'badge' => null,
            ],
            [
                'icon' => 'o-chat-bubble-bottom-center',
                'route' => route('student.messages'),
                'label' => 'Mensagens',
                'badge' => null,
            ],
            [
                'icon' => 'o-chat-bubble-left-right',
                'route' => route('student.forum.index'),
                'label' => 'Fórum',
                'badge' => null,
            ],
        ];
    }

    #[Computed]
    public function student()
    {
        return auth()->guard('student')->user();
    }
}; ?>

{{-- Sidebar Moderna --}}
<aside
    x-data="sidebar"
    class="fixed left-0 top-[80px] bottom-0 z-30 flex flex-col bg-white border-r border-gray-200 shadow-xl transition-all duration-300 ease-in-out hidden md:flex"
    :class="$store.expandedSidebar.expanded ? 'w-64' : 'w-20'"
>
    {{-- Header do Sidebar (Perfil do Professor) --}}
    <div class="relative">
        {{-- Background Gradient --}}
        <div class="absolute inset-0 bg-gradient-to-br from-blue-400 to-purple-400 opacity-10"></div>

        <div class="relative px-4 py-6">
            <div class="flex flex-col items-center gap-3">
                {{-- Avatar com Status Online --}}
                <div class="relative">
                    @if($this->student->profile?->avatar)
                        <img
                            src="{{ asset('storage/' . $this->student->profile->avatar) }}"
                            :class="$store.expandedSidebar.expanded ? 'w-20 h-20' : 'w-12 h-12'"
                            class="rounded-full object-cover border-[1px] border-white  shadow-lg transition-all duration-300 ease-in-out"
                            alt="{{ $this->student->full_name }}"
                        />
                    @else
                        <div
                            :class="$store.expandedSidebar.expanded ? 'w-20 h-20 text-2xl' : 'w-12 h-12 text-lg'"
                            class="flex items-center justify-center bg-gradient-to-br from-blue-700 to-blue-500 rounded-full text-white font-bold border-4 border-white dark:border-slate-800 shadow-lg transition-all duration-300"
                        >
                            {{ substr($this->student->full_name, 0, 1) }}
                        </div>
                    @endif

                </div>

                {{-- Nome e Papel --}}
                <div
                    x-show="$store.expandedSidebar.expanded"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-90"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="text-center"
                >
                    <h4 class="font-bold text-gray-900 dark:text-white capitalize">
                        {{ $this->student->full_name }}
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Aluno
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Divider --}}
    <div class="px-4">
        <div class="border-t border-gray-200 dark:border-slate-800"></div>
    </div>

    {{-- Menu de Navegação --}}
    <nav class="flex-1 py-4 px-3 overflow-y-auto">
        <ul class="space-y-1">
            @foreach ($this->menuItems as $menu)
                <li>
                    <a
                        href="{{ $menu['route'] }}"
                        wire:navigate
                        class="group relative flex items-center gap-3 px-3 py-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-blue-100 transition-all duration-200"
                        :class="{
                            'justify-center': !$store.expandedSidebar.expanded,
                            'bg-gradient-to-r from-blue-700 to-blue-500 text-white hover:from-blue-700 hover:to-blue-700 shadow-sm': isActive('{{ parse_url($menu['route'], PHP_URL_PATH) }}')
                        }"
                    >
                        {{-- Ícone --}}
                        <div class="flex-shrink-0 relative">
                            <x-mary-icon
                                name="{{ $menu['icon'] }}"
                                class="w-5 h-5 transition-transform group-hover:scale-110"
                                ::class="isActive('{{ parse_url($menu['route'], PHP_URL_PATH) }}') ? 'text-white' : ''"
                            />

                            {{-- Badge no ícone (quando colapsado) --}}
                            @if($menu['badge'] && $menu['badge']['value'] > 0)
                                <span
                                    x-show="!$store.expandedSidebar.expanded"
                                    class="absolute -top-1 -right-1 flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-red-500 rounded-full"
                                >
                                    {{ $menu['badge']['value'] }}
                                </span>
                            @endif
                        </div>

                        {{-- Label e Badge --}}
                        <div
                            x-show="$store.expandedSidebar.expanded"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            class="flex items-center justify-between flex-1"
                        >
                            <span class="font-medium capitalize">
                                {{ $menu['label'] }}
                            </span>

                            {{-- Badge --}}
                            @if($menu['badge'] && $menu['badge']['value'] > 0)
                                <span class="px-2 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full">
                                    {{ $menu['badge']['value'] }}
                                </span>
                            @endif
                        </div>

                        {{-- Indicador de Ativo (barra lateral) --}}
                        <div
                            class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-white rounded-r-full transition-opacity"
                            :class="isActive('{{ parse_url($menu['route'], PHP_URL_PATH) }}') ? 'opacity-100' : 'opacity-0'"
                        ></div>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- Divider --}}
    <div class="px-4">
        <div class="border-t border-gray-200"></div>
    </div>

    {{-- Footer com Botão de Toggle e Copyright --}}
    <div class="p-4 space-y-3">
        {{-- Botão de Toggle Sidebar --}}
        <button
            @click="$store.expandedSidebar.toggle()"
            class="w-full flex items-center justify-center gap-2 px-3 py-2 bg-gray-100  hover:bg-gray-200 rounded-lg transition-colors"
            :class="$store.expandedSidebar.expanded ? 'justify-between' : 'justify-center'"
        >
            <x-mary-icon
                name="o-bars-3"
                class="w-5 h-5 text-gray-600 "
            />
            <span
                x-show="$store.expandedSidebar.expanded"
                x-transition
                class="text-sm font-medium text-gray-600"
            >
                Recolher Menu
            </span>
        </button>

        {{-- Copyright --}}
        <div
            x-show="$store.expandedSidebar.expanded"
            x-transition
            class="text-center"
        >
            <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">
                v1.0.0
            </p>
        </div>
    </div>
</aside>
