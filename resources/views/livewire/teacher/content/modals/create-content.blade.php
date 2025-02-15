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

    public function store()
    {
        $this->form->disciplineStore();

        sleep(2);
        $this->openCreateContent = false;
    }
}; ?>
<x-mary-modal wire:model="openCreateContent" class="">
    <div>
        <h2>Adicionar nova disciplina</h2>
    </div>
    <form wire:submit.prevent="store">
        <div class="my-3">
            <x-input-label for="title" :value="__('Titulo')" />
            <x-text-input wire:model.debounce.750="form.title" id="title" class="block w-full mt-1" type="text"
                name="title" required autofocus autocomplete="title" />
            <x-input-error :messages="$errors->get('form.title')" class="mt-2" />
        </div>
        <div class="my-3">
            <x-input-label for="description" :value="__('Descrição')" />
            <x-text-area-input wire:model.debounce.750="form.description" id="description" class="block w-full mt-1"
                name="description" required />
            <x-input-error :messages="$errors->get('form.description')" class="mt-2" />
        </div>
        <div class="flex justify-end my-3">
            <div class="flex items-center gap-2">
                <x-mary-button class="text-white bg-pink-600 hover:bg-pink-900" type="submit">
                    registrar
                </x-mary-button>
                <div wire:loading>
                    <span class="text-pink-600 loading loading-spinner loading-xs"></span>
                </div>
            </div>
        </div>
    </form>
</x-mary-modal>
