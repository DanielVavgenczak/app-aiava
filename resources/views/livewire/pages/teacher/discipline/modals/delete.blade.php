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
            dd('Dei au', $ex->getMessage());
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
    <div class="mb-9" id="discipline-delete-text-{{ $id ?? '' }}">
        <h3 class="mb-5 text-lg font-medium text-gray-700">Deletar essa há disciplina: {{ $title }} </h3>
    </div>
    <div id="discipline-delete-actions-{{ $id ?? '' }}">
        <x-mary-button label="Cancel" id="discipline-delete-cancel-{{ $id ?? '' }}"
            @click="$wire.openModalModule = false" class="text-gray-500 btn-ghost" />
        <x-mary-button label="Sim" class="btn-error text-gray-50" @click="$wire.delete"
            id="discipline-delete-send-{{ $id ?? '' }}" />
        <div wire:loading wire:target="delete" class="flex items-center gap-2">
            <span class="text-sm text-pink-600 tracking-wide">Deletando</span>
            <x-mary-loading class=" text-pink-600 loading-dots" />
        </div>
    </div>
</x-mary-modal>
