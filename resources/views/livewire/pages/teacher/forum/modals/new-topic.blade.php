<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{On, Validate};
use App\Models\Forum\ForumTopic;
new class extends Component {
    public bool $open = false;
    public string $discipline_id = '';
    #[Validate('required', message: 'O campo Novo topico é obrigatório0!')]
    public string $title;

    #[On('create-new-topic')]
    public function open($discipline_id)
    {
        $this->open = true;
        $this->discipline_id = $discipline_id;
        $this->js("console.log('open modal new topicss');");
    }

    public function save()
    {
        $this->validate([
            'title' => 'required|string|min:3',
        ]);
        ForumTopic::create([
            'title' => $this->title,
            'discipline_id' => $this->discipline_id
        ]);

        $this->open = false;
        $this->dispatch('forum-event', type:'create', msg:'Topico criado com sucesso!');
    }
}; ?>

<x-mary-modal wire:model="open" title="Novo topico" subtitle="Crie um novo tópico">
    <div>
        <form wire:submit.prevent="save">
            <div>
                <x-mary-input wire:model="title" label="Novo tópico" class="class-input-name !outline-none"
                autocomplete="title" />
            </div>
            <div class="mt-2">
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
