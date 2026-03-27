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
        $this->disciplineClasseInfo = Student::with(['classe','discipline'])
            ->findOrFail($this->authenticated['logged']->id);
        return $this->disciplineClasseInfo;

    }

    // TODO: Refactor this method to clear more
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
            <div>

                <h3>
                    {{ $disciplineClasseInfo->discipline->title }}
                </h3>
                <p>{{ $disciplineClasseInfo->classe->name  }}</p>
            <span> <x-mary-icon name="o-user-circle" class="w-4 h-4" />Professor:
                {{$disciplineClasseInfo->discipline->teacher->fullName() }}
            </span>
            </div>
            <div>
                {{ $disciplineClasseInfo->discipline->forum_topics_count  }} topicos
                <div>
                    <a href="{{ route('student.forum.show', $disciplineClasseInfo->discipline->id) }}">Ver tópicos</a>
                </div>
            </div>
        </div>
    </div>
</div>
