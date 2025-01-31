<?php

use Livewire\Volt\Component;
use App\Models\Module;

new class extends Component {
    public Module $module;
    public bool $modalVideoBlock = false;
    public bool $sureDeleteModal = false;
    public $selectedTab = 'upload-computed-tab';
    public $newBlock = false;
    public $monitorLesson = '';

    public function mount(Module $module)
    {
        $this->fill($module);
    }
    public function showVideoLesson(string $lesson_id)
    {
        $this->dispatch('load-video-lesson', lesson_id: $lesson_id);
    }
}; ?>
<div class="">
    <div>
        {{-- @forelse ($module->lessons as $lesson)
            <div class="flex flex-col gap-4">
                <span wire:key="{{ $lesson->id }}" wire:click="showVideoLesson('{{ $lesson->id }}')"
                    class="px-2 py-3 text-sm font-normal text-gray-500 uppercase cursor-pointer">{{ $lesson->title }}</span>
            </div>
        @empty
            <p class="font-light text-gray-500 uppercase text-sx">nenhum bloco</p>
        @endforelse --}}
    </div>
    {{-- <x-mary-modal wire:model="sureDeleteModal" class="backdrop-blur">
        <div class="flex flex-col items-center w-full gap-6 mb-5 emjustify-center">
            <h3 class="text-lg text-red-700">Tem certeza que deseja deletar modulo?</h3>
            <p class="text-sm text-gray-600">{{ $module->title }}</p>
        </div>
        <x-mary-button label="Cancel" @click="$wire.openNewBlock()" />
        <x-mary-button label="Sim" class="btn-error" />
    </x-mary-modal> --}}
    <!-- accordeon -->
    {{-- <div x-data="{ isExpanded: false }" class="bg-gray-200">
        <div class="flex items-center justify-between w-full bg-white">
            <x-mary-dropdown>
                <x-slot:trigger>
                    <x-mary-button icon="o-ellipsis-vertical" class="bg-white" />
                </x-slot:trigger>
                <x-mary-menu-item title="Atividade / Avaliação" icon="o-arrow-path" />
                <x-mary-menu-item title="Editar" icon="o-arrow-path" />
                <x-mary-menu-item title="Deletar" @click="$wire.sureDeleteModal = true" icon="o-trash" />
            </x-mary-dropdown>
            <div>
                <span class="left-0 text-base font-light text-gray-600"> {{ $module->title }}</span>
            </div>
            <button id="controlsAccordionItemOne-{{ $module->id }}" type="button"
                class="flex items-center justify-between gap-4 p-4 text-left bg-neutral-50 underline-offset-2 hover:bg-neutral-50/75 focus-visible:bg-neutral-50/75 focus-visible:underline focus-visible:outline-none dark:bg-neutral-900 dark:hover:bg-neutral-900/75 dark:focus-visible:bg-neutral-900/75"
                aria-controls="accordionItemOne-{{ $module->id }}" @click="isExpanded = ! isExpanded"
                :class="isExpanded ? 'text-onSurfaceStrong dark:text-onSurfaceDarkStrong font-bold' :
                    'text-onSurface dark:text-onSurfaceDark font-medium'"
                :aria-expanded="isExpanded ? 'true' : 'false'">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="2"
                    stroke="currentColor" class="w-4 h-4 text-gray-600 transition size-5 shrink-0" aria-hidden="true"
                    :class="isExpanded ? 'rotate-180' : ''">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
            </button>
        </div>
        <div x-cloak x-show="isExpanded" id="accordionItemOne-{{ $module->id }}" role="region"
            class="relative z-40 px-3 py-5 bg-gray-200" aria-labelledby="controlsAccordionItemOne" x-collapse>
            <!-- add bloco-->
            <livewire:teacher.content.modal-new-block :module_id="$module->id" :title="$module->title" />
            <x-mary-button icon-right="o-plus" class="text-xs uppercase bg-pink-500 text-gray-50" label="novo bloco"
                type="button" wire:click.prevent="$dispatch('open-new-block-to-module')" />

            <div>
                @forelse ($module->lessons as $lesson)
                    <div class="flex flex-col gap-4">
                        <span wire:key="{{ $lesson->id }}" wire:click="showVideoLesson('{{ $lesson->id }}')"
                            class="px-2 py-3 text-sm font-normal text-gray-500 uppercase cursor-pointer">{{ $lesson->title }}</span>
                    </div>
                @empty
                    <p class="font-light text-gray-500 uppercase text-sx">nenhum bloco</p>
                @endforelse
            </div>
        </div> --}}
</div>
</div>
