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
        $this->dispatch('send-id-discipline', discipline_id: $this->form->getDisciplineId());
        $this->form->reset();
        sleep(2);
    }
}; ?>
<x-mary-modal wire:model="openCreateContent" class="">
    <div>
        <h2 class="text-gray-800 text-lg font-semibold tracking-wide">Nova disciplina</h2>
    </div>
    <form wire:submit.prevent="store">
        <div class="my-3" id="discipline-input-title">
            <x-input-label for="title" class="text-gray-600 text-sm" :value="__('Titulo')" />
            <x-text-input wire:model.debounce.750="form.title" id="title"
                class="border-[1px] focus:border-pink-500 focus:outline focus:outline-pink-50 px-3 py-2 block w-full mt-1"
                type="text" name="title" required autofocus autocomplete="title" />
            <x-input-error :messages="$errors->get('form.title')" class="mt-2" />
        </div>
        <div class="flex justify-end my-3" id="discipline-save">
            <div class="flex items-center gap-2">
                <x-mary-button class="text-gray-500 btn-ghost" @click="$wire.openCreateContent = false"
                    id="create-cancel">
                    Cancelar
                </x-mary-button>
            </div>
            <div class="flex items-center gap-2">
                <x-mary-button class="text-white capitalize bg-pink-600 hover:bg-pink-900" type="submit"
                    id="create-confirm" label="Cadastrar" spinner="store" />
                <div wire:loading wire:target="store" class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-pink-600 tracking-wider">Registrando disciplina</span>
                    <x-mary-loading class="loading-dots text-pink-600" />
                </div>
            </div>
        </div>
    </form>
</x-mary-modal>
