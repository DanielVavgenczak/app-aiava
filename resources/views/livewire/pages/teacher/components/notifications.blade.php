<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {
    public bool $showDrawerNotifications = false;

    #[On('open-notifications-drawer')]
    public function openDrawer()
    {
        $this->showDrawerNotifications = true;
    }
}; ?>
<x-mary-drawer wire:model="showDrawerNotifications" class="w-11/12 lg:w-1/3" right>
    <div>
        Listagem das notifications
    </div>
    <x-mary-button label="Close" @click="$wire.showDrawerNotifications = false" />
</x-mary-drawer>
