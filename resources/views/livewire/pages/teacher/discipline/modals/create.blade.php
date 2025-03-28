<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use App\Livewire\Forms\{DisciplineForm};

new class extends Component {
    public DisciplineForm $form;
    public bool $openCreateContent = false;

    #[On('create-content-modal')]
    public function open($open)
    {
        $this->openCreateContent = $open;
    }
    #[On('to-close-create')]
    public function close()
    {
        $this->openCreateContent = false;
    }

    public function store()
    {
        $this->form->disciplineStore();
        $this->openCreateContent = false;
        $this->dispatch('content-created', title: $this->form->title, action: 'create');
        $this->form->reset();
        sleep(1);
    }
}; ?>
<x-mary-modal wire:model="openCreateContent" class="">
    <div>
        <h2>Nova disciplina</h2>
    </div>
    <form wire:submit.prevent="store">
        <div class="my-3" id="discipline-input-title">
            <x-input-label for="title" :value="__('Titulo')" />
            <x-text-input wire:model.debounce.750="form.title" id="title" class="block w-full mt-1" type="text"
                name="title" required autofocus autocomplete="title" placeholder="Ex: Teoria musical" />
            <x-input-error :messages="$errors->get('form.title')" class="mt-2" />
        </div>
        <div class="my-3" id="discipline-input-description">
            <x-input-label for="description" :value="__('Descrição')" />
            <x-text-area-input wire:model.debounce.750="form.description" id="description" class="block w-full mt-1"
                name="description" required placeholder="Ex: Teoria musical" />
            <x-input-error :messages="$errors->get('form.description')" class="mt-2" />
        </div>
        <div class="flex justify-end my-3" id="discipline-save">
            <div class="flex items-center gap-2">
                <x-mary-button class="text-gray-500 btn-ghost" @click="$wire.openCreateContent = false"
                    id="create-cancel">
                    cancelar
                </x-mary-button>
            </div>
            <div class="flex items-center gap-2">
                <x-mary-button class="text-white capitalize bg-pink-600 hover:bg-pink-900" type="submit"
                    id="create-confirm">
                    registrar
                </x-mary-button>
                <div wire:loading>
                    <span class="text-pink-600 loading loading-spinner loading-xs"></span>
                </div>
            </div>
        </div>
    </form>
</x-mary-modal>
