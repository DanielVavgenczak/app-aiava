<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Teacher\Student;
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
        $this->disciplineClasseInfo = Student::with(['classe', 'discipline' => fn($query) => $query->withCount('ForumTopics')])
        ->findOrFail($this->authenticated['logged']->id);
        return $this->disciplineClasseInfo;
    }

    // TODO: Refactor this method to clear more"
    private function authenticatedUser()
    {
        if (!Auth::guard('teacher')->check()) {
            $this->authenticated['logged'] = Auth::guard('student')->user();
            $this->authenticated['type'] = 'student';
            return $this->authenticated;
        }

        $this->authenticated['logged'] = Auth::guard('teacher')->user();
        $this->authenticated['type'] = 'teacher';
        return $this->authenticated;
    }
}; ?>
<div>
    <div class="py-4">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('student.index') }}" wire:navigate
                class="text-gray-500 hover:text-pink-700 transition-colors">
                <x-mary-icon name="o-home" class="w-4 h-4" />
            </a>
            <x-mary-icon name="o-chevron-right" class="w-3 h-3 text-gray-400" />
            <a href="{{ route('student.forum.index') }}" wire:navigate class="text-gray-500 hover:text-pink-700">
                Fórum
            </a>
            {{-- <x-mary-icon name="o-chevron-right" class="w-3 h-3 text-gray-400" />
            <span class="text-pink-700 font-medium">{{ $discipline->title }}</span> --}}
        </div>
    </div>

    <div class="p-5  bg-white flex flex-col gap-4">
        <div class="px-6 py-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div
                        class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                        <x-mary-icon name="o-chat-bubble-left-right" class="w-6 h-6 text-gray-900" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Postagens no Fórum</h2>
                        <p class="text-gray-600 text-sm">Acompanhe as discussões das sua turma</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="w-1/4 relative bg-gradient-to-br from-gray-50 to-white border-2 border-gray-200 rounded-xl p-4 hover:shadow-xl hover:border-blue-400 hover:-translate-y-1 transition-all duration-300"
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
                <a href="{{ route('student.forum.show', $disciplineClasseInfo->discipline->id) }}" wire:navigate
                    class="btn btn-block btn-sm bg-gradient-to-r from-blue-700 to-blue-500 text-white border-0 hover:from-blue-700 hover:to-blue-500 group-hover:shadow-lg transition-all">
                    <x-mary-icon name="o-arrow-right-circle" class="w-4 h-4" />
                    Acessar
                </a>
            </div>
        </div>
    </div>
</div>
</div>
