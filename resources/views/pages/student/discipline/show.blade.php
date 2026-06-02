<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Module;

new #[Layout('layouts::student')] class extends Component {
    public $discipline_id;
    public $discipline = null; // ✅ Adiciona propriedade para discipline
    public $modules = [];

    public function mount($discipline_id): void
    {
        $this->discipline_id = $discipline_id;
        $this->loadData();
    }

    // ✅ Método melhorado
    protected function loadData(): void
    {
        // ✅ Carrega módulos
        $this->modules = Module::with([
            'discipline:id,title', // ✅ Sintaxe mais limpa
            'lessons' => fn($q) => $q->orderBy('created_at', 'ASC'),
            'activitTasks.tasks' => fn($q) => $q->orderBy('created_at', 'ASC'),
        ])
        ->where('discipline_id', $this->discipline_id)
        ->get();

        // ✅ Pega discipline do primeiro módulo (se existir)
       // $this->discipline = $this->modules->first()?->discipline;

        // ✅ OU carrega discipline diretamente (mais seguro)
        $this->discipline = \App\Models\Teacher\Discipline::select('id', 'title')
         ->find($this->discipline_id);
    }
}; ?>

<div>
    <livewire:global.breadcrumb :items="[
        ['label' => 'Disciplinas', 'route' => 'student.discipline', 'icon' => 'o-academic-cap'],
        ['label' => $discipline->title],
    ]" color="pink" tour-id="discipline-show-breadcrumb" />

    {{-- Conteúdo --}}
    <div class="p-5 bg-white rounded-lg shadow">
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
                        <div id="module-{{ $module->id }}">
                        <x-mary-collapse separator>
                            <x-slot:heading>
                                <span class="text-gray-800 tracking-wider text-[15px] font-medium">
                                    {{ $module->title }}
                                </span>
                            </x-slot:heading>

                            <x-slot:content>
                                {{-- Lições --}}
                                @if ($module->lessons->count() > 0)
                                    <ul x-data="{ activeLesson: null }" class="lesson-student-list">
                                        @foreach ($module->lessons as $lesson)
                                            <li>
                                                <button
                                                    class="transition-colors w-full flex items-center gap-2 py-4 px-3 text-left"
                                                    @click="
                                                        activeLesson = activeLesson === '{{ $lesson->id }}' ? null : '{{ $lesson->id }}';
                                                        $dispatch('stundent-lesson-streaming-block', { lesson_id: '{{ $lesson->id }}' });
                                                    "
                                                    :class="{
                                                        'bg-blue-200 text-blue-700 border-l-4 border-blue-700': activeLesson === '{{ $lesson->id }}',
                                                        'hover:bg-gray-100 text-gray-700': activeLesson !== '{{ $lesson->id }}'
                                                    }"
                                                >
                                                    <x-mary-icon name="o-play" class="w-4 h-4" />
                                                    <span class="font-normal tracking-wider">{{ $lesson->title }}</span>
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-gray-600 tracking-wider text-sm py-2">
                                        Nenhuma lição neste módulo
                                    </p>
                                @endif

                                {{-- Atividades --}}
                                @if ($module->activitTasks->count() > 0)
                                    <div class="mt-2 space-y-2">
                                        @foreach ($module->activitTasks as $activitTask)
                                            <button
                                                @click="$dispatch('student-event-open-task', { id: '{{ $activitTask->id }}' })"
                                                class="w-full flex items-center gap-2 px-4 py-3 hover:bg-blue-50 text-blue-600 font-semibold text-sm tracking-wider transition-colors rounded"
                                            >
                                                <x-mary-icon name="o-document-text" class="w-4 h-4" />
                                                Atividade
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </x-slot:content>
                        </x-mary-collapse>
                        </div>

                        {{-- Modal de Tarefas --}}
                        @if ($module->activitTasks->count() > 0)
                            <livewire:pages::student.discipline.lesson.show-task-lesson
                                :key="'student-task-' . $module->id"
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

