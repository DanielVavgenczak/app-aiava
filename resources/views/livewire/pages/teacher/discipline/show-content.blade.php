<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, On};
use App\Models\{Discipline, Lesson};
use Mary\Traits\Toast;

new #[Layout('layouts.app')] class extends Component {
    use Toast;
    public Discipline $discipline;
    public $blockSelected = null;
    public $allLessons = [];

    public function mount($discipline)
    {
        $this->discipline = $discipline->load('modules.lessons');
        $this->allLessons = $this->discipline->modules->flatMap->lessons->all();
    }
    #[On('select-lesson')]
    public function selectedBlock($block_id)
    {
        $this->blockSelected = $this->discipline->modules->flatMap->lessons->firstWhere('id', $block_id);
        $this->dispatch('load-video-lesson', $block_id)->to('pages.teacher.discipline.module.block.video-content');
    }

    #[On('new-module-created')]
    public function updateDisciplineList()
    {
        $this->success('Nova aula registrada com sucesso!');
    }

    #[On('next-video-content')]
    public function next($id)
    {
        $currentLesson = $this->discipline->modules->flatMap->lessons->firstWhere('id', $id);

        $currentIndex = array_search($currentLesson, $this->allLessons);
        $nextIndex = $currentIndex + 1;
        if ($nextIndex < count($this->allLessons)) {
            $this->blockSelected = $this->allLessons[$nextIndex];
            $this->dispatch('load-video-lesson', $this->blockSelected->id);
        }
        // Debug para verificar se o evento está funcionando
        $this->js("console.log('Next video" . $id . "')");
    }

    #[On('previous-video-content')]
    public function previous($id)
    {
        $currentLesson = $this->discipline->modules->flatMap->lessons->firstWhere('id', $id);
        $currentIndex = array_search($currentLesson, $this->allLessons);
        $previousIndex = $currentIndex - 1;

        if ($previousIndex >= 0) {
            $this->blockSelected = $this->allLessons[$previousIndex];
            $this->dispatch('load-video-lesson', $this->blockSelected->id);
        }

        // Debug para verificar se o evento está funcionando
        $this->js("console.log('Previous video: " . $previousIndex . "')");
    }

    public function nextLesson()
    {
        if (!$this->blockSelected) {
            $this->blockSelected = $this->allLessons[0] ?? null;
        } else {
            $currentIndex = array_search($this->blockSelected, $this->allLessons);
            $nextIndex = $currentIndex + 1;
            if ($nextIndex < count($this->allLessons)) {
                $this->blockSelected = $this->allLessons[$nextIndex];
                $this->dispatch('load-video-lesson', $this->blockSelected->id);
            }
        }
    }

    public function previousLesson()
    {
        if (!$this->blockSelected) {
            $this->blockSelected = $this->allLessons[0] ?? null;
        } else {
            $currentIndex = array_search($this->blockSelected, $this->allLessons);
            $previousIndex = $currentIndex - 1;
            if ($previousIndex >= 0) {
                $this->blockSelected = $this->allLessons[$previousIndex];
                $this->dispatch('load-video-lesson', $this->blockSelected->id);
            }
        }
    }
}; ?>

