<header
    class="w-full bg-white dark:bg-slate-900 dark:border-gray-700 h-[80px] py-4 px-6 border-[1px] border-gray-300 flex justify-between items-center">
    <div class="flex items-center gap-3">
        <x-mary-button icon="o-bars-3" class="btn-circle btn-ghost" />
        <img src="{{ asset('images/logo.png') }}" class="w-32 " />
    </div>
    <div class="flex items-center gap-3">
        <x-mary-icon name="o-bell" class="w-5 h-5 text-gray-600" />
        <x-mary-theme-toggle />
        <x-mary-dropdown right>
            <x-slot:trigger>
                <button class="capitalize">{{ Auth::user()->first_name }}</button>
            </x-slot:trigger>
            <x-mary-menu-item class="capitalize" title="perfil" />
            <x-mary-menu-item class="capitalize" title="sair" wire:click="logout" />
        </x-mary-dropdown>
    </div>
</header>
