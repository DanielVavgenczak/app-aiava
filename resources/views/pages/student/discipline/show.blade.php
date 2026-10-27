<?php

use Livewire\Component;
use Livewire\Attributes\{Layout, On, Url};
use App\Models\Module;
use App\Support\ActivityProgress;

new #[Layout('layouts::student')] class extends Component {
    public $discipline_id;
    public $discipline = null; // ✅ Adiciona propriedade para discipline
    public $modules = [];
    public array $activityProgress = [];

    #[Url]
    public ?string $activity = null;

    public ?string $highlightModuleId = null;

    public function mount($discipline_id): void
    {
        $this->discipline_id = $discipline_id;
        $this->loadData();
    }

    // ✅ Método melhorado
    protected function loadData(): void
    {
        $student = auth()->guard('student')->user();

        abort_unless(
            $student && (string) $student->discipline_id === (string) $this->discipline_id,
            404
        );

        // ✅ Carrega módulos
        $this->modules = Module::with([
            'discipline:id,title', // ✅ Sintaxe mais limpa
            'lessons' => fn($q) => $q->orderBy('created_at', 'ASC'),
            'activitTasks.tasks' => fn($q) => $q->orderBy('created_at', 'ASC'),
        ])
        ->where('discipline_id', $this->discipline_id)
        ->get();

        $this->highlightModuleId = $this->activity
            ? $this->modules
                ->first(fn ($module) => $module->activitTasks->contains(
                    fn ($task) => (string) $task->id === (string) $this->activity
                ))
                ?->id
            : null;

        // ✅ OU carrega discipline diretamente (mais seguro)
        $this->discipline = \App\Models\Teacher\Discipline::select('id', 'title')
         ->find($this->discipline_id);

        // Situação de cada atividade para o aluno (acesso, resposta e prazo).
        $activities = $this->modules->flatMap(fn ($module) => $module->activitTasks);
        $progress = ActivityProgress::forStudent($student, $activities);

        $this->activityProgress = collect($progress)->map(fn (array $item) => [
            'status' => $item['status'],
            'label' => ActivityProgress::label($item['status'], $item['late']),
            'badge' => ActivityProgress::badgeClass($item['status'], $item['late']),
            'deadline' => $item['deadline']?->format('d/m/Y H:i'),
            'available_at' => $item['available_at']?->format('d/m/Y H:i'),
            'first_accessed_at' => $item['first_accessed_at']?->format('d/m/Y H:i'),
            'answered_at' => $item['answered_at']?->format('d/m/Y H:i'),
        ])->all();
    }

    #[On('student-activity-progress-updated')]
    public function refreshActivityProgress(): void
    {
        $this->loadData();
    }
}; ?>

