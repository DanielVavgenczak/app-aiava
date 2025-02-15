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

    <div x-data class="fixed inset-0 z-40 bg-black bg-opacity-50" x-show="$store.tourguide.openMai">
        <!-- Centralizando a div -->
        <div
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-lg sm:w-1/2 bg-white rounded-lg shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">

            <h2 class="text-lg font-semibold text-gray-700">Precisa criar uma disciplina?</h2>
            <div class="flex items-center justify-center p-4 bg-gray-100">
                <x-mary-button class="w-full border-gray-700 btn-outline sm:w-auto" label="Vamos criar uma disciplina?"
                    @click="$store.tourguide.startTourCreateContent()" />
            </div>
        </div>
        <img src="{{ asset('images/ai-sos.png') }}" class="fixed right-0 w-64 bottom-4 sm:w-48">
    </div>
</div>
