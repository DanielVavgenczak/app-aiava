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
        <div class="flex justify-end my-3" id="content-input-save" id="content-input-save">
            <x-mary-button class="text-white bg-pink-600 hover:bg-pink-900" type="submit">
                registrar
            </x-mary-button>
        </div>
    </form>
    <!-- Overlay tourguide -->
    <div x-data x-show="$store.tourguide.openMai" class="fixed inset-0 z-50 "
        :class="!$store.tourguide.helperContentInit ? 'bg-black bg-opacity-80' : 'bg-none'">
        <img src="{{ asset('images/ai-sos.png') }}" class="absolute right-0 w-64 bottom-4 ">
    </div>
    <!-- Popover do Tour -->
    <div id="tour-popover" x-data x-show="$store.tourguide.popoverContentCreate"
        class="absolute z-50 flex flex-col w-auto gap-4 p-6 text-gray-800 bg-white rounded-tl-lg rounded-tr-lg rounded-bl-lg">
        <p id="tour-message" class="break-all"></p>
        <div class="flex items-center justify-between gap-4 p-3 bg-gray-200" x-show="$store.tourguide.helperContent">
            ver com o paulo aqui
        </div>
    </div>
</div>
