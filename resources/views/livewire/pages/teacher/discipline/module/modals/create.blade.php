<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{On, Validate};
use App\Models\{Module};

new class extends Component {
    public bool $open = false;

    #[Validate('required', message: 'O título do aula é obrigatório!')]
    public string $title = '';
    public string $disciplineId;

    #[On('open-module-create')]
    public function openModal($open): void
    {
        $this->open = $open;
        $this->module_id = '';
        $this->title = '';
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
        $this->dispatch('module-event', type: 'create', msg: 'Aula criada com sucesso!');
        $this->open = false;
        //$this->redirect(route('teacher.discipline.show', $this->disciplineId), navigate: true);
    }
    public function cancel()
    {
        $this->reset('title', 'open');
    }
}; ?>
<x-mary-modal wire:model="open" title="Nova aula" subtitle="Crie uma nova aula"
    data-tour="new-lesson-{{ $disciplineId }}">
    <div>
        <form wire:submit.prevent="save">
            <div class="my-3" id="lesson-title">
              <input 
                type="text" 
                class="input w-full !outline-none lesson-input-title focus:border-gray-400" 
                wire:model="title" 
                placeholder="Título da aula"/>                
            </div>
            <div class="flex items-center justify-end gap-4 mt-5" id="lesson-actions-buttons">
                <button type="button" wire:click="cancel" id="module-input-cancel"
                    class="btn btn-sm bg-ghost">Cancelar</button>
                <button type="submit" spinner id="module-input-create"
                    class="btn btn-sm text-gray-50 bg-pink-600">Registrar
                </button>
            </div>
        </form>
    </div>
</x-mary-modal>
