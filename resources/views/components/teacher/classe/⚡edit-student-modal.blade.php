<?php

use Livewire\Component;
use App\Models\Teacher\{Classe, Student};
use Livewire\Attributes\{On, Validate};
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    public bool $open = false;

    #[Validate('required', message: 'Nome do estudante é obrigatório!')]
    #[Validate('min:5', message: 'O nome do estudante deve ter no minimo 5 caracteres')]
    public string $studentName = '';
    #[Validate('required', message: 'O e-mail do estudante é obrigatório!')]
    #[Validate('email', message: 'O e-mail do estudante deve ser valido!')]
    public string $studentEmail = '';
    public string $studentPhone = '';

    public Student $student;

    #[On('teacher::edit-student-form')]
    public function open($id)
    {
        if ($this->student->id === $id || !$this->student->id === '') {
            $this->open = true;
        }
    }

    public function mount(Student $student)
    {
        $this->fill($student);
        $this->studentName = $student->full_name;
        $this->studentEmail = $student->email;
        $this->studentPhone = $student->phone ?? '';
    }

    /**
     * TODO: Futuramente verificar se o estudande
     * pode ser mudado de turma ou ate mesmo disciplina
     *  **/

    public function updateStudent()
    {
        try {
            $update = [
                'full_name' => $this->studentName,
                'email' => $this->studentEmail,
            ];
            $this->student->update($update);
            $this->dispatch('student-event', type: 'update', message: 'Aluno atualizado com sucesso!');
            $this->open = false;
        } catch (\Exception $e) {
            $this->open = false;
            if (!empty($e->getMessage())) {
                $this->error('Erro interno no sistema', 'Error interno no sistema para essa funcionalidade por favor tente novamente mais tarde', position: 'toast-top toast-end', timeout: 8000);
            }
        }
    }

    #[On('student-edit-cancel')]
    public function cancel()
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

<x-mary-modal wire:model="open" title="Editar aluno" subtitle="Editar informações do aluno {{ $student->full_name }}"
    box-class="w-1/2  max-w-6xl">

    <form wire:submit.prevent="updateStudent" wire:loading.class="flex flex-col justify-center items-center">
        <div id="student-edit-actions-{{ $student->id }}">
            <div class="mb-3  gap-2" wire:target="updateStudent" wire:loading,remove>
                <div class="" id="student-input-full_name-{{ $student->id }}">
                    <x-mary-input label="Nome" wire:model="studentName" class="w-full !outline-none"
                        wire:keydown.enter="addStudent" />
                </div>
                <div class="" id="student-input-email-{{ $student->id }}">
                    <x-mary-input label="E-mail" class="text-gray-700" wire:model="studentEmail"
                        class="w-full !outline-none" wire:keydown.enter="addStudent" />
                </div>
            </div>

            <div>
                <div class="self-end mt-4" wire:loading.remove>
                    <button type="button" class="btn btn-sm btn-ghost tracking-wider capitalize"
                        x-on:click="$wire.cancel()" id="student-edit-cancel-{{ $student->id }}">cancelar</button>
                    <button type="submit"
                        class="btn btn-sm bg-blue-400 hover:bg-blue-500 capitalize text-gray-50 tracking-wider"
                        wire:click="updateStudent" id="student-edit-confirm-{{ $student->id }}">salvar</button>
                </div>
                <div wire:loading wire:target="updateStudent" class="w-full flex justify-center">
                    <div class="flex flex-col justify-center items-center gap-4">
                        <x-mary-loading class="loading-spinner loading-xl text-blue-400" />
                        <span class="text-xs capitalize font-semibold tracking-wider text-blue-400">salvando</span>
                    </div>
                </div>
            </div>


        </div>
    </form>
</x-mary-modal>
