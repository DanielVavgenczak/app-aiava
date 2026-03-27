<?php

use Livewire\Component;
use Livewire\Attributes\On;

new class extends Component {
    public bool $showDrawerMessages = false;

    #[On('open-message-drawer')]
    public function openDrawer()
    {
        $this->js("console.log('vai caga')");
        $this->showDrawerMessages = true;
    }
}; ?>
<x-mary-drawer wire:model="showDrawerMessages" class="w-11/12 lg:w-1/3" right>
    <div>
        Uma listagem das mensagems aqui
    </div>
    <x-mary-button label="Close" @click="$wire.showDrawerMessages = false" />
</x-mary-drawer>
