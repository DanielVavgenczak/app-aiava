<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Teacher\{Discipline};
use Illuminate\Support\Facades\Session;

new class extends Component {
    public bool $open = false;
    public string $id = '';
    public string $title = '';

    #[On('delete-module-modal')]
    public function open($id)
    {
        if ($this->id === $id || $this->id === '') {
            $this->id = $id;
            $discipline = Discipline::find($id);
            $this->title = $discipline ? $discipline->title : '';
            $this->open = true;
        }
    }

    #[On('to-close')]
    public function toClose()
    {
        $this->open = false;
    }

    public function delete()
    {
        try {
            $discipline = Discipline::find($this->id);
            if (!$discipline) {
                Session::flash('error', 'Disciplina não encontrada');
            }
            $modules = $discipline->modules;
            foreach ($modules as $module) {
                $lessons = $module->lessons;
                foreach ($lessons as $lesson) {
                    if ($lesson->path_file && Storage::disk('private')->exists($lesson->path_file)) {
                        Storage::disk('private')->delete($lesson->path_file);
                    }
                    $lesson->delete();
                }
                $module->delete();
            }
            $discipline->delete();
            $this->dispatch('discipline-event', type: 'delete', msg: 'Disciplina excluída com sucesso!');
            $this->resetState();
            // sleep(3);
        } catch (Exception $ex) {
            $this->open = false; // Fecha o modal em caso de erro
            $this->title = ''; // Reseta o título
            $this->id = ''; // Reseta o ID
        }
    }
    private function resetState()
    {
        $this->open = false; // Fecha o modal
        $this->id = ''; // Reseta o ID
        $this->title = ''; // Reseta o título
    }
}; ?>

<x-mary-modal wire:model="open">
    <div class="flex flex-col items-center gap-2 p-5" id="discipline-delete-text-{{ $id ?? '' }}"
        wire:loading.remove>
        <div class="p-5 bg-blue-100 rounded-full">
            <x-mary-icon name="o-trash" class="w-6 h-6 text-blue-400" />
        </div>
        <p class="mb-3 text-sm font-medium tracking-wider text-blue-400">
            {{ $title }}
        </p>
    <div class="flex flex-col items-center w-full gap-2 p-5 text-center" wire:loading wire:target="delete">
        <div class="mb-2">
            <x-mary-loading class="progress-error w-7 h-7" />
        </div>
        <p class="text-xs tracking-wider text-blue-400">Deletando</p>
    </div>

    <div id="discipline-delete-actions-{{ $id ?? '' }}" class="flex justify-center w-full" wire:loading.remove>
        <button type="button" id="discipline-delete-cancel-{{ $id ?? '' }}" x-on:click="$wire.open = false"
            class="capitalize btn btn-sm btn-ghost">
            cancelar
        </button>
        <button class="capitalize bg-blue-400 btn btn-sm text-blue-50" x-on:click="$wire.delete" wire:loading.remove
            id="discipline-delete-send-{{ $id ?? '' }}">
            Deletar
        </button>
    </div>
</x-mary-modal>
