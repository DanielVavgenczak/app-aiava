<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use App\Models\Teacher\Student;

new class extends Component {
    public bool $open = false;
    public string $id;
    public ?Student $student;

    #[On('show-student-details')]
    public function open($id)
    {
        if ($this->student->id === $id) {
            $this->open = true;
        }
    }
}; ?>
<x-mary-modal wire:model="open" title="Detalhes do aluno">
    <div class="flex flex-col gap-2">
        <h3> {{ $student->full_name }}</h3>
        <div class="flex flex-col gap-2">
            <span> {{ $student->email }}</span>
            <span>
                {{ $student->phone ? $student->phone : 'Sem telefone' }}
            </span>
        </div>
    </div>
</x-mary-modal>
