<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Teacher\Classe;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    public bool $openDelete = false;
    public ?Classe $classe;

    public function mount(Classe $classe)
    {
        $this->fill($classe);
    }

    #[On('delete-classe-modal')]
    public function open($id)
    {
        if ($this->classe->id === $id || !$this->classe->id === '') {
            $this->openDelete = true;
        }
    }
    public function delete()
    {
        try {
            $students = $this->classe->students()->with('profile')->get();

            foreach ($students as $student) {
                if ($student->profile?->avatar && Storage::disk('public')->exists($student->profile->avatar)) {
                    Storage::disk('public')->delete($student->profile->avatar);
                }
            }

            $this->classe->delete();
            $this->openDelete = false;
            $this->classe = null;
            $this->dispatch('action-event-classe', type: 'delete');
        } catch (\Exception $e) {
            $this->openDelete = false;
            if (!empty($e->getMessage())) {
                $this->error('Erro interno no sistema', 'Error interno no sistema para essa funcionalidade por favor tente novamente mais tarde', position: 'toast-top toast-end', timeout: 8000);
            }
        }
    }

    #[On('classe-delete-cancel')]
    public function close()
    {
        $this->openDelete = false;
    }
}; ?>

<x-mary-modal wire:model="openDelete">

    @if ($classe)
    <div id="classe-delete-content">

    <div class="mb-6 flex justify-center items-center gap-3 p-5""
            wire:loading.remove>
            <x-mary-icon name="o-trash" class="text-red-500 w-4 h-4" />
            <span class="text-red-400">Tem certeza que deseja deletar? {{ $classe->name }}</span>
        </div>
        <div class="flex flex-col items-center text-center gap-3 p-5 w-full" wire:loading wire:target="delete">
            <div class="mb-4">
                <x-mary-loading class="progress-error w-7 h-7" />
            </div>
            <p class="text-[16px] text-pink-600 tracking-wide">Deletando</p>
        </div>

        <div id="classe-delete-actions-{{ $classe->id ?? '' }}" class="w-full flex justify-end" wire:loading.remove>
            <button id="classe-delete-cancel-{{ $classe->id ?? '' }}" x-on:click="$wire.openDelete = false"
                class=" btn btn-sm capitalize">
                Cancelar
            </button>
            <button class="btn btn-sm btn-error text-red-50" x-on:click="$wire.delete" wire:loading.remove
                id="classe-delete-confirm-{{ $classe->id ?? '' }}">
                Deletar
            </button>
        </div>

    </div>
    @endif
</x-mary-modal>
