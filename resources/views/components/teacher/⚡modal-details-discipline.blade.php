<?php

use Livewire\Component;
use App\Models\Teacher\{Discipline};
use Livewire\Attributes\On;

new class extends Component {
    public bool $open = false;
    public Discipline $discipline;

    #[On('details-content-modal')]
    public function open($id)
    {
        if ($this->discipline->id === $id) {
            $this->open = true;
        }
    }
}; ?>
<x-mary-modal wire:model="open" title="Detalhes: {{ $discipline->title ?? 'Disciplina' }}"
    subtitle="Veja os detalhes da disciplina: {{ $discipline->title ?? 'Disciplina' }}">
    <div>
        <h2>{{ $discipline->title }}</h2>
    </div>
    <x-slot:actions>
        <button x-on:click="$wire.open = false" class="capitalize btn btn-sm btn-ghost">
            fechar
        </button>
    </x-slot:actions>
</x-mary-modal>
