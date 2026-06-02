<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Teacher\Student;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Teacher;
use Illuminate\Support\Facades\Auth;

new #[Layout(name: 'layouts::student')] class extends Component {
    public $teacher_id;
    public $disciplineClasseInfo;
    public $authenticated;
    public $forumTopicsCount = 0;

    public function mount()
    {
        $this->authenticatedUser();
        $this->getDisciplineClasseForum();
    }

    protected function getDisciplineClasseForum()
    {
        if ($this->authenticated['type'] === 'teacher') {
            $teacher = $this->authenticated['logged'];
            $disciplines = Discipline::where('teacher_id', $teacher->id)
                ->withCount('ForumTopics')
                ->get();
            $this->disciplineClasseInfo = (object) [
                'discipline' => $disciplines->first(),
                'classe' => (object) ['name' => ''],
                'teacher' => $teacher,
            ];
            return $this->disciplineClasseInfo;
        }

        $this->disciplineClasseInfo = Student::with(['classe', 'discipline' => fn($query) => $query->withCount('ForumTopics')])
        ->findOrFail($this->authenticated['logged']->id);
        return $this->disciplineClasseInfo;
    }

    private function authenticatedUser()
    {
        if (Auth::guard('teacher')->check()) {
            $this->authenticated['logged'] = Auth::guard('teacher')->user();
            $this->authenticated['type'] = 'teacher';
            return $this->authenticated;
        }

        $this->authenticated['logged'] = Auth::guard('student')->user();
        $this->authenticated['type'] = 'student';
        return $this->authenticated;
    }
}; ?>
<div>
    <livewire:global.breadcrumb :items="[
        ['label' => 'Disciplinas', 'route' => 'student.discipline', 'icon' => 'o-academic-cap'],
        ['label' => 'Fórum'],
    ]" color="pink" tour-id="forum-breadcrumb" />

    <div class="p-5  bg-white flex flex-col gap-4">
        <div class="px-6 py-5" id="forum-header">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div
                        class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                        <x-mary-icon name="o-chat-bubble-left-right" class="w-6 h-6 text-gray-900" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Postagens no Fórum</h2>
                        <p class="text-gray-600 text-sm">Acompanhe as discussões da sua turma</p>
                    </div>
                </div>
            </div>
        </div>
        <div id="forum-discipline-grid">
            @if($authenticated['type'] === 'teacher')
                @php
                    $disciplines = \App\Models\Teacher\Discipline::where('teacher_id', $authenticated['logged']->id)
                        ->withCount('ForumTopics')
                        ->get();
                @endphp
                @forelse($disciplines as $discipline)
                <div id="forum-discipline-card" class="w-1/4 relative bg-gradient-to-br from-gray-50 to-white border-2 border-gray-200 rounded-xl p-4 hover:shadow-xl hover:border-blue-400 hover:-translate-y-1 transition-all duration-300"
                    wire:key="forum-discipline-{{ $discipline->id }}">
                    <div class="flex justify-center mb-3">
                        <div class="w-14 h-14 bg-gradient-to-br from-blue-700 to-blue-500 rounded-xl flex items-center justify-center shadow-sm">
                            <x-mary-icon name="o-academic-cap" class="w-7 h-7 text-white" />
                        </div>
                    </div>
                    <h3 class="text-center text-sm font-bold text-gray-900 line-clamp-2 min-h-[2.5rem] flex flex-col">
                        {{ $discipline->title }}
                    </h3>
                    <div class="flex flex-col items-center justify-center gap-1 text-xs text-gray-600 mb-3">
                        <div class="flex items-center gap-1">
                            <x-mary-icon name="o-chat-bubble-left-ellipsis" class="w-3 h-3" />
                            <span>{{ $discipline->forum_topics_count }} {{ Str::plural('tópico', $discipline->forum_topics_count) }}</span>
                        </div>
                        <a id="forum-access-btn" href="{{ route('student.forum.show', $discipline->id) }}" wire:navigate
                            class="btn btn-block btn-sm bg-gradient-to-r from-blue-700 to-blue-500 text-white border-0 hover:from-blue-700 hover:to-blue-500 transition-all">
                            <x-mary-icon name="o-arrow-right-circle" class="w-4 h-4" />
                            Acessar
                        </a>
                    </div>
                </div>
                @empty
                <p class="text-gray-500 text-center py-8">Nenhuma disciplina encontrada.</p>
                @endforelse
            @else
            <div id="forum-discipline-card" class="w-1/4 relative bg-gradient-to-br from-gray-50 to-white border-2 border-gray-200 rounded-xl p-4 hover:shadow-xl hover:border-blue-400 hover:-translate-y-1 transition-all duration-300"
                wire:key="forum-discipline-{{ $disciplineClasseInfo->id }}">
                {{-- Ícone --}}
                <div class="flex justify-center mb-3">
                    <div
                        class="w-14 h-14 bg-gradient-to-br from-blue-700 to-blue-500 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300">
                        <x-mary-icon name="o-academic-cap" class="w-7 h-7 text-white" />
                    </div>
                </div>
                <h3
                    class="text-center text-sm font-bold text-gray-900  line-clamp-2 min-h-[2.5rem] group-hover:text-blue-700 transition-colors flex flex-col">
                    {{ $disciplineClasseInfo->discipline->title }}
                </h3>
                {{-- Info de Tópicos --}}
                <div class="flex flex-col items-center justify-center gap-1 text-xs text-gray-600 mb-3">
                    <div class="flex gap-1 items-center">
                            @if ($disciplineClasseInfo->discipline->teacher->avatar??null)
                                <img src="{{ asset('storage/' . $disciplineClasseInfo->discipline->teacher->avatar) }}"
                                    alt="Avatar do Professor" class="w-4 h-4 rounded-full object-cover">
                            @else
                             <x-mary-icon name="o-user-circle" class="w-4 h-4" />
                            @endif
                            <span>
                            <b>Professor:</b>
                            {{ $disciplineClasseInfo->discipline->teacher->fullName() }}
                        </span>
                    </div>
                    <div class="flex items-center gap-1">
                        <x-mary-icon name="o-chat-bubble-left-ellipsis" class="w-3 h-3" />
                        <span>
                            {{ $disciplineClasseInfo->discipline->forum_topics_count }}
                            {{ Str::plural('tópico', $disciplineClasseInfo->discipline->forum_topics_count) }}
                        </span>
                    </div>

                    <p>{{ $disciplineClasseInfo->classe->name }}</p>
                    {{-- Botão de Acesso --}}
                    <a id="forum-access-btn" href="{{ route('student.forum.show', $disciplineClasseInfo->discipline->id) }}" wire:navigate
                        class="btn btn-block btn-sm bg-gradient-to-r from-blue-700 to-blue-500 text-white border-0 hover:from-blue-700 hover:to-blue-500 group-hover:shadow-lg transition-all">
                        <x-mary-icon name="o-arrow-right-circle" class="w-4 h-4" />
                        Acessar
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Tour Modal --}}
    <div class="fixed inset-0 z-40 bg-gray-900/50" x-data x-show="$store.tourguide.openMai">
        <div
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2
            -translate-y-1/2 w-full max-w-4xl sm:w-2/3 bg-white rounded-lg
            shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">
           <div class="flex justify-between items-center">
               <h2 class="text-lg font-normal text-gray-700">Opções do Fórum</h2>
                <div class="flex justify-end">
                    <x-mary-button icon="o-x-mark"
                        class="bg-white border-none btn-square hover:none hover:bg-pink-600 hover:text-gray-100"
                        x-on:click="$store.tourguide.cancelOptionsTourPage()" />
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <x-mary-button
                    class="font-medium text-blue-600 border-[1px] border-blue-600 btn hover:bg-blue-600 hover:text-gray-100"
                    label="Visão geral do Fórum"
                    x-on:click="$store.tourguide.start('StartTourStudentForum')" />
            </div>
        </div>
    </div>
</div>
