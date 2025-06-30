<?php

use Livewire\Volt\Component;
use App\Models\{Discipline};
use Livewire\Attributes\On;

new class extends Component {
    public bool $open = false;
    public ?Discipline $discipline = null;
    public $title;

    #[On('edit-content-modal')]
    public function open($id)
    {
        if ($this->discipline->id === $id) {
            $this->open = true;
        }
    }

    #[On('to-close')]
    public function toClose()
    {
        $this->open = false;
    }

    public function mount(Discipline $discipline)
    {
        $this->fill($discipline);
        $this->title = $discipline->title;
    }
    public function update()
    {
        $this->validate([
            'title' => 'required|string|max:255',
        ]);

        $this->discipline->update([
            'title' => $this->title,
        ]);
        $this->open = false;
        $this->dispatch('content-update', title: $this->title, action: 'update');
    }
}; ?>

<x-mary-modal wire:model="open">
    <div>
        <h2>Editar: {{ $discipline->title ?? 'Discipline' }}</h2>
    </div>
    <form wire:submit.prevent="update">
        <div class="my-3" id="discipline-edit-form-title-{{ $discipline->id ?? '' }}">
            <x-input-label for="title" :value="__('Titulo')" />
            <x-text-input wire:model="title" id="title"
                class="border-[1px] focus:border-pink-500 focus:outline focus:outline-pink-50 px-3 py-2 block w-full mt-1"
                type="text" name="title" required autofocus autocomplete="title" />
            <x-input-error :messages="$errors->get('title')" class="mt-2" />
        </div>
        <div class="flex justify-end my-3" id="discipline-edit-form-actions-{{ $discipline->id ?? '' }}">
            <div class="flex items-center gap-2">
                <button class="btn text-gray-500 capitalize  hover:text-pink-900"
                    x-on:click="$wire.openEditContent = false" id="discipline-edit-cancel-{{ $discipline->id ?? '' }}">
                    Cancelar
                </button>
                <button class="btn text-white capitalize bg-pink-600 hover:bg-pink-900" type="submit"
                    id="discipline-edit-form-send-{{ $discipline->id ?? '' }}">
                    Editar
                </button>
                <div wire:loading>
                    <span class="text-pink-600 loading loading-spinner loading-xs"></span>
                </div>
            </div>
        </div>
    </form>
</x-mary-modal>
