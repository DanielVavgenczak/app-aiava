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
        return Discipline::with([
            'modules' => function ($q) {
                $q->orderBy('created_at', 'ASC'); // Ordem crescente
            },
            'modules.lessons' => function ($q) {
                $q->orderBy('created_at', 'ASC'); // Ordem crescente
            },
            'modules.activitTasks' => function ($q) {
                $q->orderBy('created_at', 'ASC'); // Ordem crescente
            },
        ])
            ->where('id', $id)
            ->first();
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
       <h2>{{ $discipline->title }}</h2>
        <button class="bg-pink-700 btn text-gray-50"
        @click="$wire.openNewTopicModal">Criar novo tópico</button>
        <livewire:pages.teacher.forum.modals.new-topic />
      </div>
      <div class="content relative top-4">
        <div class="p-3 p-4 border-[1px] border-gray-200 bg-gray-50">
          <div class="flex flex-col">
            <div class="w-12 h-12 rounded-full overflow-hidden bg-gradient-to-br from-blue-100 to-purple-100">
              <img
              src="https://unsplash.com/pt-br/fotografias/man-in-black-button-up-shirt-ZHvM3XIOHoE"
              alt="Foto de perfil"
              class="w-full h-full object-cover hover:scale-110 transition-transform duration-300"
              onerror="this.src='https://ui-avatars.com/api/?name=Usuário&background=4f46e5&color=fff&size=128'"
              >
            </div>
            <h3>Fulando</h3>
          </div>
          <div>
            <h3>Alguem conseguiu resolver os exercicios da a</h3>
          </div>

        </div>
      </div>


   </div>
</div>
