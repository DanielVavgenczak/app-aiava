<?php

use Livewire\Volt\Component;
use App\Models\{Discipline};
use Livewire\Attributes\On;

new class extends Component {
    public bool $openDetailsContent = false;
    public Discipline $discipline;

    #[On('details-content-modal')]
    public function open($id)
    {
        if ($this->discipline->id === $id) {
            $this->openDetailsContent = true;
        }
    }
}; ?>
<x-mary-modal wire:model="openDetailsContent" class="">
    <div class="mb-5">
        <h2>Detalhes</h2>
    </div>
    <div>
        <h2>{{ $discipline->title }}</h2>
        <p>{{ $discipline->description }}</p>
    </div>
    <div>
        <x-mary-button label="fechar" @click="$wire.openDetailsContent = false" />
    </div>
</x-mary-modal>
