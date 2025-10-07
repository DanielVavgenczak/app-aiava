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
  <button>
    <x-mary-icon name="o-x-mark"  @click="$wire.showDrawerNotifications = false"/>
  </button>
    <div class="p-5 bg-gray-200 text-sm font-normal tracker-wider">
        Em breve listagem de notificações
    </div>
</x-mary-drawer>
