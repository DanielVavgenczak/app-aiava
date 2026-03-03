@php
    $itemsMenu = [
        [
            'icon' => 'o-home',
            'route' => route('teacher.index'),
            'label' => 'home',
            'badge' => null,
        ],
        [
            'icon' => 'o-academic-cap',
            'route' => route('teacher.discipline'),
            'label' => 'conteudos',
            'badge' => null,
        ],
        [
            'icon' => 'o-chat-bubble-bottom-center',
            'route' => route('teacher.messages'),
            'label' => 'Chat',
            'badge' => ['value' => 2, 'class' => 'badge-error badge-dash text-white'],
        ],
        [
            'icon' => 'o-users',
            'route' => route('teacher.classes'),
            'label' => 'Turmas',
            //'badge' => ['value' => 2, 'class' => 'badge-error badge-dash text-white'],
        ],
        [
            'icon' => 'o-chat-bubble-left-right',
            'route' => route('teacher.forum'),
            'label' => 'Forum',
        ],
    ];
@endphp

<aside x-data="sidebar"
    class="fixed left-0 bottom-0 z-30 flex flex-col justify-between top-[80px] h-[calc(100%-80px)] bg-white dark:bg-slate-900 transition-all duration-300 ease-in-out  hidden md:flex"
    :class="$store.expandedSidebar.expanded ? 'w-1/6' : 'w-20'">
    <!-- Conteúdo superior -->
    <div class="py-5">
        <div class="flex flex-col items-center justify-center gap-2">

            {{-- @if (!Auth::guard('teacher')->user()->profile?->avatar)
                <livewire:pages.teacher.components.avatar
                    initiais="{{ Auth::guard('teacher')->user()->initialLetterToAvatar() }}" class="w-20 h-20">
                @else
                    <img src="{{ asset('storage/' . Auth::guard('teacher')->user()->profile->avatar) }}"
                        :class="$store.expandedSidebar.expanded ? 'w-20 h-20' : 'w-12 h-12'"
                        class="transition-all duration-300 ease-in-out border-2 border-gray-200 rounded-full dark:border-slate-700"
                        width="80" height="80" style="width: 80px; height: 80px; object-fit:cover;" loading="lazy" />
            @endif
            <h4 class="font-medium text-gray-600 capitalize dark:text-gray-200">
                {{ Auth::guard('teacher')->user()->first_name }}</h4> --}}
        </div>
    </div>

    <!-- Área do menu com scroll overflow-y-auto -->
    <div class="flex-1 py-3">
        <nav>
            @foreach ($itemsMenu as $menu)
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

    <!-- Rodapé fixo no final -->
    <div class="w-full py-3 dark:bg-slate-900">
        <p class="text-sm text-center text-gray-600 dark:text-gray-400">Sos Solutions</p>
    </div>
</aside>
