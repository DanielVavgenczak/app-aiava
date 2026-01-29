<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Module;
use App\Models\Lesson;

new #[Layout('layouts.student-panel')] class extends Component {
    public $discipline_id;
    public $modules = [];
    public $lessons = [];
    public function mount($discipline_id)
    {
        $this->getModule($discipline_id);
    }
    protected function getModule($id)
    {
        $this->modules = Module::with([
            'discipline' => function ($query) {
                $query->select('id', 'title');
            },
            'lessons' => function ($query) {
                $query->orderBy('created_at', 'ASC');
            },
            'activitTasks' => function ($query) {
                $query->with([
                    'tasks' => function ($query) {
                        $query->orderBy('created_at', 'ASC');
                    },
                ]);
                // $query->orderBy('created_at', 'ASC');
            },
        ])
            ->where('discipline_id', $id)
            ->get();
        return $this->modules;
    }
}; ?>

<div class="">
    <header class="py-5">
        <a href="{{ route('student.discipline') }}">
            <x-mary-icon name="o-arrow-left" class="w-4" />
            <span class="uppercase tracking-wider text-xs font-semibold">voltar</span>
        </a>
    </header>
    <div class="p-5 bg-white">
        <div class="py-4">
            <h2 class="text-gray-800 tracking-wide text-lg font-semibold">
                {{ $modules->first()->discipline->title }}
            </h2>
            <p class="text-gray-600 tracking-wide font-medium">Lista de aulas e blocos</p>
        </div>
        <div class="flex gap-2 w-full mt-3">
            <div class="w-1/4">
                @foreach ($modules as $module)
                    <x-mary-collapse separator>
                        <x-slot:heading>
                            <span class="text-gray-800 tracking-wider text-[15px] font-medium">
                                {{ $module->title }}</span>
                        </x-slot:heading>
                        @if (count($module->lessons) > 0)
                            <x-slot:content>
                                <ul x-data="{ activeLesson: null }" class="lesson-student-list">
                                    @foreach ($module->lessons as $lesson)
                                        <li class="">
                                            <button
                                                x-on:click="
                                                activeLesson = activeLesson === '{{ $lesson->id }}' ? null : '{{ $lesson->id }}'
                                                $dispatch('stundent-lesson-streaming-block',
                                                {lesson_id:'{{ $lesson->id }}'});
                                                "
                                                x-bind:class="{
                                                    'bg-blue-200 text-blue-700  border-l-4 border-blue-700': activeLesson === '{{ $lesson->id }}',
                                                    'hover:bg-gray-100 text-gray-700': activeLesson !== '{{ $lesson->id }}'
                                                }"
                                                class="transition-colors btn-active cursor-pointer w-full flex items-center gap-2 py-4 px-3 hover:bg-gray-100 text-gray-600 font-normal tracking-wider">
                                                <x-mary-icon name="o-play" class="w-4 h-4" />
                                                {{ $lesson->title }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </x-slot:content>
                        @else
                            <x-slot:content>
                                <span class="text-gray-600 tracking-wider text-sm">Nenhum bloco nessa aula!</span>
                            </x-slot:content>
                        @endif
                    </x-mary-collapse>
                    @if ($module->activitTasks->count() > 0)
                        @foreach ($module->activitTasks as $activitTask)
                            <div class="p-4 hover:bg-gray-100">
                                <button
                                    class="ml-4
                                    text-blue-400 font-semibold text-xs uppercase tracking-wider cursor-pointer"
                                    x-on:click="$dispatch('student-event-open-task', {id:'{{ $activitTask->id }}'})">
                                    <x-mary-icon name="o-document-text" class="w-4 h-4" />
                                    Atividade
                                </button>
                            </div>
                        @endforeach
                        <livewire:pages.student.discipline.lesson.show-task-lesson :key="'student-task' . $module->id" />
                    @endif
                @endforeach
            </div>
            <div class="w-3/4 h-full">
                <livewire:pages.student.discipline.lesson.show-video-lesson />
            </div>
        </div>
    </div>
    @script
    <script>

    // Atualiza o highlight na listagem do aluno
    Livewire.on('update-student-lesson-highlight', (event) => {
        // Atualiza todos os Alpine.js com activeLesson
        document.querySelectorAll('.lesson-student-list').forEach(element => {
            const alpine = Alpine.$data(element);
            if (alpine && alpine.activeLesson !== undefined) {
                alpine.activeLesson = event.lesson_id;
            }
        });
    });

    // Player de vídeo
    document.addEventListener('livewire:initialized', () => {
        const player = document.getElementById('videoPlayer');

        Livewire.on('video-load', event => {
            let video = document.getElementById("videoPlayer");
            if (video) {
                video.src = event.url;
            }
        });
    });
</script>
@endscript
