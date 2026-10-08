<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Livewire\Forms\{DisciplineForm};
use App\Models\Teacher\Discipline;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public DisciplineForm $form;
    public bool $open = false;
    public $diciplineIdToManager;

    #[On('create-content-modal')]
    public function open($open)
    {
        $this->open = $open;
        if ($open) {
            $this->form->reset();
            $this->resetValidation();
        }
    }

    #[On('close-create-with-tour')]
    #[On('discipline-close-modal-create')]
    public function cancel()
    {
        $this->open = false;
        $this->form->reset();
        $this->resetValidation();
    }

    public function store()
    {
        $teacher = Auth::guard('teacher')->user();
        $this->authorizeForUser($teacher, 'create', Discipline::class);

        $result = $this->form->disciplineStore();

        if (! $result) {
            return;
        }

        $this->open = false;
        $this->dispatch('discipline-send-id-tour', discipline_id: $this->form->getDisciplineId());
        $this->dispatch('discipline-event', type: 'create', msg: 'Disciplina criada com sucesso!');
        $this->form->resetForm();
        $this->resetValidation();
    }
}; ?>
<x-mary-modal wire:model="open" title="Nova disciplina" subtitle="Registre uma disciplina">
    <form wire:submit.prevent="store"  id="discipline-input-title">
        <div wire:loading.remove>
            <div class="flex flex-col gap-1">
              <input wire:model="form.title" id="title_discipline" placeholder="Título da disciplina"
              class="input title !outline-none focus:border-gray-400 w-full" autocomplete="title" />
            </div>
            @error('form.title')
            <div id="title_discipline_error">
              <span class="text-xs text-red-600">{{ $message }}</span>
            </div>
            @enderror
        </div>
        <div class="flex items-center justify-end gap-2 my-3" id="discipline-save">
            <div class="flex items-center gap-2" id="create-cancel" wire:loading.remove>
                <button type="button" class="cursor-pointer btn btn-sm btn-ghost" x-on:click="$wire.cancel()">
                    Cancelar
                </button>
            </div>
            <div id="create-confirm" wire:loading.remove>
                <button class="bg-blue-600 btn btn-sm text-gray-50 hover:bg-blue-900" type="submit">
                    Registrar
                </button>
                <div id="{{ $diciplineIdToManager }}"></div>
            </div>
            <div wire:loading wire:target="store" class="flex flex-col justify-center w-full text-center">
              <span class="text-xs tracking-wider text-blue-600">Registrando</span>
                <div class="mb-2">
                    <x-mary-loading class="text-blue-600 loading-dots" />
                </div>
            </div>
        </div>
    </form>
</x-mary-modal>
