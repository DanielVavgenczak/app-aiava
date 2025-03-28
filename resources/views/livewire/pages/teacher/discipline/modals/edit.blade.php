<?php

use Livewire\Volt\Component;
use App\Models\{Discipline};
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;

new class extends Component {
    public bool $openEditContent = false;
    public ?Discipline $discipline = null;
    public $title;
    public $description;

    #[On('edit-content-modal')]
    public function open($id)
    {
        if ($this->discipline->id === $id) {
            $this->openEditContent = true;
        }
    }

    #[On('to-close')]
    public function toClose()
    {
        $this->openEditContent = false;
    }

    public function mount(Discipline $discipline)
    {
        $this->fill($discipline);
        $this->title = $discipline->title;
        $this->description = $discipline->description;
    }
    public function update()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $this->discipline->update([
            'title' => $this->title,
            'description' => $this->description,
        ]);
        $this->openEditContent = false;
        $this->dispatch('content-update', title: $this->title, action: 'update');
    }
}; ?>

<x-mary-modal wire:model="openEditContent">
    <div>
        <h2>Editar: {{ $discipline->title ?? 'Discipline' }}</h2>
    </div>
    <form wire:submit.prevent="update">
        <div class="my-3" id="discipline-edit-form-title-{{ $discipline->id ?? '' }}">
            <x-input-label for="title" :value="__('Titulo')" />
            <x-text-input wire:model="title" id="title" class="block w-full mt-1" type="text" name="title" required
                autofocus autocomplete="title" />
            <x-input-error :messages="$errors->get('title')" class="mt-2" />
        </div>
        <div class="my-3" id="discipline-edit-form-description-{{ $discipline->id ?? '' }}">
            <x-input-label for="description" :value="__('Descrição')" />
            <x-text-area-input wire:model="description" id="description" class="block w-full mt-1" name="description"
                required />
            <x-input-error :messages="$errors->get('description')" class="mt-2" />
        </div>
        <div class="flex justify-end my-3" id="discipline-edit-form-actions-{{ $discipline->id ?? '' }}">
            <div class="flex items-center gap-2">
                <x-mary-button class="text-gray-500 capitalize btn-ghost hover:bg-pink-900"
                    @click="$wire.openEditContent = false" id="discipline-edit-cancel-{{ $discipline->id ?? '' }}">
                    Cancelar
                </x-mary-button>
                <x-mary-button class="text-white capitalize bg-pink-600 hover:bg-pink-900" type="submit"
                    id="discipline-edit-form-send-{{ $discipline->id ?? '' }}">
                    Editar
                </x-mary-button>
                <div wire:loading>
                    <span class="text-pink-600 loading loading-spinner loading-xs"></span>
                </div>
            </div>
        </div>
    </form>
</x-mary-modal>
