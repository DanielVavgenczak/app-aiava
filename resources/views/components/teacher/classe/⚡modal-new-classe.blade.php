<?php

use Livewire\Component;
use Livewire\Attributes\{Validate, On};
use App\Models\Teacher\{Classe, Student,Discipline};
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    #[Validate('required', message: 'O nome da classe é obrigatório!')]
    #[Validate('min:5', message: 'O nome da classe deve ter no minimo 5 caracteres')]
    public string $class_name = '';
    public bool $open = false;

    #[Validate('required', message: 'Disciplina é obrigatória!')]
    public string $disciplineId = '';
    public array $disciplines = [];

    #[On('teacher::create-classe')]
    public function openForm($disciplineId)
    {
        $this->open = true;
        $this->getDisciplines($disciplineId);
    }

    public function storeClasse()
    {
        $this->validateMultiple(['class_name', 'disciplineId']);
        try {
            Classe::create(
                attributes: [
                    'name' => $this->class_name,
                    'discipline_id' => $this->disciplineId,
                    'teacher_id' => Auth::guard('teacher')->user()->id,
                ],
            );
            $this->dispatch('action-event-classe', type: 'create');
            $this->reset('class_name', 'disciplineId');
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
        if (!empty($id)) {
            $this->disciplineId = $id;
            $this->disciplines = Discipline::with('students')
                ->where('id', $id)
                ->where('teacher_id', Auth::guard('teacher')->user()->id)
                ->get()
                ->toArray();
            return;
        }
        $this->disciplines = Discipline::with('students')->where(column: 'teacher_id', operator: Auth::guard('teacher')->user()->id)->get()->toArray();
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

    #[On('classe-create-cancel')]
    public function cancelRegister()
    {
        $this->open = false;
        $this->class_name = '';
        $this->disciplines = [];
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

<x-mary-modal wire:model="open" title="Nova turma" subtitle="Registre uma nova turma">
    <form wire:submit.prevent="storeClasse" >
        <div class="flex flex-col" wire:loading.remove id="register-classe-inputs">
            <div class="mb-4" id="classe-input-discipline">
                   <label class="block mb-1 text-sm font-medium text-gray-700">Disciplina</label>
                    <x-mary-select wire:model="disciplineId" placeholder="Selecione a disciplina" :options="$disciplines"
                        option-label="title" option-value="id" placeholder-value="0" class="w-full !outline-none" />
            </div>
            <div class="my-3" id="classe-input-name">
                <x-mary-input wire:model="class_name" label="Titulo" placeholder="Nome da turma"
                    class="class-input-name !outline-none" autocomplete="title" />
            </div>
            <div class="flex gap-2 items-center justify-end my-3" id="classe-input-actions">
                <div class="flex items-center gap-2" id="classe-input-cancel">
                    <button class="btn btn-sm text-gray-500 btn-ghost cursor-pointer capitalize"
                        x-on:click.prevent="$wire.cancelRegister" id="create-cancel">
                        Cancelar
                    </button>
                </div>
                <div class="flex items-center gap-2" id="classe-input-save">
                    <button class="btn btn-sm text-gray-50 capitalize bg-blue-400 hover:bg-blue-500" type="submit"
                        id="create-confirm">
                        cadastrar
                    </button>
                </div>
            </div>
        </div>
        <div wire:loading wire:target="storeClasse">
            <div class="flex flex-col items-center gap-4">
                <x-mary-loading class="loading-spinner loading-xl text-pink-700" />
                <span class="text-xs capitalize font-semibold tracking-wider text-pink-700">cadastrando</span>
            </div>
        </div>
    </form>
</x-mary-modal>
