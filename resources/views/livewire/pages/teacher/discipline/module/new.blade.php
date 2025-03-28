<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use App\Models\{Module};

new class extends Component {
    public bool $open = false;
    public string $title = '';
    public string $disciplineId;

    #[On('open-modal-module')]
    public function openModal($open): void
    {
        $this->open = $open;
        $this->module_id = '';
        $this->title = '';
    }

    public function save()
    {
        $validate = $this->validate([
            'title' => 'required|string|min:3',
        ]);
        $module = Module::create([
            'title' => $this->title,
            'discipline_id' => $this->disciplineId,
        ]);
        $this->dispatch('module-created');
        $this->pull('open', 'title');
        $this->dispatch('new-module-created');
    }
}; ?>
<x-mary-modal wire:model="open" class="">
    <div class="mb-5">
        <h3>Novo modulo </h3>
    </div>
    <div>
        <form wire:submit.prevent="save">
            <div class="my-3" id="module-input-title">
                <x-input-label for="title" :value="__('Titulo')" />
                <x-text-input wire:model.live="title" id="title" class="block w-full mt-1" type="text"
                    placeholder="Ex: Teoria Musical" name="title" required autofocus autocomplete="title" />
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>
            <div class="flex items-center justify-end gap-4 mt-5">
                <x-mary-button label="Cancel" @click="$wire.open = false" />
                <x-mary-button type="submit" spinner id="module-input-send">Cadastrar</x-mary-button>
            </div>
        </form>
    </div>
</x-mary-modal>
