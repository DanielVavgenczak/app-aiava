<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Validate, On};
use App\Models\Teacher\{Classe, Student};
use App\Models\{Discipline};

new class extends Component {
    #[Validate('required', message: 'O nome da classe é obrigatório!')]
    #[Validate('min:5', message: 'O nome da classe deve ter no minimo 5 caracteres')]
    public string $class_name = '';
    public bool $open = false;

    #[Validate('required', message: 'Disciplina é obrigatória!')]
    public string $disciplineId = '';
    public array $disciplines = [];
    public string $classId = '';
    public string $classNameAdded = '';

    #[Validate('required', message: 'Nome do estudante é obrigatório!')]
    #[Validate('min:5', message: 'O nome do estudante deve ter no minimo 5 caracteres')]
    public string $studentName = '';
    #[Validate('required', message: 'O e-mail do estudante é obrigatório!')]
    #[Validate('email', message: 'O e-mail do estudante deve ser valido!')]
    public string $studentEmail = '';
    public string $studentPhone = '';
    public array $students = [];

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
            $class = Classe::create(
                attributes: [
                    'name' => $this->class_name,
                    'discipline_id' => $this->disciplineId,
                    'teacher_id' => Auth::user()->id,
                ],
            );
            sleep(1);
            $this->classId = $class->id;
            $this->classNameAdded = $class->name;
            $this->dispatch('action-event-classe', type: 'create');
        } catch (\Exception $e) {
            dd('Deu erro ao cadastrar', $e->getMessage());
        }
    }
    public function addStudent()
    {
        $this->validateMultiple(['studentName', 'studentEmail']);
        $students = [
            'name' => $this->studentName,
            'email' => $this->studentEmail,
            'phone' => $this->studentPhone,
        ];
        $this->students[] = $students;
        $this->studentName = '';
        $this->studentEmail = '';
        $this->studentPhone = '';
        sleep(2);
    }
    /**
     * Remove student in list
     * **/
    public function removeStudent(int $index)
    {
        unset($this->students[$index]);
        $this->students = array_values($this->students);
        sleep(1);
    }

    public function storeStudent()
    {
        try {
            DB::transaction(function () {
                foreach ($this->students as $student) {
                    Student::create([
                        'full_name' => $student['name'],
                        'email' => $student['email'],
                        'phone' => $student['phone'] ? $student['phone'] : null,
                        'class_id' => $this->classId,
                        'teacher_id' => Auth::user()->id,
                        'discipline_id' => $this->disciplineId,
                    ]);
                }
            });
            $this->dispatch('student-created-success');
            sleep(2);
            $this->resetForm();
        } catch (\Exception $e) {
            dd('Deu erro ao cadastrar estuante', $e->getMessage());
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
        $this->classId = '';
        $this->classNameAdded = '';
        $this->studentName = '';
        $this->studentEmail = '';
        $this->studentPhone = '';
        $this->students = [];
        $this->open = false;
    }

    public function cancelRegister()
    {
        $this->reset('studentName', 'studentEmail', 'studentPhone', 'students');
        $this->class_name = '';
        $this->classId = '';
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
    @if (empty($classId))
        <form wire:submit.prevent="storeClasse" wire:loading.class="flex flex-col justify-center items-center">
            <div class="flex flex-col" wire:loading.remove>
                <div class="mb-4">
                    @if (count($disciplines) > 1)
                        <label class="block mb-1 text-sm font-medium text-gray-700">Disciplina</label>
                        <x-mary-select wire:model="disciplineId" placeholder="Selecione a disciplina" :options="$disciplines"
                            option-label="title" option-value="id" placeholder-value="0" class="w-full !outline-none" />
                    @else
                        <label class="block mb-1 text-sm font-medium text-gray-700">Disciplina selecionada</label>
                        <x-mary-select wire:model="disciplineId" :options="$disciplines" option-label="title"
                            option-value="id" placeholder-value="title" class="w-full !outline-none" />
                    @endif
                </div>
                <div class="my-3" id="classe-input-name">
                    <x-mary-input wire:model="class_name" label="Turma" class="class-input-name !outline-none"
                        autocomplete="title" />
                </div>
                <div class="flex gap-2 items-center justify-end my-3" id="discipline-save">
                    <div class="flex items-center gap-2">
                        <button class="btn btn-sm text-gray-500 btn-ghost cursor-pointer capitalize"
                            x-on:click.prevent="$wire.cancelRegister()" id="create-cancel">
                            Cancelar
                        </button>
                    </div>
                    <div class="flex  items-center gap-2">
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
    @endif
    @if (!empty($classId) && !empty($classNameAdded))
        <div class="bg-gray-100 p-3">
            <div class="mb-4 flex justify-between items-center">
                <div>
                    <h2 class="text-lg font-semibold tracking-wide text-gray-700">{{ $classNameAdded }}</h2>
                    <p class="text-sm font-normal text-gray-600">Adicionar alunos</p>
                </div>
                <button class="capitalize text-xs btn btn-sm bg-pink-600 text-gray-50"
                    x-on:click="$wire.cancelRegister()">
                    Adicionar Alunos Depois
                </button>
            </div>
            <div class="mb-3 grid grid-cols-12 gap-2 items-baseline" wire:target="storeStudent" wire:loading,remove>
                <div class="col-span-4">
                    <x-mary-input label="Nome do aluno" wire:model="studentName" class="w-full !outline-none"
                        wire:keydown.enter="addStudent" />
                </div>
                <div class="col-span-4">
                    <x-mary-input label="E-mail do aluno" class="text-gray-700" wire:model="studentEmail"
                        class="w-full !outline-none" wire:keydown.enter="addStudent" />
                </div>
                <div class="col-span-3">
                    <x-mary-input label="Telefone" class="text-gray-700" wire:model="studentPhone"
                        class="w-full !outline-none" wire:keydown.enter="addStudent" hint="Opcional" />
                </div>
                <div class="col-span-1">
                    <x-mary-button icon="o-plus" type="button" class="btn-sm btn-primary"
                        wire:click.prevent="addStudent" spinner />
                </div>
            </div>

            @if (count($students) > 0)
                <div class="">
                    <ul>
                        @foreach ($students as $index => $student)
                            <li class="flex items-center justify-between hover:bg-gray-200">
                                <div class="flex justify-between p-4 w-1/2">
                                    <span class="text-sm text-gray-700 tracking-wide">{{ $student['name'] }}</span>
                                    <span class="text-sm text-gray-700">{{ $student['email'] }}</span>
                                    <span class="text-sm text-gray-700">{{ $student['phone'] }}</span>
                                </div>
                                <x-mary-button icon="o-trash" class="btn btn-sm btn-ghost text-red-400"
                                    wire:click="removeStudent({{ $index }})" spinner />
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="self-end mt-4" wire:loading.remove>
                    <button class="btn btn-sm btn-ghost tracking-wider capitalize"
                        x-on:click="$wire.open = false">cancelar</button>
                    <button type="button" class="btn btn-sm bg-pink-700 capitalize text-gray-50 tracking-wider"
                        wire:click.prevent="storeStudent">registrar</button>
                </div>
                <div wire:loading wire:target="storeStudent" class="w-full flex justify-center">
                    <div class="flex flex-col justify-center items-center gap-4">
                        <x-mary-loading class="loading-spinner loading-xl text-pink-700" />
                        <span class="text-xs capitalize font-semibold tracking-wider text-pink-700">cadastrando
                            estudantes</span>
                    </div>
                </div>
        </div>
    @endif
    @endif
</x-mary-modal>
