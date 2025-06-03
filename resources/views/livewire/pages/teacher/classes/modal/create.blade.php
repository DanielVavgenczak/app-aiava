<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use App\Models\Teacher\{Classe, Student};
use App\Models\{Discipline};

new class extends Component {
    public string $name = '';
    public bool $open = false;
    public string $disciplineId = '';
    public array $disciplines = [];
    public string $classId = '';
    public string $classNameAdded = '';
    public string $studentName = '';
    public string $studentEmail = '';
    public array $students = [];

    #[On('open-form-class-create')]
    public function openForm($open)
    {
        $this->open = $open;
        $this->getDisciplines();
    }

    public function close()
    {
        $this->open = false;
    }

    public function storeClasse()
    {
        try {
            $class = Classe::create([
                'name' => $this->name,
                'discipline_id' => $this->disciplineId,
                'teacher_id' => Auth::user()->id,
            ]);
            sleep(4);
            $this->classId = $class->id;
            $this->classNameAdded = $class->name;
        } catch (\Exception $e) {
            dd('Deu erro ao cadastrar', $e->getMessage());
        }
    }
    public function addStudent()
    {
        $students = [
            'name' => $this->studentName,
            'email' => $this->studentEmail,
        ];
        $this->students[] = $students;
        $this->studentName = '';
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
        //dd($this->students);
        try {
            DB::transaction(function () {
                foreach ($this->students as $student) {
                    Student::create([
                        'full_name' => $student['name'],
                        'email' => $student['email'],
                        'class_id' => $this->classId,
                        'teacher_id' => Auth::user()->id,
                        'discipline_id' => $this->disciplineId,
                    ]);
                }
            });
            $this->dispatch('student-created-success');
            sleep(5);
            $this->resetForm();
        } catch (\Exception $e) {
            dd('Deu erro ao cadastrar estuante', $e->getMessage());
        }
    }
    private function getDisciplines()
    {
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
        $this->name = '';
        $this->classId = '';
        $this->classNameAdded = '';
        $this->studentName = '';
        $this->students = [];
        $this->open = false;
    }
}; ?>

<x-mary-modal wire:model="open" title="Nova turma" subtitle="Crie uma nova turma" box-class="w-11/12  max-w-6xl">
    @if ($classId === '')
        <form wire:submit.prevent="storeClasse" wire:loading.class="flex flex-col justify-center items-center">
            <div class="flex flex-col" wire:loading.remove>
                <div class="mb-4">
                    <label class="block mb-1 text-sm font-medium text-gray-700">Disciplina</label>
                    <x-mary-select wire:model="disciplineId" placeholder="Selecione a disciplina" :options="$disciplines"
                        option-label="title" option-value="id" placeholder-value="0" class="w-full !outline-none" />
                </div>
                <div class="my-3" id="classe-input-name">
                    <x-mary-input wire:model="name" label="Turma" class="class-input-name !outline-none"
                        autocomplete="title" />
                </div>
                <div class="flex gap-2 items-center justify-end my-3" id="discipline-save">
                    <div class="flex items-center gap-2">
                        <button class="text-gray-500 btn-ghost cursor-pointer" x-on:click.prevent="$wire.open = false"
                            id="create-cancel">
                            Cancelar
                        </button>
                    </div>
                    <div class="flex  items-center gap-2">
                        <button class="btn text-gray-50 capitalize bg-pink-700 hover:bg-pink-900" type="submit"
                            id="create-confirm">
                            cadastrar
                        </button>
                    </div>
                </div>
            </div>
            <div wire:loading wire:target="storeClasse">
                <div class="flex flex-col items-center gap-4">
                    <x-mary-loading class="loading-spinner loading-xl text-pink-700" />
                    <span class="text-xs uppercase font-semibold tracking-wider text-pink-700">cadastrando</span>
                </div>
            </div>
        </form>
    @endif
    @if ($classId !== '')
        <div class="bg-gray-100 p-3">
            <div class="mb-4">
                <h2 class="text-lg font-semibold tracking-wide text-gray-700">{{ $classNameAdded }}</h2>
                <p class="text-sm font-normal text-gray-600">Adicionar alunos</p>
            </div>
            <div class="mb-3 grid grid-cols-12 gap-2 ">
                <div class="col-span-5">
                    <x-mary-input label="Nome do aluno" wire:model="studentName" class="w-full !outline-none"
                        wire:keydown.enter="addStudent" />
                </div>
                <div class="col-span-5">
                    <x-mary-input label="E-mail do aluno" wire:model="studentEmail" class="w-full !outline-none"
                        wire:keydown.enter="addStudent" />
                </div>
                <div class="col-span-1 flex justify-center items-end">
                    <x-mary-button icon="o-plus" type="button" class="btn-md btn-primary"
                        wire:click.prevent="addStudent" spinner />
                </div>
            </div>
            <div class="">
                @if (count($students) > 0)
                    <ul>

                        @foreach ($students as $index => $student)
                            <li>
                                <div>

                                    <span>{{ $student['name'] }}</span>
                                    <span>{{ $student['email'] }}</span>
                                </div>

                                <x-mary-button icon="o-trash" class="btn-sm btn-ghost text-red-400"
                                    wire:click="removeStudent({{ $index }})" spinner />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="self-end mt-4" wire:loading.remove>
                <button class="btn btn-ghost tracking-wider capitalize"
                    x-on:click="$wire.open = false">cancelar</button>
                <button type="button" class="btn bg-pink-700 capitalize text-gray-50 tracking-wider"
                    wire:click.prevent="storeStudent">registrar</button>
            </div>
            <div class="flex flex-col items-center gap-4">
                <div wire:loading wire:target="storeStudent">
                    <x-mary-loading class="loading-dots loading-lg text-pink-700" />
                    <span class="text-sm uppercase font-semibold tracking-wider text-pink-700">cadastrando
                        estudantes</span>
                </div>
            </div>
        </div>
    @endif
</x-mary-modal>
