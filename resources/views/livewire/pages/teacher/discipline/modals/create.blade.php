<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use App\Livewire\Forms\{DisciplineForm};

new class extends Component {
    public DisciplineForm $form;
    public bool $open = false;
    #[On('create-content-modal')]
    public function open($open)
    {
        $this->open = $open;
    }
    #[On('close-create')]
    public function cancel()
    {
        $this->open = false;
        $this->form->reset();
        $this->js("console.log('Modal closed and form reset');");
    }

    public function store()
    {
        $this->form->disciplineStore();
        $this->open = false;
        $this->dispatch('discipline-event', type: 'create', msg: 'Disciplina criada com sucesso!');
        $this->dispatch('send-id-discipline', discipline_id: $this->form->getDisciplineId());
        $this->form->reset();
        sleep(2);
    }
}; ?>
<x-mary-modal wire:model="open" title="Nova disciplina" subtitle="Registre uma nova disciplina">
    <form wire:submit.prevent="store">
        <div id="discipline-input-title" wire:loading.remove>
            <x-mary-input wire:model="form.title" label="Titulo da disciplina" class="title !outline-none"
                autocomplete="title" />
        </div>
        <div class="flex items-center justify-end gap-2 my-3" id="discipline-save">
            <div class="flex items-center gap-2" id="create-cancel" wire:loading.remove>
                <button type="button" class="cursor-pointer btn btn-sm btn-ghost" x-on:click="$wire.cancel()">
                    Cancelar
                </button>
            </div>
            <div id="create-confirm" wire:loading.remove>
                <button class="bg-pink-600 btn btn-sm text-gray-50 hover:bg-pink-900" type="submit">
                    cadastrar
                </button>
            </div>
            <div wire:loading wire:target="store" class="flex flex-col justify-center w-full text-center">
                <div class="mb-2">
                    <x-mary-loading class="text-pink-600 loading-dots" />
                </div>
                <span class="text-xs tracking-wider text-pink-600">Registrando</span>
            </div>
        </div>
    </form>
</x-mary-modal>
