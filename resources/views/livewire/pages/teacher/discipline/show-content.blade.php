<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, On, Url};
use App\Models\{Discipline, Lesson, Module};
use Mary\Traits\Toast;

new #[Layout('layouts.app')] class extends Component {
    use Toast;
    public bool $openFormNewLesson = false;
    public Discipline $discipline;
    public $blockSelected = null;
    public $allLessons = [];
    public string $search = '';
    public bool $showTaskModule = false;

    #[Url]
    public ?string $highlight = null;

    public function mount($discipline)
    {
        // Garanta que $discipline é um objeto Discipline
        // Ordena módulos e lições por data de criação (mais novo primeiro)
        if (session('module_created')) {
            $this->success('Nova aula registrada com sucesso!');
        }
        $this->discipline = $discipline->load([
            'modules' => function ($q) {
                $q->orderBy('created_at', 'ASC'); // Ordem crescente (mais antigo primeiro)
            },
            'modules.lessons' => function ($q) {
                $q->orderBy('created_at', 'ASC'); // Ordem crescente (mais antigo primeiro)
            },
            'modules.activitTasks' => function ($q) {
                $q->orderBy('created_at', 'ASC'); // Ordem crescente (mais antigo primeiro)
            },
        ]);

        $this->allLessons = $this->discipline->modules
            ->flatMap(fn($module) => $module->lessons)
            ->values() // Reseta os índices do array
            ->all();
    }
    #[On('select-lesson')]
    public function selectedBlock($block_id)
    {
        $this->blockSelected = $this->discipline->modules->flatMap->lessons->firstWhere('id', $block_id);
        $this->dispatch('load-video-lesson', $block_id)->to('pages.teacher.discipline.module.block.video-content');
    }

    #[On('uploaded-video')]
    public function handleVideoUploaded()
    {
        $this->success('Novo bloco adicionado com sucesso!');
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
    /**
     * Quinto andar
     * 41 998282562
     * 41
     *
     *  **/
    public function openModalNewLesson()
    {
        $this->dispatch('open-new-block-to-module', true);
    }

    public function openModalTask()
    {
        $this->openFormNewLesson = true;
        $this->dispatch('open-create-task');
    }

    #[On('close-create-task')]
    public function seeModalTask()
    {
        $this->openFormNewLesson = false;
    }
    #[On('task-created-success')]
    public function taskAddSuccess($message)
    {
        $this->success($message);
    }

    #[On('task-deleted')]
    public function refreshShowContent()
    {
        $this->js("console.log('E para carregar os componentes')");
        $this->success('Atividade excluida com sucesso!', icon: 'o-check', css: 'bg-pink-500 text-base-100');
        return $this->redirect(route('teacher.discipline.show', $this->discipline), navigate: true);
    }
}; ?>
<div>
    {{-- TODO: criar um componente para receber o link de voltar  --}}
    <div class="py-4">
        <a href="{{ route('teacher.discipline') }}" wire:navigate>
            <x-mary-icon name="o-arrow-left" class="w-4" />
            <span class="text-xs font-semibold text-gray-600 uppercase">voltar</span>
        </a>
    </div>
    <livewire:pages.teacher.discipline.module.new :disciplineId="$discipline->id" />
    <livewire:pages.teacher.discipline.module.task.modal-create :lessons="$discipline->modules" :disciplineId="$discipline->id" />
    <!-- modal new module -->
    <div x-data="{ openCollapseId: null }" class="p-5 bg-white">
        <div class="h-screen">
            <div class="flex items-center justify-between mt-4 mb-10" x-data>
                <div class="flex flex-col gap-4">
                    <h2 class="text-lg font-semibold text-gray-800 tracking-wider">
                        {{ $discipline->title }}
                    </h2>
                    <input wire:model="search" placeholder="Pesquisar aula" icon="o-magnifying-glass"
                        id="content-input-search"
                        class="rounded-lg px-3 py-2 focus:outline-1 focus:border-pink-500  focus:outline-pink-500 border-[1px] border-gray-400 " />

                </div>
                <div>
                    @if ($discipline->modules->count() > 0)
                        <x-mary-button class="bg-pink-600 text-gray-50 open-new-lesson rounded-lg"
                            id="content-lesson-create" label="Atividade / Avaliação" icon-right="o-document-duplicate"
                            wire:click="openModalTask" />
                    @endif
                    <x-mary-button class="text-sm bg-pink-600 text-gray-50 open-new-lesson rounded-lg"
                        id="content-lesson-create" label="Nova aula"
                        x-on:click="$dispatch('open-modal-module',{open: true})" icon-right="o-plus" />

                </div>
            </div>
            <div class="flex gap-5 relative h-[calc(100vh-180px)]">
                <div class="w-1/2 h-auto overflow-y-auto" wire:key="module-list-container" id="module-list-all">
                    @if ($discipline->modules->count() > 0)
                        @foreach ($discipline->modules as $module)
                            {{--  modals --}}
                            <div class="w-full bg-white border-[1px] border-gray-200 px-3 py-2 accordion module-card"
                                wire:key="module-{{ $module->id }}" x-data="{ isExpanded: false }" x-show="true"
                                id="accordion-{{ $module->id }}" data-tour="{{ $module->id }}">
                                <div class="flex items-center" id="accordion-module-{{ $module->id }}">
                                    <x-mary-dropdown class="" wire:key="module-options-{{ $module->id }}">
                                        <x-slot:trigger>
                                            <x-mary-button icon="o-ellipsis-vertical"
                                                id="tourguide-content-lesson-options"
                                                class="bg-white border-none btn-set-dropdown" />
                                        </x-slot:trigger>
                                        <x-mary-menu-item title="Editar" id="content-lesson-edit-btn"
                                            icon="o-pencil-square" />
                                        <x-mary-menu-item id="content-lesson-delete-btn" class="btn-to-delete"
                                            title="Deletar" icon="o-trash"
                                            x-on:click="$dispatch('delete-module-modal', { id: '{{ $module->id }}'})" />
                                    </x-mary-dropdown>
                                    <div id="content-lesson-add-new-block-{{ $module->id }}"
                                        class="flex items-center justify-between w-full cursor-pointer open-collapse-btn">
                                        <span
                                            class="text-[15px] font-normal text-gray-700 capitalize relative left-[20%]">{{ $module->title }}</span>
                                        <x-mary-button icon-right="o-chevron-down"
                                            class="bg-white open-new-block-lesson"
                                            id="open-accordion-block-{{ $module->id }}"
                                            x-on:click="openCollapseId === '{{ $module->id }}' ? openCollapseId = null : openCollapseId = '{{ $module->id }}'; isExpanded = !isExpanded" />
                                    </div>
                                </div>
                                <div class="flex flex-col gap-3 p-4 bg-gray-100"
                                    x-show="openCollapseId === '{{ $module->id }}'" x-collapse>
                                    <x-mary-button label="Bloco" icon-right="o-plus"
                                        id="block-open-new-block-{{ $module->id }}"
                                        class="text-sm text-white capitalize bg-pink-600 tour-open-new-block-to-click"
                                        x-on:click="$dispatch('open-create-task', { open: true })" responsive />
                                    <livewire:pages.teacher.discipline.module.block.index :module_id="$module->id"
                                        :title="$module->title" wire:key="block-index-{{ $module->id }}" />
                                    @forelse ($module->lessons as $lesson)
                                        <div class="flex flex-col gap-2 px-2 py-3 cursor-pointer hover:bg-gray-300"
                                            wire:key="lesson-{{ $lesson->id }}"
                                            x-on:click="$dispatch('select-lesson', { block_id: '{{ $lesson->id }}' })">
                                            <div class="flex flex-col gap-3">
                                                <div class="flex items-center gap-3">
                                                    <x-mary-icon name="o-play" class="w-4 h-4 mr-2 text-pink-400" />
                                                    <span
                                                        class="text-sm font-semibold text-gray-700">{{ $lesson->title }}</span>
                                                </div>
                                                <div class="flex">
                                                    <x-mary-icon name="o-clock" class="w-4 h-4 mr-2 text-gray-400" />
                                                    <span
                                                        class="text-xs font-medium text-gray-400 uppercase">10min</span>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="px-2 py-3" wire:key="no-lessons-{{ $module->id }}">
                                            <span class="text-sm font-medium text-gray-500 uppercase">nenhum
                                                bloco</span>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                            @if ($module->activitTasks->count() > 0)
                                @foreach ($module->activitTasks as $activit)
                                    <div class="flex gap-3 px-10 py-2 cursor-pointer bg-pink-50 hover:bg-gray-300"
                                        x-on:click="$dispatch('task-module', {activit_id:'{{ $activit->id }}'})">
                                        <div class="flex items-center gap-3 ">
                                            <div class="p-2  rounded-sm">
                                                <x-mary-icon name="o-document-duplicate"
                                                    class="w-4 h-4 text-pink-600" />
                                            </div>
                                            <span
                                                class="text-xs tracking-wide font-light uppercase text-pink-600">atividade
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                                <!-- drawer task module -->
                            @endif
                        @endforeach
                    @else
                        <div wire:key="no-modules" class="absolute w-full h-screen bg-white">
                            <p class="text-lg font-light text-center text-gray-600">Esse conteúdo não possui nenhum
                                módulo</p>
                        </div>
                    @endif
                </div>
                <div class="w-full h-screen">
                    @if ($blockSelected && $blockSelected->path_file)
                        <livewire:pages.teacher.discipline.module.block.video-content :lesson="$blockSelected"
                            wire:key="video-block-{{ $blockSelected->id }}" />
                    @else
                        <div class="flex flex-col items-center justify-center h-full gap-4 bg-gray-100 rounded-lg">
                            <x-mary-icon name="o-video-camera" class="w-16 h-16 text-gray-400"
                                wire:key="no-video-placeholder" />
                            <p class="text-sm text-gray-500">Selecione um bloco</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <!-- drawer task module -->
        <livewire:pages.teacher.discipline.module.task.show-task />

        <!-- Modal options tour -->
        <div x-data class="fixed inset-0 z-40 bg-gray-900/50" x-show="$store.tourguide.openMai">
            <div
                class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-full sm:w-1/2 bg-white rounded-lg shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">
                <x-mary-button icon="o-x-mark" class="btn-square"
                    x-on:click="$store.tourguide.cancelOptionsTourPage()" />
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-normal text-gray-700">Aqui você pode adicionar novas aulas e
                        blocos?{{ $openFormNewLesson }}
                    </h2>
                </div>
                <!-- options show !-->
                @if (!$openFormNewLesson)
                    <div class="grid grid-cols-4 gap-3 p-4 bg-gray-100">

                        <x-mary-button
                            class="font-light text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                            label="Criar nova aula?"
                            x:on:click="$store.tourguide.start('StartTourDisciplineCreateContent')" />
                        @if ($discipline->modules->count() > 0)
                            <x-mary-button
                                class="font-light text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                                label="Adicionar um bloco?"
                                x-on:click="$store.tourguide.start('StartTourDisciplineCreateContentUploadBlock')" />
                            <x-mary-button
                                class="font-light text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                                label="Vamos deletar uma aula"
                                x-on:click="$store.tourguide.startTourDeleteModule()" />
                            <x-mary-button
                                class="font-light text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                                label="Vamos criar uma tarefa"
                                x-on:click="$store.tourguide.startCreateTaskLesson()" />
                            <x-mary-button
                                class="font-light text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                                label="Vamos editar uma aula" x-on:click="$store.tourguide.startEditLesson()" />
                        @endif
                    </div>
                @else
                    <x-mary-button
                        class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                        label="Vamos criar uma atividade"
                        x-on:click="$store.tourguide.start('StartTourCreateActivitTask')" />
                @endif

            </div>
            <img src="{{ asset('images/ai-sos.png') }}" class="fixed right-0 w-64 bottom-4 sm:w-48">
        </div>
    </div>
