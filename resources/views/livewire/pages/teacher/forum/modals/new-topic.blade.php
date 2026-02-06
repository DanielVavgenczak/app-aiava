<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{On, Validate};
use App\Models\Forum\ForumTopic;
use Illuminate\Support\Collection;

new class extends Component {
    public bool $open = false;
    public string $discipline_id = '';

    #[Validate('required', message: 'O campo Novo topico é obrigatório0!')]
    public string $title;
    public  $classes = [];

    #[Validate('required', message: 'O campo Classe é obrigatório!')]
    public $classe_id;

    #[On('create-new-topic')]
    public function open($discipline_id, $classes = null)
    {
        $this->open = true;
        $this->discipline_id = $discipline_id;
        $this->classes = $classes;
   }

   public function save()
    {
        $this->validate([
            'title' => 'required|string|min:3',
            "classe_id" => 'required|exists:classes,id',
        ]);
        ForumTopic::create([
            'title' => $this->title,
            'discipline_id' => $this->discipline_id,
            'classe_id' => $this->classe_id,
        ]);

        $this->open = false;
        $this->dispatch('forum-event', type:'create', msg:'Topico criado com sucesso!');
    }
}; ?>

<x-mary-modal wire:model="open" title="Novo topico" subtitle="Crie um novo tópico">
    <div>
        <form wire:submit.prevent="save">
            <div class="mb-4">
                <x-mary-input wire:model="title" label="Novo tópico" class="class-input-name !outline-none"
                autocomplete="title" />
            </div>
            <div class="mb-4" id="classe-input-discipline">
                <label class="block mb-1 text-sm font-medium text-gray-700">Turma</label>
                    <x-mary-select wire:model="classe_id"
                    placeholder="Selecione uma turma" :options="$classes"
                        option-label="name" option-value="id" placeholder-value="0" class="w-full !outline-none" />
            </div>
            <div class="mt-2 flex justify-end w-full">
                <button class="btn btn-sm text-gray-50 capitalize bg-pink-700 hover:bg-pink-900" type="submit" wire:loading.remove>
                    cadastrar
                </button>
               <div wire:loading wire:target="save" class="flex flex-col justify-center w-full text-center">
                <span class="text-xs tracking-wider text-pink-600">Criando topico</span>
                <div class="mb-2">
                    <x-mary-loading class="text-pink-600 loading-dots" />
                </div>
            </div>
            </div>
        </form>
    </div>
    </x-model>
