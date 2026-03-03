<?php

use Livewire\Volt\Component;
use App\Models\{Discipline};

new class extends Component {
  public $disciplines;
  public string $search = '';

  public function mount()
  {
      $this->disciplines = $this->getDisciplines();
  }

    private function getDisciplines(){
       $teacher_id = Auth::guard('teacher')->user()->id;
        return Discipline::where('teacher_id', $teacher_id)
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%');
            })
            ->withCount('ForumTopics')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}; ?>

<div>
  <div class="flex items-center justify-between">
    <h2>Foruns</h2>
  </div>
    <div class="grid grid-cols-5 gap-2">
      @foreach ($disciplines as $discipline)
        <div class="p-4 border rounded border-[1px] border-gray-300">
          <h3 class="text-gray-700 text-sm font-normal">{{ $discipline->title }}</h3>
          topicos: {{  $discipline->forum_topics_count }}
          <a href="{{ route('teacher.forum.show', $discipline->id) }}">acessar</a>
        </div>

      @endforeach
    </div>
</div>
