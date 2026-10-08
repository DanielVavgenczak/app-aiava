<?php

use Livewire\Component;
use App\Livewire\Forms\DisciplineForm;
use App\Models\Teacher\{Discipline};
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

new class extends Component {
    public bool $open = false;
    public Discipline $discipline;
    public DisciplineForm $form;

    #[On('edit-content-modal')]
    public function open($id)
    {
        if ($this->discipline->id === $id) {
            $this->open = true;
        }
    }

    #[On('to-close')]
    public function toClose()
    {
        $this->open = false;
    }

    public function mount(Discipline $discipline)
    {
        $this->discipline = $discipline;
        $this->form->disciplineId = $discipline->id;
        $this->form->title = $discipline->title;
    }

    public function update()
    {
        $this->authorizeForUser(Auth::guard('teacher')->user(), 'update', $this->discipline);

        if (! $this->form->disciplineUpdate()) {
            $this->open = false;
            $this->dispatch('discipline-event', type: 'error', msg: 'Disciplina não encontrada!');
            return;
        }

        $this->discipline->refresh();
        $this->open = false;
        $this->dispatch('discipline-event', type: 'update', msg: 'Disciplina editada com sucesso!');
    }
}; ?>

<x-mary-modal wire:model="open" title="Editar" subtitle="Edite a disciplina: {{ $discipline->title ?? 'Discipline' }}">
    <form wire:submit.prevent="update">
        <div id="discipline-edit-form-actions-{{ $discipline->id ?? '' }}">
        <div wire:loading.remove class="my-3">
            <x-mary-input for="form.title" :value="__('Titulo')" wire:model="form.title" class="!outline-none" />
            @error('form.title')
                <span class="text-xs text-red-600">{{ $message }}</span>
            @enderror
        </div>
        <div class="flex justify-end my-3">
            <div class="flex items-center gap-2">
                <button class="cursor-pointer btn btn-sm btn-ghost" x-on:click="$wire.open = false"
                    id="discipline-edit-cancel-{{ $discipline->id ?? '' }}">
                    Cancelar
                </button>
                <button class="bg-blue-400 btn btn-sm text-gray-50" type="submit"
                    id="discipline-edit-form-send-{{ $discipline->id ?? '' }}">
                    Editar
                </button>
            </div>
        </div>
        <div wire:loading class="flex flex-col justify-center w-full text-center">
            <div class="mb-2">
                <x-mary-loading class="text-blue-400 loading-dots" />
            </div>
            <span class="text-xs text-blue-400">Editando</span>
        </div>
        </div>
    </form>
</x-mary-modal>
