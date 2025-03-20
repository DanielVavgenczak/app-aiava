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

    public ?Discipline $discipline = null;
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
    // #[On('module-created')]
    // public function updateModuleItem()
    // {
    //     $this->discipline->load(['modules']);
    // }
}; ?>

<div>
    <!-- modal new module -->
    <livewire:teacher.content.new-module :disciplineId="$discipline->id" />
    <div x-data="{ openCollapseId: null }">
        <div class="py-6">
            <div class="space-y-2 max-w-7xl sm:px-6 lg:px-8">
                <div class="py-4">
                    <a href="{{ route('teacher.content') }}" wire:navigate>
                        <x-mary-icon name="o-arrow-left" class="w-4" />
                        <span class="text-xs font-semibold text-gray-600 uppercase">voltar</span>
                    </a>
                </div>
                <div class="h-screen p-4 bg-white sm:p-8 sm:rounded-0">
                    <div class="flex items-center justify-between mt-4 mb-10" x-data>
                        <h2 class="text-lg font-semibold text-gray-600">
                            {{ $discipline->title }}
                        </h2>
                        <x-mary-button class="text-sm bg-pink-600 text-gray-50 open-new-lesson"
                            id="content-lesson-create" label="Nova aula"
                            @click="$dispatch('open-modal-module',{open: true})" icon-right="o-plus" />
                    </div>
                    <div class="w-1/3 bg-gray-200 rounded-sm">
                        @forelse($discipline->modules as $module)

                            <div class="w-full bg-white border-[1px] border-gray-200 px-3 py-2 accordion"
                                mary:key="{{ $module->id }}" x-data="{ isExpanded: false }" x-show="true"
                                id="accordion-{{ $module->id }}">
                                <div class="flex items-center">
                                    <x-mary-dropdown class="">
                                        <x-slot:trigger>
                                            <x-mary-button icon="o-ellipsis-vertical"
                                                id="tourguide-content-lesson-options"
                                                class="bg-white border-none btn-set-dropdown" />
                                        </x-slot:trigger>
                                        <x-mary-menu-item title="Atividade / Avaliação" icon="o-arrow-path"
                                            id="content-lesson-createtask-btn" />
                                        <x-mary-menu-item title="Editar" id="content-lesson-edit-btn"
                                            icon="o-pencil-square" />
                                        <x-mary-menu-item id="content-lesson-delete-btn" class="btn-to-delete"
                                            title="Deletar" icon="o-trash"
                                            @click="$dispatch('delete-module-modal', { id: '{{ $module->id }}'})" />
                                    </x-mary-dropdown>
                                    <div id="content-lesson-add-new-block"
                                        class="flex items-center justify-between w-full cursor-pointer open-collapse-btn">
                                        <span
                                            class="text-[15px] font-normal text-gray-700 capitalize relative left-[20%]">{{ $module->title }}</span>
                                        <x-mary-button icon-right="o-chevron-down"
                                            class="bg-white open-new-block-lesson" id="tourguid-open-accordion-blocks"
                                            @click="openCollapseId === '{{ $module->id }}' ? openCollapseId = null : openCollapseId = '{{ $module->id }}'; isExpanded = !isExpanded" />
                                    </div>
                                </div>
                                <div class="flex flex-col gap-3 p-4 bg-gray-100"
                                    x-show="openCollapseId === '{{ $module->id }}'" x-collapse>
                                    <x-mary-button label="Bloco" icon-right="o-plus" id="block-open-new-block"
                                        class="text-sm text-white capitalize bg-pink-600 tour-open-new-block-to-click"
                                        @click="$dispatch('open-new-block-to-module',{open:true})" />
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
                                        <div class="px-2 py-3">
                                            <span class="text-sm font-medium text-gray-500 uppercase">nenhum
                                                bloco</span>
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                        @empty
                            <p class="absolute text-lg font-light text-center text-gray-600">Esse conteudo não
                                possue
                                nenhum modulo
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div x-data class="fixed inset-0 z-40 bg-black bg-opacity-50" x-show="$store.tourguide.openMai">
            <!-- Centralizando a div -->
            <div
                class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-full sm:w-1/2 bg-white rounded-lg shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">
                <x-mary-button icon="o-x-mark" class="btn-square" @click="$store.tourguide.closeTour()" />
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-normal text-gray-700">Aqui você pode adicionar novas aulas e novos blocos?
                    </h2>
                </div>
                <div class="grid grid-cols-4 gap-3 p-4 bg-gray-100">
                    <x-mary-button class="w-full border-gray-700 btn-outline sm:w-auto"
                        label="Vamos criar uma nova aula?" @click="$store.tourguide.startCreateLesson()" />
                    <x-mary-button class="w-full border-gray-700 btn-outline sm:w-auto" label="Adicionar um bloco?"
                        @click="$store.tourguide.startAddNewBlock()" />
                    <x-mary-button class="w-full border-gray-700 btn-outline sm:w-auto" label="Vamos deletar uma aula"
                        @click="$store.tourguide.startTourDeleteModule()" />
                    <x-mary-button class="w-full border-gray-700 btn-outline sm:w-auto" label="Vamos criar uma tarefa"
                        @click="$store.tourguide.startCreateTaskLesson()" />
                    <x-mary-button class="w-full border-gray-700 btn-outline sm:w-auto" label="Vamos editar uma aula"
                        @click="$store.tourguide.startEditLesson()" />

                </div>
            </div>
            <img src="{{ asset('images/ai-sos.png') }}" class="fixed right-0 w-64 bottom-4 sm:w-48">
        </div>
    </div>
