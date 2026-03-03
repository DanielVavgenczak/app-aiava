<?php

use Livewire\Volt\Component;

new class extends Component {
    public $items = [];

    public function mount()
    {
        $this->items = [
            [
                'icon' => 'o-home',
                'route' => route('student.index'),
                'label' => 'home',
                'badge' => null,
            ],
            [
                'icon' => 'o-academic-cap',
                'route' => route('student.discipline'),
                'label' => 'disciplina',
                'badge' => null,
            ],
            [
                'icon' => 'o-chat-bubble-left-right',
                'route' => route('student.forum.index'),
                'label' => 'Forum',
            ],
        ];
    }
}; ?>
<div>
    <aside x-data="sidebar"
        class="fixed left-0 bottom-0 z-30 flex flex-col justify-between top-[80px] h-[calc(100%-80px)] bg-white dark:bg-slate-900 transition-all duration-300 ease-in-out  hidden md:flex"
        :class="$store.expandedSidebar.expanded ? 'w-1/6' : 'w-20'">
        <!-- Conteúdo superior -->
        <div class="py-5">
            <div class="flex flex-col items-center justify-center gap-2">
                @if (!Auth::guard('student')->user()->profile?->avatar)
                    <livewire:pages.teacher.components.avatar
                        initiais="{{ Auth::guard('student')->user()->initialLetterToAvatar() }}" class="w-20 h-20">
                    @else
                        <img src="{{ asset('storage/' . Auth::guard('student')->user()->profile->avatar) }}"
                            :class="$store.expandedSidebar.expanded ? 'w-20 h-20' : 'w-12 h-12'"
                            class="transition-all duration-300 ease-in-out border-2 border-gray-200 rounded-full dark:border-slate-700" />
                @endif
                <h4 class="font-medium text-gray-600 capitalize dark:text-gray-200">
                    {{ Auth::guard('student')->user()->firstName() }}</h4>
            </div>
        </div>

        <!-- Área do menu com scroll overflow-y-auto -->
        <div class="flex-1 py-3">
            <nav>
                @foreach ($items as $menu)
                    <a class="flex gap-4 py-4 text-gray-600 capitalize transition-all duration-300 ease-in-out dark:text-gray-200 hover:text-blue-400"
                        :class="{
                            'justify-center': !$store.expandedSidebar.expanded,
                            'text-white bg-blue-400 dark:bg-slate-800': isActive(
                                '{{ parse_url($menu['route'], PHP_URL_PATH) }}')
                        }"
                        href="{{ $menu['route'] }}" wire:navigate>
                        <x-mary-icon name="{{ $menu['icon'] }}" />
                        <span x-show="$store.expandedSidebar.expanded" class="transition-all duration-300 ease-in-out">
                            {{ $menu['label'] }}
                        </span>
                    </a>
                @endforeach
            </nav>
        </div>


    </aside>