<div>
    <!-- modal new module -->
    <livewire:pages.teacher.discipline.module.new :disciplineId="$discipline->id" />
    <div x-data="{ openCollapseId: null }">
        <div class="py-6">
            <div class="space-y-2 max-w-7xl sm:px-6 lg:px-8">
                <div class="py-4">
                    <a href="{{ route('teacher.discipline') }}" wire:navigate>
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
                    <div class="flex gap-5">
                        <div class="w-1/3 h-auto bg-red-100 rounded-sm">
                            @if ($discipline->modules->count() > 0)
                                @foreach ($discipline->modules as $module)
                                    <div class="w-full bg-white border-[1px] border-gray-200 px-3 py-2 accordion"
                                        mary:key="module-{{ $module->id }}" x-data="{ isExpanded: false }" x-show="true"
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
                                                    class="bg-white open-new-block-lesson"
                                                    id="tourguid-open-accordion-blocks"
                                                    @click="openCollapseId === '{{ $module->id }}' ? openCollapseId = null : openCollapseId = '{{ $module->id }}'; isExpanded = !isExpanded" />
                                            </div>
                                        </div>
                                        <div class="flex flex-col gap-3 p-4 bg-gray-100"
                                            x-show="openCollapseId === '{{ $module->id }}'" x-collapse>
                                            <x-mary-button label="Bloco" icon-right="o-plus" id="block-open-new-block"
                                                class="text-sm text-white capitalize bg-pink-600 tour-open-new-block-to-click"
                                                @click="$dispatch('open-new-block-to-module',{open:true})" />
                                            <!-- add bloco-->
                                            <livewire:pages.teacher.discipline.module.block.index :module_id="$module->id"
                                                :title="$module->title" />
                                            @forelse ($module->lessons as $lesson)
                                                <div class="flex flex-col gap-2 px-2 py-3 cursor-pointer hover:bg-gray-300"
                                                    wire:key="lesson-{{ $lesson->id }}"
                                                    @click="$dispatch('select-lesson', { block_id: '{{ $lesson->id }}' })">
                                                    <div class="flex flex-col gap-3 ">
                                                        <span
                                                            class="text-sm font-normal text-gray-700">{{ $lesson->title }}</span>
                                                        <div class="flex">
                                                            <x-mary-icon name="o-tv"
                                                                class="w-4 h-4 mr-2 text-gray-400" />
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
                                @endforeach
                            @else
                                <p class="absolute text-lg font-light text-center text-gray-600">Esse conteudo não
                                    possue
                                    nenhum modulo
                                </p>
                            @endif

                        </div>
                        <div class="w-full h-screen">
                            @if ($blockSelected && $blockSelected->path_file)
                                <livewire:pages.teacher.discipline.module.block.video-content :lesson="$blockSelected"
                                    wire:key="video-{{ $blockSelected->id }}" />
                            @else
                                <div
                                    class="flex flex-col items-center justify-center h-64 gap-4 bg-gray-100 rounded-lg">
                                    <x-mary-icon name="o-video-camera" class="w-16 h-16 text-gray-400" />
                                    <p class="text-sm text-gray-500">Selecione um bloco</p>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div x-data class="fixed inset-0 z-40 bg-black bg-opacity-50" x-show="$store.tourguide.openMai">
            <!-- Centralizando a div -->
            <div
                class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-full sm:w-1/2 bg-white rounded-lg shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">
                <x-mary-button icon="o-x-mark" class="btn-square" @click="$store.tourguide.cancelOptionsTourPage()" />
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-normal text-gray-700">Aqui você pode adicionar novas aulas e novos blocos?
                    </h2>
                </div>
                <div class="grid grid-cols-4 gap-3 p-4 bg-gray-100">
                    <x-mary-button
                        class="font-light text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                        label="Vamos criar uma nova aula?" @click="$store.tourguide.startCreateLesson()" />
                    <x-mary-button
                        class="font-light text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                        label="Adicionar um bloco?" @click="$store.tourguide.startAddNewBlock()" />
                    <x-mary-button
                        class="font-light text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                        label="Vamos deletar uma aula" @click="$store.tourguide.startTourDeleteModule()" />
                    <x-mary-button
                        class="font-light text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                        label="Vamos criar uma tarefa" @click="$store.tourguide.startCreateTaskLesson()" />
                    <x-mary-button
                        class="font-light text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                        label="Vamos editar uma aula" @click="$store.tourguide.startEditLesson()" />

                </div>
            </div>
            <img src="{{ asset('images/ai-sos.png') }}" class="fixed right-0 w-64 bottom-4 sm:w-48">
        </div>
    </div>
