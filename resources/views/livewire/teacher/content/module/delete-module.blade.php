<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {
    public bool $openModalModule = false;
    public string $id;
    public string $title;

    #[On('delete-module-modal')]
    public function open($id)
    {
        if ($this->id === $id) {
            $this->openModalModule = true;
        }
        if ($id === null) {
            $this->openModalModule = false;
        }
    }
}; ?>

<x-mary-modal wire:model="openModalModule" class="">
    <div class="mb-9" id="module-delete">
        <h3 class="mb-5 text-base font-medium text-gray-700">Tem certeza que deseja deletar a aula: <span
                class="font-extralight">{{ $title }}</span></h3>
        <p class="text-sm font-normal text-gray-500">Ao deletar essa aula todos os blocos serão deletados junto da mesma!
        </p>
    </div>
    <div class="delete-module-options">
        <x-mary-button label="Cancel" @click="$wire.openModalModule = false" />
        <x-mary-button label="Sim" class="btn-error text-gray-50" @click="$wire.openModalModule = false" />
    </div>
</x-mary-modal>
