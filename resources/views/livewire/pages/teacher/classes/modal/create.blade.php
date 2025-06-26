<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Validate, On};
use App\Models\Teacher\{Classe, Student};
use App\Models\{Discipline};
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

    #[On('open-form-class-create')]
    public function openForm($open, $disciplineId)
    {
        $this->open = $open;
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
                    'teacher_id' => Auth::user()->id,
                ],
            );
            sleep(1);
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
                ->where('user_id', Auth::user()->id)
                ->get()
                ->toArray();
            return;
        }
        $this->disciplines = Discipline::with('students')
            ->where('user_id', Auth::user()->id)
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

    public function cancelRegister()
    {
        $this->class_name = '';
        $this->open = false;
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

<x-mary-modal wire:model="open" title="Nova turma" subtitle="Registre uma nova turma" box-class="w-11/12  max-w-6xl"
    persistent>
    <form wire:submit.prevent="storeClasse" wire:loading.class="flex flex-col justify-center items-center">
        <div class="flex flex-col" wire:loading.remove>
            <div class="mb-4" id="classe-input-discipline">
                @if (count($disciplines) > 1)
                    <label class="block mb-1 text-sm font-medium text-gray-700">Disciplina</label>
                    <x-mary-select wire:model="disciplineId" placeholder="Selecione a disciplina" :options="$disciplines"
                        option-label="title" option-value="id" placeholder-value="0" class="w-full !outline-none" />
                @else
                    <label class="block mb-1 text-sm font-medium text-gray-700">Disciplina selecionada</label>
                    <x-mary-select wire:model="disciplineId" :options="$disciplines" option-label="title" option-value="id"
                        placeholder-value="title" class="w-full !outline-none" x-on:disabled="true" disabled />
                @endif
            </div>
            <div class="my-3" id="classe-input-name">
                <x-mary-input wire:model="class_name" label="Turma" class="class-input-name !outline-none"
                    autocomplete="title" />
            </div>
            <div class="flex gap-2 items-center justify-end my-3" id="classe-input-actions">
                <div class="flex items-center gap-2" id="classe-input-cancel">
                    <button class="btn btn-sm text-gray-500 btn-ghost cursor-pointer capitalize"
                        x-on:click.prevent="$wire.cancelRegister()" id="create-cancel">
                        Cancelar
                    </button>
                </div>
                <div class="flex  items-center gap-2" id="classe-input-save">
                    <button class="btn btn-sm text-gray-50 capitalize bg-pink-700 hover:bg-pink-900" type="submit"
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
