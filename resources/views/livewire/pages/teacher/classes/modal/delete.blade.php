<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
new class extends Component {
    public bool $openDelete = false;

    #[On('delete-class')]
    public function open($id)
    {
        $this->openDelete = true;
    }

    public function close()
    {
        $this->openDelete = false;
    }
}; ?>
<x-mary-modal wire:model="openDelete" class="backdrop-blur">

</x-mary-modal>
