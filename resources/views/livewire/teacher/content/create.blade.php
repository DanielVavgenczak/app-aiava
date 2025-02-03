<?php

use Livewire\Volt\Component;
use App\Livewire\Forms\{DisciplineForm};

new class extends Component {
    public DisciplineForm $form;

    public function store()
    {
        $this->form->disciplineStore();

        return $this->redirect(route('teacher.content'));
    }
}; ?>

<div class="w-1/2">
    <form wire:submit.prevent="store">
        <div class="my-3" id="content-input-title">
            <x-input-label for="title" :value="__('Titulo')" />
            <x-text-input wire:model.live="form.title" id="title" class="block w-full mt-1" type="text" name="title"
                required autofocus autocomplete="title" />
            <x-input-error :messages="$errors->get('form.title')" class="mt-2" />
        </div>
        <div class="my-3" id="content-input-description">
            <x-input-label for="description" :value="__('Descrição')" />
            <x-text-area-input wire:model.live="form.description" id="description" class="block w-full mt-1"
                name="description" required />
            <x-input-error :messages="$errors->get('form.description')" class="mt-2" />
        </div>
        <div class="flex justify-end my-3">
            <x-mary-button class="text-white bg-pink-600 hover:bg-pink-900" type="submit" id="content-input-save">
                registrar
            </x-mary-button>
        </div>
    </form>
</div>
