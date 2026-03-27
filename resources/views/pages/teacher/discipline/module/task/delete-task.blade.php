<?php

use Livewire\Component;
use Livewire\Attributes\{On};
use App\Models\Teacher\ActivitTask;

new class extends Component {
    public bool $openDelete = false;
    public ActivitTask $activittask;

    public function mount(ActivitTask $activittask)
    {
        $this->activittask = $activittask;
    }

    #[On('open-modal-delete')]
    public function open($id)
    {
        $this->openDelete = true;
    }

    public function delete()
    {
        $this->activittask->delete();
        $this->dispatch('task-deleted');
        sleep(2);
        $this->openDelete = false;
    }
}; ?>

<x-mary-modal wire:model="openDelete" class="backdrop-blur">
    Tem certeza que deseja deletar essa atividade ?
    {{ $activittask->id }}
    <x-slot:actions>
        <x-mary-button label="Cancel" x-on:click="$openDelete = false" />
        <x-mary-button label="Sim" class="btn-error" wire:click="delete" spinner />
    </x-slot:actions>
</x-mary-modal>
