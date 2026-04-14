<?php

use Livewire\Component;
use Livewire\Attributes\{On, Validate};
use App\Models\Forum\ForumTopic;

new class extends Component {
    public bool $open = false;
    public string $discipline_id = '';

    #[Validate('required', message: 'O campo Novo topico é obrigatório!')]
    public string $title = '';

    #[On('student::new-topic-forum')]
    public function openModal($discipline_id)
    {
        $this->discipline_id = $discipline_id;
        $this->open = true;
    }

    public function save()
    {
        $this->validate();

        ForumTopic::create([
            'title' => $this->title,
            'discipline_id' => $this->discipline_id,
            'classe_id' => Auth::guard('student')->user()->classe->id,
            'owner_id' => Auth::guard('student')->user()->id,
            'owner_type' => 'App\Models\Teacher\Student',
        ]);

        $this->open = false;
        $this->dispatch('forum-event', type:'create', msg:'Topico criado com sucesso!');
        $this->reset('title');
    }
}; ?>


<x-mary-modal wire:model="open" title="Novo topico" subtitle="Crie um novo tópico">
        <form wire:submit.prevent="save">
            <div class="mb-4">
                <x-mary-input wire:model="title" label="Novo tópico" class="class-input-name !outline-none"
                autocomplete="title" />
            </div>
            <div class="mt-2 flex justify-end w-full">
                <button class="btn btn-sm bg-ghost" @click="$wire.open = false">
                    Cancelar
                </button>
                <button class="btn btn-sm text-gray-50 capitalize bg-blue-700 hover:bg-blue-900" type="submit" wire:loading.remove>
                    cadastrar
                </button>
               <div wire:loading wire:target="save" class="flex flex-col justify-center w-full text-center">
                <span class="text-xs tracking-wider text-blue-600">Criando topico</span>
                <div class="mb-2">
                    <x-mary-loading class="text-blue-600 loading-dots" />
                </div>
            </div>
            </div>
        </form>
</x-mary-modal>
