<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, On, Url};
use App\Models\{Discipline};
use Mary\Traits\Toast;

new #[Layout('layouts.app')]  class extends Component {
  use Toast;
  public string $disciplineID = '';
  public $discipline;

  public function mount(string $id)
    {
        $this->disciplineID = $id;
        $this->discipline = $this->loadDisciplineContent($this->disciplineID);
        //$this->allLessons = $this->discipline->modules->flatMap->lessons->values()->toArray();
    }

    #[On('forum-event')]
    public function update($type, $msg)
    {
        match ($type) {
            'update', 'create', 'uploaded' => $this->handleEventWithToast($type, $msg),
            'delete' => $this->handleEventWithToast($type, $msg),
            default => null,
        };
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
        return Discipline::withCount('ForumTopics') // Adiciona a contagem
        ->with([
            'ForumTopics' => function ($q) {
                $q->orderBy('created_at', 'ASC');
            },
        ])
        ->where('id', $id)
        ->first(); // Não esqueça este first()!
    }


    public function openNewTopicModal(){
        $this->dispatch('create-new-topic', discipline_id: $this->disciplineID);
    }
}; ?>

<div>
  <div class="py-4">
     <a href="{{ route('teacher.forum') }}" wire:navigate>
            <x-mary-icon name="o-arrow-left" class="w-4 text-gray-500" />
            <span class="text-xs font-semibold tracking-wider text-gray-500 uppercase">voltar</span>
        </a>
   </div>
   <div class="bg-white p-5">
      <div class="flex items-center justify-between">
        <div>
            <h2 >{{ $discipline->title }}</h2>
            <small>Perguntar como selecionar a turma</small>
        </div>
        <button class="bg-pink-700 btn text-gray-50"
        @click="$wire.openNewTopicModal">Criar novo tópico</button>
        <livewire:pages.teacher.forum.modals.new-topic />
      </div>
      <div class="content relative top-4">
        <div>
            @foreach ($discipline->ForumTopics as $topic )
                <div class="bg-gray-100 p-5 my-2 rounded-sm">
                    <div>
                        <a href="{{ route('teacher.forum.question', $topic->id) }}">{{ $topic->title }}</a>
                    </div>
                    <span>
                    {{ $topic->created_at }}
                    </span>
                </div>
            @endforeach
        </div>
      </div>
   </div>
</div>
