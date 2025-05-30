<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use App\Models\{Discipline};
use Illuminate\Support\Facades\Session;

new class extends Component {
    public bool $openModalModule = false;
    public string $id = '';
    public string $title = '';

    #[On('delete-module-modal')]
    public function open($id)
    {
        if ($this->id === $id || $this->id === '') {
            $this->id = $id;
            $discipline = Discipline::find($id);
            $this->title = $discipline ? $discipline->title : '';
            $this->openModalModule = true;
        }
    }

    #[On('to-close')]
    public function toClose()
    {
        $this->openModalModule = false;
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
            $this->dispatch('content-deleted', title: $this->title, action: 'delete');
            $this->resetState();
            sleep(3);
        } catch (Exception $ex) {
            dd('Error:', $ex->getMessage());
        }
    }
    private function resetState()
    {
        $this->openModalModule = false; // Fecha o modal
        $this->id = ''; // Reseta o ID
        $this->title = ''; // Reseta o título
    }
}; ?>

<x-mary-modal wire:model="openModalModule">
    <div class="mb-9 flex flex-col items-center gap-3 p-5" id="discipline-delete-text-{{ $id ?? '' }}"
        wire:loading.remove>
        <div class="p-5 rounded-full bg-red-100">
            <x-mary-icon name="o-trash" class="text-red-700 w-6 h-6" />
        </div>
        <p class="mb-3 tracking-wide text-[18px] font-medium text-red-700">
            {{ $title }}
        </p>
    </div>
    <div class=" flex flex-col items-center text-center gap-3 p-5 w-full" wire:loading wire:target="delete">
        <div class="mb-4">
            <x-mary-loading class="progress-error w-7 h-7" />
        </div>
        <p class="text-[16px] text-pink-600 tracking-wide">Deletando</p>
    </div>

    <div id="discipline-delete-actions-{{ $id ?? '' }}" class="w-full flex justify-end" wire:loading.remove>
        <button id="discipline-delete-cancel-{{ $id ?? '' }}" x-on:click="$wire.openModalModule = false"
            class="text-gray-500 btn capitalize">
            cancelar
        </button>
        <button class="btn bg-red-500 text-red-50 capitalize" x-on:click="$wire.delete" wire:loading.remove
            id="discipline-delete-send-{{ $id ?? '' }}">
            Deletar
        </button>
    </div>
</x-mary-modal>
