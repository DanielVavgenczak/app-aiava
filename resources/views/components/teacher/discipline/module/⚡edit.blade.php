<?php

use Livewire\Component;
use Livewire\Attributes\{On, Validate};
use App\Models\Module;

new class extends Component {

    public bool $open = false;
    public $module = null;
    #[Validate('required', message: 'O título do módulo é obrigatório!')]
    public string $title;

    public function mount(Module $module)
    {
        $this->fill($module);
        $this->title = $module->title;
    }
    #[On('teacher::open-edit-module')]
    public function open($id)
    {
        if ($this->module->id === $id) {
            $this->open = true;
        }
    }

    public function update()
    {
        $this->validate();
        try {
            $this->module->update([
                'title' => $this->title,
            ]);
            $this->open = false;
            $this->dispatch('teacher::module-event', type: 'update', msg: 'Aula edita com sucesso!');
            $this->reset('title');
        } catch (\Exception $e) {
            if (!empty($e->getMessage())) {
                $this->toast(type: 'error', title: $e->getMessage(), icon: 'o-alert-triangle', css: 'alert-error');
            }
            return;
        }
    }

    #[On('tour::clear-edit-lesson-modal')]
    public function closeWithTour()
    {
        $this->open = false;
        //$this->reset('title');
    }
    public function cancelEdit()
    {
        $this->open = false;
        //$this->reset('title');
    }
}; ?>

<x-mary-modal wire:model="open" title="Editar {{ $module->title ?? 'aula' }}"
    subtitle="Edite o módulo: {{ $module->title ?? 'aula' }}">
    <form wire:submit.prevent="update" id="edit-module-{{ $module->id ?? '' }}">
        <div wire:loading.remove class="my-3" id="module-edit-title-{{ $module->id ?? '' }}">
            <x-mary-input for="title" :value="__('Titulo')" wire:model="title" class="!outline-none" />
        </div>
        <div class="flex justify-end my-3" id="module-edit-form-actions-{{ $module->id ?? '' }}" wire:loading.remove>
            <div class="flex items-center gap-2">
                <button type="button" class="cursor-pointer btn btn-sm btn-ghost" wire:click="cancelEdit"
                    id="module-edit-cancel-{{ $module->id }}">
                    Cancelar
                </button>
                <button class="bg-blue-400 btn btn-sm text-gray-50" type="submit"
                    id="module-edit-send-{{ $module->id }}">
                    Editar
                </button>
            </div>
        </div>
        <div wire:loading class="flex flex-col justify-center w-full text-center">
            <div class="mb-2">
                <x-mary-loading class="text-blue-600 loading-dots" />
            </div>
            <span class="text-xs text-blue-600">Editando</span>
        </div>
    </form>
</x-mary-modal>