<div>
    <livewire:global.breadcrumb :items="[
        ['label' => 'Disciplinas', 'route' => 'student.discipline', 'icon' => 'o-academic-cap'],
        ['label' => $discipline->title],
    ]" color="blue" tour-id="discipline-show-breadcrumb" />

    {{-- Conteúdo --}}
    <div class="p-5 bg-white rounded-lg shadow"
        x-data="{ openCollapseId: @js($highlightModuleId) }"
        @if ($activity)
            x-init="setTimeout(() => document.getElementById('open-activity-{{ $activity }}')?.scrollIntoView({ behavior: 'smooth', block: 'center' }), 250)"
        @endif
    >
        {{-- ✅ Verifica se discipline existe --}}
            <div class="py-4 border-b border-gray-200" id="discipline-show-header">
                <h2 class="text-gray-800 tracking-wide text-lg font-semibold">
                    {{ $discipline->title }}
                </h2>
                <p class="text-gray-600 tracking-wide font-medium mt-1">
                    Lista de aulas e blocos
                </p>
            </div>

        {{-- ✅ Lista de Módulos --}}
        @if($modules->count() > 0)
            <div class="flex gap-4 w-full mt-6">
                {{-- Sidebar com Módulos --}}
                <div class="w-1/4" id="discipline-show-modules-sidebar">
                    @foreach ($modules as $module)
                        <div
                            class="w-full bg-white border-[1px] border-gray-200 px-3 py-2 accordion lesson-card"
                            wire:key="module-{{ $module->id }}"
                            x-data="{ isExpanded: false }"
                            x-show="true"
                            id="accordion-{{ $module->id }}"
                            data-tour="{{ $module->id }}">
                            <!-- Click open -->
                            <div class="flex items-center py-2 px-2" id="accordion-module-{{ $module->id }}">
                                <div></div>
                                <div
                                id="content-lesson-add-new-block-{{ $module->id }}"
                                class="flex items-center justify-between w-full cursor-pointer open-collapse-btn">
                                <span class="text-[14px] font-normal tracking-wide text-gray-700 capitalize relative left-[2%]">
                                  {{ $module->title }}
                                </span>
                                <button class="bg-white cursor-pointer"
                                     id="open-accordion-block-{{ $module->id }}"
                                            x-on:click="openCollapseId === '{{ $module->id }}' ? openCollapseId = null : openCollapseId = '{{ $module->id }}'; isExpanded = !isExpanded">
                                            <x-mary-icon name="o-chevron-down" class="text-gray-500 w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                           <div
                            class="flex flex-col gap-3 p-4 bg-gray-100"
                            x-show="openCollapseId === '{{ $module->id }}'" x-collapse>
                                {{-- Lições --}}
                                @if ($module->lessons->count() > 0)
                                    <ul x-data="{ activeLesson: null }" class="lesson-student-list">
                                        @foreach ($module->lessons as $lesson)
                                            <li class="hover:bg-blue-400 ">
                                                <button
                                                    class="cursor-pointer w-full flex items-center gap-2 py-4 px-3 text-left"
                                                    x-on:click="
                                                        activeLesson = activeLesson === '{{ $lesson->id }}' ? null : '{{ $lesson->id }}';
                                                        $dispatch('stundent-lesson-streaming-block', { lesson_id: '{{ $lesson->id }}' });
                                                    "
                                                    :class="{
                                                        'bg-blue-200 text-blue-700 border-l-4 border-blue-700': activeLesson === '{{ $lesson->id }}',
                                                        'hover:bg-gray-100 text-gray-700': activeLesson !== '{{ $lesson->id }}'
                                                    }"
                                                >
                                                    <x-mary-icon name="o-play" class="w-4 h-4 text-blue-500" />
                                                    <span class="font-bold tracking-wider text-xs uppercase text-blue-500">{{ $lesson->title }}</span>
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-gray-600 tracking-wider text-sm py-2">
                                        Nenhuma atividade neste módulo
                                    </p>
                                @endif

                                {{-- Atividades --}}
                                @if ($module->activitTasks->count() > 0)
                                    <div class="mt-2 space-y-2 tasks-list-to-student bg-white" id="content-activitie-lesson-{{ $module->id }}">
                                        @foreach ($module->activitTasks as $activitTask)
                                                @php
                                                    $progress = $this->activityProgress[$activitTask->id] ?? null;
                                                    $isOpenForAnswers = $activitTask->isOpenForAnswers();
                                                    $startsLater = $activitTask->start && $activitTask->start->isFuture();
                                                    $isPastDeadline = $activitTask->end && $activitTask->end->isPast();
                                                @endphp
                                                <div id="activity-{{ $activitTask->id }}" class="flex flex-col gap-1 scroll-mt-28 rounded {{ (string) $activity === (string) $activitTask->id ? 'ring-2 ring-blue-400 bg-blue-50/50 p-1' : '' }}">
                                                    <div class="flex items-center gap-2">
                                                        <button
                                                            id="open-activity-{{ $activitTask->id }}"
                                                            data-tour-activity="{{ $activitTask->id }}"
                                                            x-on:click="$dispatch('student-event-open-task', { id: '{{ $activitTask->id }}' })"
                                                            @disabled(! $isOpenForAnswers)
                                                            @if ($isOpenForAnswers)
                                                                title="Abrir atividade"
                                                            @elseif ($startsLater)
                                                                title="Disponível a partir de {{ $activitTask->start->format('d/m/Y H:i') }}"
                                                            @else
                                                                title="Prazo encerrado em {{ $activitTask->end->format('d/m/Y H:i') }}"
                                                            @endif
                                                            class="tasks-open border-[1px] border-gray-300 flex-1 flex items-center gap-2 px-4 py-3 text-blue-600 font-semibold text-xs uppercase tracking-wider transition-colors rounded {{ $isOpenForAnswers ? 'hover:bg-blue-50' : 'cursor-not-allowed opacity-50' }}"
                                                        >
                                                            <x-mary-icon name="o-document-text" class="w-4 h-4" />
                                                            Atividade <span class="text-xs font-normal text-gray-500">({{ $activitTask->created_at->diffForHumans() }} )</span>
                                                        </button>
                                                        <button
                                                            id="open-activity-result-{{ $activitTask->id }}"
                                                            x-on:click="$dispatch('student-event-open-task-result', { id: '{{ $activitTask->id }}' })"
                                                            class="border-[1px] border-gray-300 px-3 py-3 hover:bg-green-50 text-green-600 font-semibold text-xs uppercase tracking-wider transition-colors rounded"
                                                        >
                                                            <x-mary-icon name="o-chart-bar" class="w-4 h-4" />
                                                            Resultado
                                                        </button>
                                                    </div>
                                                    @if ($progress)
                                                        <div class="flex flex-wrap items-center gap-2 pl-1" id="activity-status-{{ $activitTask->id }}">
                                                            @if ($startsLater)
                                                                <x-mary-badge value="Disponível em {{ $activitTask->start->format('d/m/Y H:i') }}" class="badge-sm badge-info" />
                                                            @elseif ($isPastDeadline)
                                                                <x-mary-badge value="Encerrada em {{ $activitTask->end->format('d/m/Y H:i') }}" class="badge-sm badge-ghost" />
                                                            @endif
                                                            <x-mary-badge value="{{ $progress['label'] }}" class="badge-sm {{ $progress['badge'] }}" />
                                                            @if ($progress['deadline'])
                                                                <span class="text-[11px] text-gray-500">Prazo: {{ $progress['deadline'] }}</span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Modal de Tarefas --}}
                        @if ($module->activitTasks->count() > 0)
                            <livewire:pages::student.discipline.lesson.show-task-lesson
                                :key="'student-task-' . $module->id"
                            />
                            <livewire:pages::student.discipline.lesson.task-result
                                :key="'student-task-result-' . $module->id"
                            />
                        @endif
                    @endforeach
                </div>

                {{-- Player de Vídeo --}}
                <div class="w-3/4" id="discipline-show-video-player">
                    <livewire:pages::student.discipline.lesson.show-video-lesson />
                </div>
            </div>
        @else
            {{-- ✅ Estado vazio --}}
            <div class="py-12 text-center">
                <x-mary-icon name="o-academic-cap" class="w-16 h-16 mx-auto text-gray-400 mb-4" />
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhum módulo disponível</h3>
                <p class="text-gray-600">
                    Esta disciplina ainda não possui módulos cadastrados.
                </p>
            </div>
        @endif
    </div>
    {{-- Tour Modal --}}
    <div class="fixed inset-0 z-40 bg-gray-900/50" x-data x-show="$store.tourguide.openMai">
        <div
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2
            -translate-y-1/2 w-full max-w-4xl sm:w-2/3 bg-white rounded-lg
            shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">
           <div class="flex justify-between items-center">
               <h2 class="text-sn font-bold text-gray-700">Opções da Disciplina</h2>
                <div class="flex justify-end">
                    <x-mary-button icon="o-x-mark"
                        class="bg-white border-none btn-square hover:none hover:bg-blue-400 hover:text-gray-100"
                        x-on:click="$store.tourguide.cancelOptionsTourPage()" />
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <x-mary-button
                    class="btn-md font-medium text-blue-400 border-[1px] border-blue-400 btn hover:bg-blue-400 hover:text-gray-100"
                    label="Visão geral da Disciplina"
                    x-on:click="$store.tourguide.start('StartTourStudentDisciplineShow')" />
                <x-mary-button
                    class="btn-md font-medium text-blue-400 border-[1px] border-blue-400 btn hover:bg-blue-400 hover:text-gray-100"
                    label="Acessar atividade"
                    x-on:click="$store.tourguide.start('StartTourStudentDisciplineShowActivitie')" />
                <x-mary-button
                    class="btn-md font-medium text-blue-400 border-[1px] border-blue-400 btn hover:bg-blue-400 hover:text-gray-100"
                    label="Responder atividade"
                    x-on:click="$store.tourguide.start('StartTourStudentAnswerActivity')" />
                <x-mary-button
                    class="btn-md font-medium text-blue-400 border-[1px] border-blue-400 btn hover:bg-blue-400 hover:text-gray-100"
                    label="Ver resultado da atividade"
                    x-on:click="$store.tourguide.start('StartTourStudentActivityResult')" />
            </div>
        </div>
        <img src="{{ asset('images/rb-aiava.png') }}" class="fixed right-0 w-64 bottom-4 sm:w-48" />
    </div>
</div>

    @script
    <script>
        // Atualiza highlight na listagem
        Livewire.on('update-student-lesson-highlight', (event) => {
            document.querySelectorAll('.lesson-student-list').forEach(element => {
                const alpine = Alpine.$data(element);
                if (alpine?.activeLesson !== undefined) {
                    alpine.activeLesson = event.lesson_id;
                }
            });
        });

        // Player de vídeo
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('video-load', event => {
                const video = document.getElementById('videoPlayer');
                if (video) {
                    video.src = event.url;
                }
            });
        });
    </script>
    @endscript

