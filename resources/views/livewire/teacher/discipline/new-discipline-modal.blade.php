<?php

use Livewire\Volt\Component;
use Livewire\Attribute\{On};
use Livewire\Attributes\{Validate};
use App\Livewire\Forms\{DisciplineForm};

new class extends Component {
    public DisciplineForm $form;
    public bool $myModal2 = false;
    #[On('open-modal-new-discipline')]
    public function open(bool $open)
    {
        $this->myModal2 = $open;
    }
    public function store()
    {
        $this->form->disciplineStore();

        return $this->redirect(route('teacher.content'));
    }
}; ?>
<x-mary-modal wire:model="myModal2" title="Hello" subtitle="Livewire example" separator>
    <div>Hey!</div>

    <x-slot:actions>
        <x-mary-button label="Cancel" @click="$wire.myModal2 = false" />
        <x-mary-button label="Confirm" class="btn-primary" />
    </x-slot:actions>
</x-mary-modal>
{{-- <div class="w-1/2">
    <form wire:submit.prevent="store">
        <div class="my-3">
            <x-input-label for="title" :value="__('Titulo')" />
            <x-text-input wire:model.live="form.title" id="title" class="block w-full mt-1" type="text" name="title"
                required autofocus autocomplete="title" />
            <x-input-error :messages="$errors->get('form.title')" class="mt-2" />
        </div>
        <div class="my-3">
            <x-input-label for="description" :value="__('Descrição')" />
            <x-text-area-input wire:model.live="form.description" id="description" class="block w-full mt-1"
                name="description" required />
            <x-input-error :messages="$errors->get('form.description')" class="mt-2" />
        </div>
        <div class="flex justify-end my-3">
            <x-mary-button class="btn-primary" type="submit">
                registrar
            </x-mary-button>
        </div>
    </form>
</div> --}}
