<?php

use Livewire\Component;
use Livewire\Attributes\{Validate, On};
use App\Models\Teacher\{Classe, Student,Discipline};
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    #[Validate('required', message: 'O nome da classe é obrigatório!')]
    #[Validate('min:5', message: 'O nome da classe deve ter no minimo 5 caracteres')]
    public string $classe_name = '';
    public bool $open = false;
    public ?Classe $classe = null;
    #[Validate('required', message: 'Disciplina é obrigatória!')]
    public string $disciplineId = '';
    public array $disciplines = [];

    #[On('edit-classe-modal')]
    public function open($id)
    {
        if ($this->classe->id === $id || !$this->classe->id === '') {
            $this->open = true;
            $this->getDisciplines($this->classe->discipline_id);
        }
    }

    public function mount(Classe $classe)
    {
        $this->fill($classe);
        $this->classe_name = $classe->name;
        $this->disciplineId = $classe->discipline_id;
    }

    public function updateClasse()
    {
        $this->js('console.log("Atualizando classe...")');
        $this->validateMultiple(['classe_name', 'disciplineId']);
        try {
            $this->classe->update([
                'name' => $this->classe_name,
                'discipline_id' => $this->disciplineId,
            ]);
            sleep(1);
            $this->dispatch('action-event-classe', type: 'update');
            $this->open = false;
        } catch (\Exception $e) {
            $this->open = false;
            if (!empty($e->getMessage())) {
                $this->error('Erro interno no sistema', 'Error interno no sistema para essa funcionalidade por favor tente novamente mais tarde', position: 'toast-top toast-end', timeout: 8000);
            }
        }
    }

    private function getDisciplines(string $id = '')
    {
        $this->disciplines = Discipline::with('students')
            ->where('teacher_id', Auth::guard('teacher')->user()->id)
            ->get()
            ->toArray();
    }
    /**
     * Clean labels
     * **/
    public function resetForm()
    {
        $this->disciplineId = '';
        $this->class_name = '';
        $this->open = false;
    }

    #[On('classe-edit-cancel')]
    public function cancelEdit()
    {
        $this->open = false;
    }

    protected function validateMultiple($fields)
    {
        $validate = [];
        foreach ($fields as $field) {
            $validateData = $this->validateOnly($field);
            $validate[key($validateData)] = current($validateData);
        }
        return $validate;
    }
}; ?>

<x-mary-modal wire:model="open" title="Editar turma" subtitle="Edite informações da turma">
    <form wire:submit.prevent="updateClasse" wire:loading.class="flex flex-col justify-center items-center" id="classe-edit-form-{{ $classe->id ?? '' }}">
        <div class="flex flex-col" wire:loading.remove >
            <div class="mb-4" id="classe-input-discipline-{{ $classe->id ?? '' }}">
                <label class="block mb-1 text-sm font-medium text-gray-700">Disciplina selecionada</label>
                <x-mary-select wire:model="disciplineId" :options="$disciplines" option-label="title" option-value="id"
                    placeholder-value="title" class="w-full !outline-none" />

            </div>
            <div class="my-3" id="classe-input-name-{{ $classe->id ?? '' }}">
                <x-mary-input wire:model="classe_name" label="Turma" class="class-input-name !outline-none"
                    autocomplete="title" />
            </div>
            <div class="flex gap-2 items-center justify-end my-3" id="classe-edit-actions-{{ $classe->id ?? '' }}">
                <div class="flex items-center gap-2" id="classe-edit-cancel">
                    <button class="btn btn-sm text-gray-500 btn-ghost cursor-pointer capitalize"
                        x-on:click.prevent="$wire.cancelEdit()" id="edit-cancel">
                        cancelar
                    </button>
                </div>
                <div class="flex  items-center gap-2" id="classe-edit-confirm">
                    <button class="btn btn-sm text-gray-50 capitalize bg-blue-400 hover:bg-blue-500" type="submit"
                        id="edit-confirm">
                        editar
                    </button>
                </div>
            </div>
        </div>
        <div wire:loading wire:target="updateClasse">
            <div class="flex flex-col items-center gap-4">
                <x-mary-loading class="loading-spinner loading-xl text-blue-700" />
                <span class="text-xs capitalize font-semibold tracking-wider text-blue-700">editando</span>
            </div>
        </div>
    </form>
</x-mary-modal>
