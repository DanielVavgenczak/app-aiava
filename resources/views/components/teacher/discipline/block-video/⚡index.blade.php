<?php

use Livewire\Component;
use Livewire\Attributes\On;

new class extends Component {
    public string $module_id;
    public string $title;
    public bool $open = false;

    public $selectedTab = 'upload-video-tab';

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

    #[On('tour::close-opened-modals')]
    public function closeTourActived()
    {
        $this->open = false;
    }
}; ?>
<x-mary-modal wire:model="open" title="Adicione conteúdo à aula:" subtitle="Adicione videos á aula  {{ $title }}"
    box-class="w-full h-screen max-w-6xl" :key="'upload-content-' . $module_id">
    <div class="px-2 py-4" id="option-block-{{ $module_id }}">
        <x-mary-tabs wire:model="selectedTab" label-class="font-normal text-gray-600" label-div-class="options-{{ $module_id }}">
            <x-mary-tab name="upload-video-tab" id="upload-video-tab" label="Video" >
                <livewire:teacher.discipline.block-video.upload
                    :module_id="$module_id"
                    :key="'module-block-upload-' . $module_id" />
            </x-mary-tab>
            <x-mary-tab name="text-pdf-tab" id="text-pdf-tab" label="Texto">
                <livewire:teacher.discipline.block-video.upload-pdf
                :module_id="$module_id"
                :key="'module-pdf-upload-' . $module_id" />
            </x-mary-tab>
            <x-mary-tab name="link-lesson-tab" id="link-lesson-tab" label="Link">
                 <livewire:teacher.discipline.block-video.input-link
                    :module_id="$module_id"
                    :key="'module-link-' . $module_id" />
            </x-mary-tab>
        </x-mary-tabs>
    </div>
</x-mary-modal>
