<?php

use Livewire\Component;
use App\Models\Teacher\{Classe, Student};
use Livewire\Attributes\{On, Validate};
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    public bool $open = false;

    // #[Validate('required', message: 'Nome do estudante é obrigatório!')]
    // #[Validate('min:5', message: 'O nome do estudante deve ter no minimo 5 caracteres')]
    // public string $studentName = '';
    // #[Validate('required', message: 'O e-mail do estudante é obrigatório!')]
    // #[Validate('email', message: 'O e-mail do estudante deve ser valido!')]
    // public string $studentEmail = '';
    // public string $studentPhone = '';

    public Student $student;

    #[On('teacher::view-student')]
    public function open($id)
    {
        if ($this->student->id === $id || !$this->student->id === '') {
            $this->open = true;
        }
    }

    public function mount(Student $student)
    {
        $this->fill($student);
    }

    // public function updateStudent()
    // {
    //     try {
    //         $update = [
    //             'full_name' => $this->studentName,
    //             'email' => $this->studentEmail,
    //         ];
    //         $this->student->update($update);
    //         $this->dispatch('student-event', type: 'update', message: 'Aluno atualizado com sucesso!');
    //         $this->open = false;
    //     } catch (\Exception $e) {
    //         $this->open = false;
    //         if (!empty($e->getMessage())) {
    //             $this->error('Erro interno no sistema', 'Error interno no sistema para essa funcionalidade por favor tente novamente mais tarde', position: 'toast-top toast-end', timeout: 8000);
    //         }
    //     }
    // }

    #[On('student-edit-cancel')]
    public function cancel()
    {
        $this->open = false;
    }

    // protected function validateMultiple($fields)
    // {
    //     $validate = [];
    //     foreach ($fields as $field) {
    //         $validateData = $this->validateOnly($field);
    //         $validate[key($validateData)] = current($validateData);
    //     }
    //     return $validate;
    // }
}; ?>

<x-mary-modal wire:model="open" title="Visão geral do aluno" subtitle="Informações do aluno {{ $student->full_name }}" box-class="w-1/2  max-w-6xl">

    {{ $student->full_name }}
</x-mary-modal>
