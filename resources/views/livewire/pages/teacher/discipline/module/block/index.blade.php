<?php

use Livewire\Volt\Component;
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

    #[On('upload-block-close')]
    public function close()
    {
        $this->open = false;
        $this->module_id = '';
        $this->title = '';
    }
}; ?>
<x-mary-modal wire:model="open" title="Adicione conteúdo à aula" subtitle="Escolha a ferramenta que desejar"
    box-class="w-full h-screen max-w-6xl verflow-y-visible" :key="'upload-content-' . $module_id">
    <div class="px-2 py-4">
        <x-mary-tabs wire:model="selectedTab" label-class="font-normal text-gray-600 ">
            <x-mary-tab name="upload-computed-tab" label="Upload pelo computador">
                <livewire:pages.teacher.discipline.module.block.upload :module_id="$module_id" :key="'module-block-upload-' . $module_id" />
            </x-mary-tab>
            <x-mary-tab name="record-to-plataform-tab" label="Gravar pela plataforma">
                <livewire:pages.teacher.discipline.module.block.record-in-plataform :module_id="$module_id" />
            </x-mary-tab>
            <x-mary-tab name="live-tab" label="Live">
                <livewire:pages.teacher.discipline.module.block.record-live :module_id="$module_id" />
            </x-mary-tab>
            <x-mary-tab name="cut-my-videos-tab" label="Recortar meu video" disabled>
                <div>Ferramenta de recorte</div>
            </x-mary-tab>
        </x-mary-tabs>
    </div>
</x-mary-modal>
