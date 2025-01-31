<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {
    public $module_id;
    public string $title;
    public bool $open = false;

    public $selectedTab = 'upload-computed-tab';

    #[On('open-new-block-to-module')]
    public function open()
    {
        $this->open = true;
    }

    public function close()
    {
        $this->open = false;
        $this->module_id = '';
        $this->title = '';
    }
}; ?>
<x-mary-modal wire:model="open" class="backdrop-blur " box-class="w-11/12 h-screen max-w-6xl verflow-y-visible">
    <div class="p-5 mb-5 ">
        <h3>Novo bloco para aula:<strong class="ml-3">{{ $title }}</strong></h3>
    </div>
    <div class="px-2 py-4">
        <x-mary-tabs wire:model="selectedTab" label-class="font-normal text-gray-600 uppercase ">
            <x-mary-tab name="upload-computed-tab" label="Upload pelo computador">
                <livewire:teacher.content.upload-block module_id="{{ $module_id }}" />
            </x-mary-tab>
            <x-mary-tab name="record-to-plataform-tab" label="Gravar pela plataforma">
                <div> <livewire:teacher.content.record-in-plataform></div>
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

{{-- <div x-data="{ modalNewBlock: false }">
        <button @click="modalNewBlock = true"
            class="flex items-center gap-2 px-3 py-2 mt-4 text-sm font-medium text-pink-500 transition rounded shadow-sm hover:bg-pink-400 hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300">
            <span class="text-xs tracking-wide uppercase">Bloco </span>
            <x-mary-icon name="o-plus" class="w-4 h-4 text-pink-500" /></button>

        <div x-cloak x-show="modalNewBlock" x-transition.opacity.duration.200ms x-trap.inert.noscroll="modalNewBlock"
            @keydown.esc.window="modalNewBlock = false" @click.self="modalNewBlock = false"
            class="fixed inset-0 z-30 flex items-end justify-center p-4 pb-8 bg-black/20 backdrop-blur-md sm:items-center lg:p-8"
            role="dialog" aria-modal="true" aria-labelledby="defaultModalTitle">
            <!-- Modal Dialog -->
            <div x-show="modalNewBlock"
                x-transition:enter="transition ease-out duration-200 delay-100 motion-reduce:transition-opacity"
                x-transition:enter-start="opacity-0 scale-50" x-transition:enter-end="opacity-100 scale-100"
                class="flex flex-col w-2/3 gap-4 overflow-hidden bg-white border rounded-md border-neutral-300 text-neutral-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
                <!-- Dialog Header -->
                <div
                    class="flex items-center justify-between p-4 border-b border-neutral-300 bg-neutral-50/60 dark:border-neutral-700 dark:bg-neutral-950/20">
                    <h3 id="defaultModalTitle" class="font-normal tracking-wide text-gray-700 dark:text-white">
                        Novo bloco</h3>
                    <button @click="modalNewBlock = false" aria-label="close modal">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"
                            stroke="currentColor" fill="none" stroke-width="1.4" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <!-- Dialog Body -->
                <div class="px-2 py-4">
                    <x-mary-tabs wire:model="selectedTab" label-class="text-xs font-normal text-gray-600 uppercase">
                        <x-mary-tab name="upload-computed-tab" label="Upload pelo computador">
                            <livewire:teacher.content.upload-block module_id="{{ $module->id }}" />
                        </x-mary-tab>
                        <x-mary-tab name="record-to-plataform-tab" label="Gravar pela plataforma">
                            <div> <livewire:teacher.content.record-in-plataform></div>
                        </x-mary-tab>
                        <x-mary-tab name="live-tab" label="Live">
                            <div>Live</div>
                        </x-mary-tab>
                        <x-mary-tab name="cut-my-videos-tab" label="Recortar meu video" disabled>
                            <div>Ferramenta de recorte</div>
                        </x-mary-tab>
                    </x-mary-tabs>
                </div>
            </div>
        </div>
    </div> --}}
