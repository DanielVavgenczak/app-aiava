<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, On, Url};
use App\Models\{Discipline};
use Mary\Traits\Toast;

new #[Layout('layouts.app')] class extends Component {
    use Toast;
    public bool $openFormNewLesson = false;
    public $discipline;
    public $blockSelected = null;
    public $allLessons = [];
    public string $search_lesson = '';
    public bool $showTaskModule = false;
    public string $disciplineID = '';

    #[Url]
    public ?string $highlight = null;
    public function mount(string $id)
    {
        if (session('delete-with-files')) {
            $this->error(session('delete-with-files'), icon: 'o-trash', css: 'alert-error');
            session()->forget('delete-with-files');
        }
        $this->disciplineID = $id;
        $this->discipline = $this->loadDisciplineContent($this->disciplineID);
        $this->allLessons = $this->discipline->modules->flatMap->lessons->values()->toArray();
    }
    #[On('select-lesson')]
    public function selectedBlock($block_id)
    {
        $this->blockSelected = $this->discipline->modules->flatMap->lessons->firstWhere('id', $block_id);
        $this->dispatch('load-video-lesson', $block_id)->to('pages.teacher.discipline.module.block.video-content');
    }

    public function refreshUploadVideo()
    {
        $this->toast(type: 'success', title: 'Bloco adicionado com sucesso', icon: 'o-check', css: 'alert-success');
        $this->js("console.log('upload event exec')");
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
    public function openModalNewLesson()
    {
        $this->dispatch('open-new-block-to-module', true);
    }

    /** Open modal new task */
    public function openModalTask()
    {
        $this->dispatch('open-create-task');
        $this->openFormNewLesson = true;
    }

    #[On('close-create-task')]
    public function seeModalTask()
    {
        $this->openFormNewLesson = false;
        $this->js("console.log('talves tenhamos que pegar o valor da variavel')");
    }
    #[On('task-created-success')]
    public function taskAddSuccess($message)
    {
        $this->success($message);
    }

    #[On('event-delete-block')]
    public function refreshDeleteBlock()
    {
        $this->discipline = $this->loadDisciplineContent($this->disciplineID);
        $this->allLessons = $this->discipline->modules->flatMap->lessons->values()->toArray();
    }

    #[On('module-event')]
    public function update($type, $msg)
    {
        match ($type) {
            'update', 'create', 'uploaded' => $this->handleEventWithToast($type, $msg),
            'delete' => $this->handleEventWithToast($type, $msg),
            default => null,
        };
        $this->discipline = $this->loadDisciplineContent($this->disciplineID);
        $this->allLessons = $this->discipline->modules->flatMap->lessons->values()->toArray();
    }

    protected function handleEventWithToast(string $type, string $msg)
    {
        if ($type !== 'delete') {
            $this->toast(type: 'success', title: "{$msg}", icon: 'o-check', css: 'alert-success');
            return;
        }
        $this->toast(type: 'error', title: "{$msg}", icon: 'o-trash', css: 'alert-error');
        return;
    }
    protected function loadDisciplineContent(string $id): Discipline
    {
        return Discipline::with([
            'modules' => function ($q) {
                $q->orderBy('created_at', 'ASC'); // Ordem crescente
            },
            'modules.lessons' => function ($q) {
                $q->orderBy('created_at', 'ASC'); // Ordem crescente
            },
            'modules.activitTasks' => function ($q) {
                $q->orderBy('created_at', 'ASC'); // Ordem crescente
            },
        ])
            ->where('id', $id)
            ->first();
    }

    // #[On('task-deleted')]
    // public function refreshShowContent()
    // {
    //     $this->success('Atividade excluida com sucesso!', icon: 'o-check', css: 'bg-pink-500 text-base-100');
    //     return $this->redirect(route('teacher.discipline.show', $this->discipline), navigate: true);
    // }
}; ?>
<div>
  
    {{-- TODO: criar um componente para receber o link de voltar  --}}
    <div class="py-4">
        <a href="{{ route('teacher.discipline') }}" wire:navigate>
            <x-mary-icon name="o-arrow-left" class="w-4 text-gray-500" />
            <span class="text-xs font-semibold tracking-wider text-gray-500 uppercase">voltar</span>
        </a>
    </div>    
    <!-- modal new module -->
    <div x-data="{ openCollapseId: null }" class="p-5 bg-white" data-tour="{{ $discipline->id }}">
        <div class="h-screen">
            <div class="flex items-center justify-between mt-4 mb-10" x-data>
                <div class="flex flex-col gap-4">
                    <div>
                        <h2 class="text-lg font-semibold tracking-wider text-gray-800">
                            {{ $discipline->title }}
                        </h2>
                        <p class="text-sm text-gray-500">Conteudo da disciplina</p>
                    </div>
                    @if ($discipline->modules->count() > 0)
                        <div id="content-input-search">
                          <input class="input !outline-none" placeholder="Pesquisar aula" wire:model.live="search_lesson" />
                        </div>
                    @endif
                </div>
                <div>
                    @if ($discipline->modules->count() > 0)
                        <button class="bg-pink-600 btn btn-sm text-gray-50 open-new-task " id="content-task-create"
                            wire:click="openModalTask">
                            <x-mary-icon name="o-document-duplicate" class="w-5 h-5" /> Atividade / Avaliação
                        </button>
                    @endif
                    <button class="bg-pink-600  btn btn-sm text-gray-50 open-new-lesson" id="content-lesson-create"
                        x-on:click="$dispatch('open-module-create',{open: true})"
                        data-tour="new-lesson-{{ $discipline->id }}">
                        <x-mary-icon name="o-plus" />
                        Aula
                    </button>
                </div>
            </div>
            <div class="flex gap-5 relative h-[calc(100vh-180px)]">
                <div class="w-1/2 h-auto overflow-y-auto" wire:key="module-list-container" id="module-list-all">
                    @if ($discipline->modules->count() > 0)
                        @foreach ($discipline->modules as $module)
                            <div class="w-full bg-white border-[1px] border-gray-100 px-3 py-2 accordion module-card"
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
                                            x-on:click="$dispatch('open-edit-module', { id: '{{ $module->id }}' })"
                                            icon="o-pencil-square" />
                                        <x-mary-menu-item id="content-lesson-delete-btn" class="btn-to-delete"
                                            title="Deletar" icon="o-trash"
                                            x-on:click="$dispatch('open-delete-module', {
                                            id: '{{ $module->id }}' })" />
                                    </x-mary-dropdown>
                                    <div id="content-lesson-add-new-block-{{ $module->id }}"
                                        class="flex items-center justify-between w-full cursor-pointer open-collapse-btn">
                                        <span
                                            class="text-[14px] font-normal tracking-wide text-gray-700 capitalize relative left-[2%]">{{ $module->title }}</span>
                                        <button class="bg-white cursor-pointer open-new-block-lesson"
                                            id="open-accordion-block-{{ $module->id }}"
                                            x-on:click="openCollapseId === '{{ $module->id }}' ? openCollapseId = null : openCollapseId = '{{ $module->id }}'; isExpanded = !isExpanded">
                                            <x-mary-icon name="o-chevron-down" />
                                        </button>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-3 p-4 bg-gray-100"
                                    x-show="openCollapseId === '{{ $module->id }}'" x-collapse>
                                    <button id="block-open-new-block-{{ $module->id }}"
                                        class="btn text-white tracking-wider uppercase text-[12px] bg-pink-600 tour-open-new-block-to-click cursor-pointer"
                                        x-on:click="$dispatch('open-new-block-to-module', { open: true })">
                                        <x-mary-icon name="o-plus" /> bloco
                                    </button>
                                    <livewire:pages.teacher.discipline.module.block.index :module_id="$module->id"
                                        :title="$module->title" :key="'block-index-' . $module->id" />

                                    @forelse ($module->lessons as $lesson)
                                        <div class="flex flex-col gap-2 px-2 py-3 cursor-pointer hover:bg-gray-300"
                                            :key="'lesson-'. $lesson->id "
                                            x-on:click="$dispatch('select-video-block', { lesson_id: '{{ $lesson->id }}' })">
                                            <div class="flex flex-col gap-3">
                                                <div class="flex items-center gap-3">
                                                    <x-mary-icon name="o-play" class="w-4 h-4 mr-2 text-pink-400" />
                                                    <span
                                                        class="text-sm font-semibold tracking-wide text-gray-600">{{ $lesson->title }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="px-2 py-3" wire:key="no-lessons-{{ $module->id }}">
                                            <span
                                                class="text-[12px] tracking-wide font-medium text-gray-600 uppercase">nenhum
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
                                            <div class="p-2 rounded-sm">
                                                <x-mary-icon name="o-document-duplicate"
                                                    class="w-4 h-4 text-pink-600" />
                                            </div>
                                            <span
                                                class="text-xs font-light tracking-wide text-pink-600 uppercase">atividade
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                                <!-- drawer task module -->
                            @endif
                            <livewire:pages.teacher.discipline.module.modals.edit :$module :key="'module-edit-' . $module->id" />
                            <livewire:pages.teacher.discipline.module.modals.delete :id="$module->id"
                                :key="'module-delete-' . $module->id" />
                        @endforeach
                    @else
                        <div wire:key="no-modules" class="absolute w-full h-screen bg-white">
                            <p class="text-sm font-medium text-center text-gray-700 tracking-wider">Essa disciplina não
                                possui nenhuma aula!</p>
                        </div>
                    @endif
                </div>
                <div class="w-full h-screen">
                    <livewire:pages.teacher.discipline.module.block.video-content :key="'video-block-' . Str::random(10)" />
                </div>
            </div>
        </div>
      
          
        <!-- Modal options tour -->
        <div x-data class="fixed inset-0 z-50 bg-gray-900/50" x-show="$store.tourguide.openMai">
            <div
                class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-full sm:w-1/2 bg-white rounded-lg shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">
                <div class="flex items-center justify-between">
                  <h2 class="text-[14px] tracking-wide font-medium text-gray-700">
                    Aqui você pode adicionar novas aulas e blocos
                    </h2>
                    <button 
                      class="btn btn-sm btn-ghost"
                      x-on:click="$store.tourguide.cancelOptionsTourPage()">
                      <x-mary-icon name="o-x-mark" class="w-4 text-gray-700"/>
                    </button>                  
                </div>
                <!-- options show !-->
                @if (!$openFormNewLesson)
                    <div class="grid grid-cols-4 gap-3 p-4 bg-gray-100">
                        <x-mary-button
                            class="font- text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                            label="Criar nova aula"
                            x-on:click="$store.tourguide.start('StartTourDisciplineCreateContent')" />
                        @if ($discipline->modules->count() > 0)
                            <x-mary-button
                                class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                                label="Adicionar um bloco"
                                x-on:click="$store.tourguide.start('StartTourDisciplineCreateContentUploadBlock')" />
                            <x-mary-button
                                class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                                label="Vamos deletar uma aula"
                                x-on:click="$store.tourguide.startTourDeleteModule()" />
                            <x-mary-button
                                class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                                label="Vamos criar uma tarefa"
                                x-on:click="$store.tourguide.startCreateTaskLesson()" />
                            <x-mary-button
                                class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                                label="Vamos editar uma aula" 
                                x-on:click="$store.tourguide.startEditLesson()" />
                        @endif
                    </div>
                @else
                   <!-- options create task -->
                    <x-mary-button
                        class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                        label="Vamos criar uma atividade"
                        x-on:click="$store.tourguide.start('StartTourCreateActivitTask')" />
                @endif
            </div>
            <img src="{{ asset('images/ai-sos.png') }}" class="fixed right-0 w-64 bottom-4 sm:w-48">
        </div>

        <livewire:pages.teacher.discipline.module.task.show-task 
          wire:key="'task-module-' .{{ $discipline->id ?? ''}}" />
        <livewire:pages.teacher.discipline.module.modals.create 
          :disciplineId="$discipline->id" 
          wire:key="'create-module-' . {{ $discipline->id ?? ''}}" />
            <livewire:pages.teacher.discipline.module.modals.create-task 
          :lessons="$discipline->modules" 
          :disciplineId="$discipline->id" 
          wire:key="'new-task-' . {{ $discipline->id ?? ''}}" /> 
    </div>
