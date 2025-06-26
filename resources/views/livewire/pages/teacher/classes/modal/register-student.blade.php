<?php

use Livewire\Volt\Component;
use App\Models\Teacher\{Classe, Student};
use Livewire\Attributes\{On, Validate};

new class extends Component {
    public bool $open = false;

    #[Validate('required', message: 'Nome do estudante é obrigatório!')]
    #[Validate('min:5', message: 'O nome do estudante deve ter no minimo 5 caracteres')]
    public string $studentName = '';
    #[Validate('required', message: 'O e-mail do estudante é obrigatório!')]
    #[Validate('email', message: 'O e-mail do estudante deve ser valido!')]
    public string $studentEmail = '';
    public string $studentPhone = '';
    public array $students = [];

    public Classe $classe;

    #[On('open-form-register-student')]
    public function openForm()
    {
        $this->open = true;
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
                        'class_id' => $this->classe->id,
                        'teacher_id' => Auth::user()->id,
                        'discipline_id' => $this->classe->discipline_id,
                    ]);
                }
            });
            $this->reset('studentName', 'studentEmail', 'studentPhone', 'students');
            $this->dispatch('student-registed-success');
            $this->open = false;
        } catch (\Exception $e) {
            dd('Deu erro ao cadastrar estuante', $e->getMessage());
        }
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

<x-mary-modal wire:model="open" title="Nova aluno" subtitle="Registrar novo aluno" box-class="w-11/12  max-w-6xl">

    <form wire:submit.prevent="storeStudent" wire:loading.class="flex flex-col justify-center items-center">
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
                <x-mary-input label="Telefone  (opcional)" class="text-gray-700" wire:model="studentPhone"
                    class="w-full !outline-none" wire:keydown.enter="addStudent" />
            </div>
            <div class="col-span-1">
                <x-mary-button icon="o-plus" label="adicionar" type="button" class="btn-sm btn-primary"
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
    </form>
</x-mary-modal>
