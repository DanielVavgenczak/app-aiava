<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use App\Models\{Discipline, Lesson, Module};
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Livewire\Attributes\Validate;
use Mary\Traits\Toast;
use Livewire\WithFileUploads;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Pion\Laravel\ChunkUpload\Handler\ContentRangeUploadHandler;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Illuminate\Database\Eloquent\Collection;

new #[Layout('layouts.app')] class extends Component {
    use Toast, WithFileUploads;

    public Discipline $discipline;
    public string $id;
    public bool $showCollapse = false;
    public $video;
    public $progress = 0;
    public $file;
    public $chunckSize = 1000000;
    public $fileChunck;
    public string $fileName = '';
    public int $fileSize = 0;
    public $finalFile;
    public ?Lesson $lesson = null;
    public string $idclicked = '';
    public string $group = 'group1';
    public array $groups = ['group1', 'group2', 'group3'];
    public function mount(string $id)
    {
        $this->discipline = Discipline::with(['modules'])
            ->where('id', $id)
            ->first();
    }

    #[On('module-created')]
    public function updateModuleItem()
    {
        $this->discipline->load(['modules']);
    }
}; ?>

<div>
    <!-- modal new module -->
    <livewire:teacher.content.new-module disciplineId="{{ $discipline->id }}" />
    <div x-data="{ openCollapseId: null }">
        <div class="py-6">
            <div class="space-y-2 max-w-7xl sm:px-6 lg:px-8">
                <div class="py-4">
                    <a href="{{ route('teacher.content') }}" wire:navigate>
                        <x-mary-icon name="o-arrow-left" class="w-4" />
                        <span class="text-xs font-semibold text-gray-600 uppercase">voltar</span>
                    </a>
                </div>
                <div class="p-4 bg-white sm:p-8 sm:rounded-0">
                    <div class="flex items-center justify-between mt-4 mb-10">
                        <h2 class="text-lg font-semibold text-gray-700">
                            {{ $discipline->title }}
                        </h2>
                        <x-mary-button class="text-sm bg-pink-600 text-gray-50" label="Nova aula"
                            wire:click="$dispatch('open-modal-module')" icon-right="o-plus" />
                    </div>
                    <div class="w-1/3 bg-gray-400 rounded-sm">
                        @forelse($discipline->modules as $module)
                            <div class="w-full bg-white border-[1px] border-gray-200 px-3 py-2"
                                :key="{{ $module->id }}" x-data="{ isExpanded: false }" x-show="true"
                                id="accordion-{{ $module->id }}">
                                <div class="flex items-center">
                                    <x-mary-dropdown class="">
                                        <x-slot:trigger>
                                            <x-mary-button icon="o-ellipsis-vertical" class="bg-white border-none" />
                                        </x-slot:trigger>
                                        <x-mary-menu-item title="Atividade / Avaliação" icon="o-arrow-path" />
                                        <x-mary-menu-item title="Editar" icon="o-arrow-path" />
                                        <x-mary-menu-item title="Deletar" @click="$wire.sureDeleteModal = true"
                                            icon="o-trash" />
                                    </x-mary-dropdown>
                                    <div class="flex items-center justify-between w-full cursor-pointer"
                                        @click="openCollapseId === '{{ $module->id }}' ? openCollapseId = null : openCollapseId = '{{ $module->id }}'; isExpanded = !isExpanded">
                                        <span
                                            class="text-[15px] font-normal text-gray-700 capitalize relative left-[20%]">{{ $module->title }}</span>
                                        <x-mary-icon name="o-chevron-down" class="bg-white" />
                                    </div>
                                </div>
                                <div class="flex flex-col gap-3 p-4 bg-gray-100"
                                    x-show="openCollapseId === '{{ $module->id }}'" x-collapse>
                                    <x-mary-button label="Bloco" icon-right="o-plus"
                                        class="text-sm text-white capitalize bg-pink-600"
                                        @click="$wire.dispatch('open-new-block-to-module')" />
                                    <!-- add bloco-->
                                    <livewire:teacher.content.modal-new-block :module_id="$module->id" :title="$module->title" />
                                    @forelse ($module->lessons as $lesson)
                                        <div class="flex flex-col gap-2 px-2 py-3 hover:bg-gray-300">
                                            <div class="flex flex-col gap-3 ">
                                                <span
                                                    class="text-sm font-normal text-gray-700">{{ $lesson->title }}</span>
                                                <div class="flex">
                                                    <x-mary-icon name="o-tv" class="w-4 h-4 mr-2 text-gray-400" />
                                                    <span
                                                        class="text-xs font-medium text-gray-400 uppercase">10min</span>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <span>nenhum bloco</span>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <p>nenhum modulo</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- <div class="flex gap-4 justfy-center">
                        <div class="flex w-full bg-gray-200">
                            <div class="w-1/3">
                                @foreach ($discipline->modules as $module)
                                    <livewire:teacher.content.module-item :module="$module" :key="$module->id">
                                @endforeach
                            </div>
                            <div class="w-full">
                                <livewire:teacher.content.video-content />
                            </div>
                        </div>
                    </div> --}}
