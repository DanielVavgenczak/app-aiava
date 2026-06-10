<?php

use Livewire\Component;
use Livewire\Attributes\On;

new class extends Component {
    public string $module_id;
    public string $title;
    public bool $open = false;

    public $selectedTab = 'upload-computed-tab';

    public function mount(string $module_id, string $title)
    {
        $this->module_id = $module_id;
        $this->title = $title;
    }
    #[On('open-new-block-to-module')]
    public function open($open)
    {
        $this->open = $open;
    }

    #[On('tour-close-modal-upload_block')]
    #[On('upload-block-close')]
    public function close()
    {
        $this->open = false;
        $this->title = '';
    }
}; ?>
<x-mary-modal wire:model="open" title="Adicione conteúdo à aula:" subtitle="Adicione videos á aula  {{ $title }}"
    box-class="w-full h-screen max-w-6xl verflow-y-visible" :key="'upload-content-' . $module_id">
    <div class="px-2 py-4">
        <x-mary-tabs wire:model="selectedTab" label-class="font-normal text-gray-600 ">
            <x-mary-tab name="upload-computed-tab" label="Upload pelo computador">
                <livewire:teacher.discipline.block-video.upload :module_id="$module_id" :key="'module-block-upload-' . $module_id" />
            </x-mary-tab>
        </x-mary-tabs>
    </div>
</x-mary-modal>
