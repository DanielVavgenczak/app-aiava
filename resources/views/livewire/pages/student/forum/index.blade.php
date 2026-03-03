<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Teacher\Student;
use Illuminate\Support\Facades\Auth;

new #[Layout(name: 'layouts.student-panel')] class extends Component {
    public $teacher_id;
    public $discipline;
    public $authenticated;
    public $forumTopicsCount = 0;
    public function mount()
    {
        $this->authenticatedUser();
        $this->getDisciplineForum();
    }

    protected function getDisciplineForum()
    {

        if(Auth::guard('student')->check()) {
            $this->discipline = Student::where('id', $this->authenticated['logged']->id)
            ->with(['discipline' => function($q) { $q->withCount('forumTopics'); }])
            ->first()->discipline;

           return $this->discipline;
        }
    }


    private function authenticatedUser()
    {
        if(!Auth::guard('teacher')->check()) {
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
        <a href="#">
            <x-mary-icon name="o-arrow-left" class="w-4" />
            <span></span>
        </a>
    </div>
    <div class="p-5  bg-white flex flex-col gap-4">
        <div>
            <h2>Forum</h2>
        </div>
        <div class="flex flex-col gap-2">
            <h3>
                {{ $discipline->title ?? 'Nenhuma disciplina encontrada' }}
            </h3>
            <div>
                {{ $discipline->forum_topics_count  }} topicos
                <div>
                    <a href="{{ route('student.forum.show', $discipline->id) }}">Ver tópicos</a>
                </div>
            </div>
        </div>
    </div>
</div>
