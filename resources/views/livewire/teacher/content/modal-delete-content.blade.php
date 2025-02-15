<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {
    public bool $openDeleteContent = false;
    public string $id;
    public string $title;

    #[On('delete-content-modal')]
    public function open($id)
    {
        if ($this->id === $id) {
            $this->openDeleteContent = true;
        }
        if ($id === null) {
            $this->openDeleteContent = false;
        }
    }
}; ?>

<x-mary-modal wire:model="openDeleteContent" class="">
    <div class="mb-9 content-delete">
        <h3 class="mb-5 text-base font-medium text-gray-700">Tem certeza que deseja deletar: <span
                class="font-extralight">{{ $title }}</span></h3>
        <p class="text-sm font-normal text-gray-500">Ao deletar esse conteudo todas as <strong>Aulas,Blocos,
                Materiais</strong> serão
            deletados junto da mesma!
        </p>
    </div>
    <div class="delete-content-options">
        <x-mary-button label="Cancel" @click="$wire.openDeleteContent = false" />
        <x-mary-button label="Sim" class="btn-error text-gray-50" @click="$wire.openDeleteContent = false" />
    </div>
</x-mary-modal>
