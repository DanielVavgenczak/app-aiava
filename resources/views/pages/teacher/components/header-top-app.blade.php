<header x-data
    class="fixed z-40 top-0 left-0 w-full bg-white dark:bg-slate-900 dark:border-gray-700 h-[80px] py-4 px-4 border-[1px] border-gray-300 flex justify-between items-center">
       <div class="flex items-center gap-3">
        <x-mary-button icon="o-bars-3" class="btn-circle btn-ghost" @click="$store.expandedSidebar.toggle()" />
        <img src="{{ asset('images/logo.png') }}" class="w-32" />
    </div>

    <div class="flex items-center">
        {{-- <x-mary-button icon="o-bell" class="indicator border-0 btn-ghost hover:bg-transparent hover:text-pink-600"
            x-on:click="$dispatch('open-notifications-drawer')">
             <x-mary-badge value="1" class="badge-secondary badge-sm indicator-item top-1 right-3" />
         </x-mary-button> --}}
        <x-mary-button class="relative border-0 btn-ghost hover:bg-transparent hover:text-pink-600">
            <x-mary-theme-toggle />
        </x-mary-button>
        <x-mary-dropdown label="
            {{ $authenticated->first_name ?? $authenticated->full_name }}" class="ml-2" align="right"
            class="capitalize border-0 btn-ghost hover:bg-transparent hover:text-pink-600">
             <x-mary-menu-item title="Perfil" link="{{ route($profileLink) }}"
                 icon="o-user" />
            <x-mary-menu-item title="Sair" wire:click.stop="logout" spinner="logout" icon="o-arrow-uturn-left" />

        </x-mary-dropdown>
    </div>
    {{-- <livewire:pages.teacher.components.notifications /> --}}
</header>
