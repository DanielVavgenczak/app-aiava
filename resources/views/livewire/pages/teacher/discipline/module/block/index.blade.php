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

    public function close()
    {
        $this->open = false;
        $this->module_id = null;
        $this->title = null;
    }
}; ?>
<x-mary-modal wire:model="open" class="" box-class="w-11/12 h-screen max-w-6xl verflow-y-visible">
    <div class="px-2 py-4">
        <x-mary-tabs wire:model="selectedTab" label-class="font-normal text-gray-600 uppercase ">
            <x-mary-tab name="upload-computed-tab" label="Upload pelo computador">
                <livewire:pages.teacher.discipline.module.block.upload :module_id="$module_id" />
            </x-mary-tab>
            <x-mary-tab name="record-to-plataform-tab" label="Gravar pela plataforma">
                {{-- <div> <livewire:teacher.content.record-in-plataform></div> --}}
            </x-mary-tab>
            <x-mary-tab name="live-tab" label="Live">
                <div>Live</div>
            </x-mary-tab>
            <x-mary-tab name="cut-my-videos-tab" label="Recortar meu video" disabled>
                <div>Ferramenta de recorte</div>
            </x-mary-tab>
        </x-mary-tabs>
    </div>
    <div class="absolute bg-red-200 bottom-3">
        <button @click="$wire.close()" class="px-2 py-3 text-white capitalize bg-red-500 rounded-md">cancelar</button>
    </div>
</x-mary-modal>
