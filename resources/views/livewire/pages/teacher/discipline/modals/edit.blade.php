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
        $this->dispatch('discipline-event', type: 'update', msg: 'Disciplina editada com sucesso!');
    }
}; ?>

<x-mary-modal wire:model="open" title="Editar" subtitle="Edite a disciplina: {{ $discipline->title ?? 'Discipline' }}">
    <form wire:submit.prevent="update">
        <div wire:loading.remove class="my-3" id="discipline-edit-form-title-{{ $discipline->id ?? '' }}">
            <x-mary-input for="title" :value="__('Titulo')" wire:model="title" class="!outline-none" />
        </div>
        <div class="flex justify-end my-3" id="discipline-edit-form-actions-{{ $discipline->id ?? '' }}">
            <div class="flex items-center gap-2">
                <button class="cursor-pointer btn btn-sm btn-ghost" x-on:click="$wire.openEditContent = false"
                    id="discipline-edit-cancel-{{ $discipline->id ?? '' }}">
                    Cancelar
                </button>
                <button class="bg-pink-600 btn btn-sm text-gray-50" type="submit"
                    id="discipline-edit-form-send-{{ $discipline->id ?? '' }}">
                    Editar
                </button>
            </div>
        </div>
        <div wire:loading class="flex flex-col justify-center w-full text-center">
            <div class="mb-2">
                <x-mary-loading class="text-pink-600 loading-dots" />
            </div>
            <span class="text-xs text-pink-600">Editando</span>
        </div>
    </form>
</x-mary-modal>
