<?php

use Livewire\Component;
use App\Models\Teacher\{Student};
use Livewire\Attributes\{On};

new class extends Component {
    public bool $open = false;
    public ?Student $student;

    #[On('teacher::remove-student')]
    public function open($id)
    {
        if ($this->student->id === $id || !$this->student->id === '') {
            $this->open = true;
        }
    }

    public function delete()
    {
        try {
            $this->student->delete();
            $this->open = false;
            $this->dispatch("student-event", type: "delete", message:"Aluno deletado com sucesso");
        } catch (\Exception $e) {
            $this->open = false;
            if (!empty($e->getMessage())) {
                $this->error('Erro interno no sistema', 'Error interno no sistema para essa funcionalidade por favor tente novamente mais tarde', position: 'toast-top toast-end', timeout: 8000);
            }
        }
    }

    #[On('student-delete-cancel')]
    public function cancel()
    {
        $this->open = false;
    }
};
?>
<x-mary-modal wire:model="open">
    <div id="student-delete-text-{{ $student->id ?? '' }}">
        <div class="mb-6 flex justify-center items-center gap-3 p-5" wire:loading.remove>
            <x-mary-icon name="o-trash" class="text-red-500 w-4 h-4" />
            <span class="text-red-500">Tem certeza que deseja deletar o aluno? {{ $student->full_name }}</span>
        </div>
        <div class="flex flex-col items-center text-center gap-3 p-5 w-full" wire:loading wire:target="delete">
            <div class="mb-4">
                <x-mary-loading class="progress-error w-7 h-7" />
            </div>
            <p class="text-[16px] text-blue-400 tracking-wide">Deletando</p>
        </div>

        <div class="w-full flex justify-end" wire:loading.remove>
            <button id="student-delete-cancel-{{ $student->id ?? '' }}" x-on:click="$wire.open = false"
                class=" btn btn-sm capitalize">
                Cancelar
            </button>
            <button type="button" class="btn btn-sm btn-error text-red-50" x-on:click="$wire.delete"
                wire:loading.remove id="student-confirm-delete-{{ $student->id ?? '' }}">
                Deletar
            </button>
        </div>
    </div>
</x-mary-modal>
