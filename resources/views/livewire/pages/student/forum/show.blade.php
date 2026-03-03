<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, On, Url};
use App\Models\{Discipline};
use App\Models\Forum\ForumTopic;

use Mary\Traits\Toast;

new #[Layout(name: 'layouts.student-panel')]  class extends Component {
  use Toast;
  public string $disciplineID = '';
  public $discipline;
  public $classes;

  public function mount(string $id)
    {
        $this->disciplineID = $id;
        $this->discipline = $this->loadDisciplineContent($this->disciplineID);
        $this->classes = $this->loadClasse($this->disciplineID);

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
                $q->orderBy('created_at', 'ASC')
                ->with(['classe','owner']);
            },
        ])
        ->where('id', $id)
        ->first();
    }

    private function loadClasse($discipline_id) {
        $this->classes = Discipline::find($discipline_id)->Classes;
        return $this->classes;
    }

    public function openNewTopicModal(){

        $this->dispatch(
            'student-create-new-topic',
            discipline_id: $this->disciplineID,
            classe_id: $this->discipline->classes->first()->id ?? null
        );
    }
}; ?>

<div>
  <div class="py-4">
     <a href="{{ route('student.forum.index') }}" wire:navigate>
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
        <button class="bg-pink-700 btn text-gray-50" type="button" @click="$wire.openNewTopicModal">Novo tópico</button>
        <livewire:pages.student.forum.components.modal-create-topic />
      </div>
      <div class="content relative top-4">
        <div>
            @foreach ($discipline->ForumTopics as $topic )
                <div class="bg-gray-100 p-5 my-2 rounded-sm">
                    <div>
                        <a href="{{ route('student.forum.topic.index', $topic->id) }}">{{ $topic->title }}</a>
                    </div>
                    <div>
                       {{ $topic->owner->full_name ?? $topic->owner->first_name ?? 'Desconecido' }}
                    </div>
                    <div class="">
                        <x-mary-badge value="{{ $topic->classe->name ?? 'Sem turma' }}"></x-mary-badge>
                    </div>

                    <span class="text-xs">
                    {{ $topic->created_at }}
                    </span>
                </div>
            @endforeach
        </div>
      </div>
   </div>
</div>
