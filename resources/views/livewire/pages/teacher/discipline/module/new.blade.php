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
        $this->js("console.log('chamando')");
    }
    #[On('to-teste-register')]
    public function save()
    {
        $validate = $this->validate([
            'title' => 'required|string|min:3',
        ]);
        $module = Module::create([
            'title' => $this->title,
            'discipline_id' => $this->disciplineId,
        ]);

        $this->pull('open', 'title');
        session()->flash('module_created', true);
        $this->redirect(route('teacher.discipline.show', $this->disciplineId), navigate: true);
    }
}; ?>
<x-mary-modal wire:model="open" class="" data-tour="new-lesson-{{ $disciplineId }}">
    <div class="mb-5">
        <h3 class="text-gray-800 text-lg font-semibold tracking-wide">Nova aula </h3>
    </div>
    <div>
        <form wire:submit.prevent="save">
            <div class="my-3" id="lesson-title">
                <x-input-label for="lesson-input-title" :value="__('Titulo')" />
                <x-text-input wire:model.live="title" id="lesson-input-title"
                    class="border-[1px] focus:border-pink-500 focus:outline focus:outline-pink-50 px-3 py-2 block w-full mt-1"
                    type="text" name="title" required autofocus autocomplete="title" />
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>
            <div class="flex items-center justify-end gap-4 mt-5" id="lesson-actions-buttons">
                <button x-on:click="$wire.open = false" id="module-input-cancel" class="btn ">Cancelar</button>
                <button type="submit" spinner id="module-input-create" class="btn ">Cadastrar
                </button>
            </div>
        </form>
    </div>
</x-mary-modal>
