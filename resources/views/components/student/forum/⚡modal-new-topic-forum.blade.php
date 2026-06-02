<?php

use Livewire\Component;
use Livewire\Attributes\{On, Validate};
use App\Models\Forum\ForumTopic;
use App\Models\Teacher\Classe;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public bool $open = false;
    public string $discipline_id = '';

    #[Validate('required', message: 'O campo título é obrigatório!')]
    public string $title = '';

    public $classes = [];
    public $classe_id;

    #[On('student::new-topic-forum')]
    public function openModal($discipline_id)
    {
        $this->discipline_id = $discipline_id;

        if (Auth::guard('teacher')->check()) {
            $this->classes = Classe::where('discipline_id', $discipline_id)->get();
        }

        $this->open = true;
    }

    public function save()
    {
        $this->validate();

        $author = Auth::guard('teacher')->user() ?? Auth::guard('student')->user();

        if (Auth::guard('student')->check()) {
            ForumTopic::create([
                'title' => $this->title,
                'discipline_id' => $this->discipline_id,
                'classe_id' => Auth::guard('student')->user()->classe->id,
                'owner_id' => $author->id,
                'owner_type' => 'App\Models\Teacher\Student',
            ]);
        } else {
            $this->validate(['classe_id' => 'required|exists:classes,id']);

            ForumTopic::create([
                'title' => $this->title,
                'discipline_id' => $this->discipline_id,
                'classe_id' => $this->classe_id,
                'owner_id' => $author->id,
                'owner_type' => 'App\Models\Teacher\Teacher',
            ]);
        }

        $this->open = false;
        $this->dispatch('forum-event', type:'create', msg:'Tópico criado com sucesso!');
        $this->reset('title', 'classe_id');
    }

    public function cancel()
    {
        $this->open = false;
        $this->reset('title', 'classe_id');
    }
}; ?>


<x-mary-modal wire:model="open" title="Novo tópico" subtitle="Crie um novo tópico">
    <form wire:submit.prevent="save">
        <div class="mb-4">
            <x-mary-input wire:model="title" label="Título" class="class-input-name !outline-none"
                autocomplete="title" />
        </div>

        @if(Auth::guard('teacher')->check())
        <div class="mb-4">
            <label class="block mb-1 text-sm font-medium text-gray-700">Turma</label>
            <x-mary-select wire:model="classe_id"
                placeholder="Selecione uma turma" :options="$classes"
                option-label="name" option-value="id" placeholder-value="0" class="w-full !outline-none" />
        </div>
        @endif

        <div class="mt-2 flex justify-end w-full">
            <button type="button" class="btn btn-sm bg-ghost" wire:click="cancel">
                Cancelar
            </button>
            <button class="btn btn-sm text-gray-50 capitalize bg-blue-700 hover:bg-blue-900" type="submit" wire:loading.remove>
                Cadastrar
            </button>
            <div wire:loading wire:target="save" class="flex flex-col justify-center w-full text-center">
                <span class="text-xs tracking-wider text-blue-600">Criando tópico</span>
                <div class="mb-2">
                    <x-mary-loading class="text-blue-600 loading-dots" />
                </div>
            </div>
        </div>
    </form>
</x-mary-modal>
