<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use App\Models\Teacher\Classe;

new class extends Component {
    public bool $openDelete = false;
    public ?Classe $classe;

    #[On('delete-classe-modal')]
    public function open($id)
    {
        if ($this->classe->id === $id || !$this->classe->id === '') {
            $this->openDelete = true;
        }
    }
    public function delete()
    {
        try {
            $this->classe->delete();
            $this->openDelete = false;
            $this->classe = null;
            $this->dispatch('action-event-classe', type: 'delete');
        } catch (\Exception $e) {
            dd('Erro ao tentar deletar', $e->getMessage());
        }
    }

    public function close()
    {
        $this->openDelete = false;
    }
}; ?>

<x-mary-modal wire:model="openDelete">
    @if ($classe)
        <div class="mb-9 flex flex-col items-center gap-3 p-5" id="classe-delete-text-{{ $id ?? '' }}"
            wire:loading.remove>
            <div class="p-5 rounded-full bg-red-100">
                <x-mary-icon name="o-trash" class="text-red-700 w-6 h-6" />
            </div>
            <p class="mb-3 tracking-wide text-[18px] font-medium text-red-700">
                {{ $classe->name }}
            </p>
        </div>
        <div class=" flex flex-col items-center text-center gap-3 p-5 w-full" wire:loading wire:target="delete">
            <div class="mb-4">
                <x-mary-loading class="progress-error w-7 h-7" />
            </div>
            <p class="text-[16px] text-pink-600 tracking-wide">Deletando</p>
        </div>

        <div id="discipline-delete-actions-{{ $id ?? '' }}" class="w-full flex justify-end" wire:loading.remove>
            <button id="discipline-delete-cancel-{{ $id ?? '' }}" x-on:click="$wire.openDelete = false"
                class="text-gray-500 btn capitalize">
                cancelar
            </button>
            <button class="btn bg-red-500 text-red-50 capitalize" x-on:click="$wire.delete" wire:loading.remove
                id="discipline-delete-send-{{ $id ?? '' }}">
                Deletar
            </button>
        </div>
    @endif
</x-mary-modal>
