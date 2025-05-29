<header x-data
    class="fixed z-40 top-0 left-0 w-full bg-white dark:bg-slate-900 dark:border-gray-700 h-[80px] py-4 px-4 border-[1px] border-gray-300 flex justify-between items-center">
    <div class="flex items-center gap-3">
        <x-mary-button icon="o-bars-3" class="btn-circle btn-ghost" @click="$store.expandedSidebar.toggle()" />
        <img src="{{ asset('images/logo.png') }}" class="w-32" />
    </div>
    <div class="flex items-center">
        <x-mary-button x-ref="vai-se-fude" icon="o-bell"
            class="relative border-0 btn-ghost hover:bg-transparent hover:text-pink-600"
            @click="$dispatch('open-notifications-drawer')">
            <x-mary-badge value="1" class="absolute text-white badge-error -right-2 -top-2 " />
        </x-mary-button>
        <x-mary-button class="relative border-0 btn-ghost hover:bg-transparent hover:text-pink-600">
            <x-mary-theme-toggle />
        </x-mary-button>
        <x-mary-dropdown label="{{ Auth::user()->first_name }}"
            class="capitalize border-0 btn-ghost hover:bg-transparent hover:text-pink-600">
            <x-mary-menu-item title="Perfil" wire:click.stop="delete2" link="{{ route('teacher.profile') }}"
                spinner="delete2" icon="o-user" />
            <x-mary-menu-item title="Sair" wire:click.stop="logout" spinner="logout" icon="o-arrow-uturn-left" />
        </x-mary-dropdown>
        {{-- <x-mary-dropdown right>
            <x-slot:trigger>
                <button class="capitalize">{{ Auth::user()->first_name }}</button>
            </x-slot:trigger>
            <x-mary-menu-item class="capitalize" title="perfil" link="{{ route('teacher.profile') }}" />
            <x-mary-menu-item class="capitalize" title="sair" wire:click="logout" />
        </x-mary-dropdown> --}}
    </div>
    <livewire:pages.teacher.components.notifications />
</header>
