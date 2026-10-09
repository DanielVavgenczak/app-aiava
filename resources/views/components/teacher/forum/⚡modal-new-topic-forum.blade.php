<?php

use Livewire\Component;
use Livewire\Attributes\{On, Validate};
use App\Models\Forum\ForumTopic;
use App\Models\Teacher\Classe;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public bool $open = false;
    public string $discipline_id = '';

    #[Validate('required', message: 'O campo Novo topico é obrigatório0!')]
    public string $title;
    public $classes = [];

    #[Validate('required', message: 'O campo Classe é obrigatório!')]
    public $classe_id;

    #[On('teacher::new-topic-forum')]
    public function open($discipline_id, $classes)
    {
        $this->open = true;
        $this->discipline_id = $discipline_id;
        $this->classes = $classes;
    }

    protected function getClassStudent()
    {
        return Classe::where('discipline_id', $this->discipline_id)->get();
    }

    public function save()
    {
        $this->validate([
            'title' => 'required|string|min:3',
            'classe_id' => 'required|exists:classes,id',
        ]);

        $teacher = Auth::guard('teacher')->user();
        $discipline = \App\Models\Teacher\Discipline::forTeacher($teacher->id)
            ->where('id', $this->discipline_id)
            ->first();

        if (!$discipline) {
            $this->error('Você não tem permissão para criar tópicos nesta disciplina.');
            $this->open = false;
            return;
        }

        if (!\App\Models\Teacher\Classe::where('id', $this->classe_id)
            ->where('discipline_id', $this->discipline_id)
            ->exists()) {
            $this->error('Turma inválida para esta disciplina.');
            $this->open = false;
            return;
        }

        ForumTopic::create([
            'title' => $this->title,
            'discipline_id' => $this->discipline_id,
            'classe_id' => $this->classe_id,
            'owner_id' => $teacher->id,
            'owner_type' => 'App\Models\Teacher\Teacher',
        ]);

        $this->open = false;
        $this->dispatch('forum-event', type: 'create', msg: 'Topico criado com sucesso!');
    }

    #[On('teacher::close-create-forum-topic')]
    public function cancel()
    {
        $this->open = false;
        $this->reset(['title', 'classe_id']);
    }
}; ?>

<x-mary-modal wire:model="open" title="Novo topico" subtitle="Crie um novo tópico">

    <form wire:submit.prevent="save">
        <div id="input-register-topic" class="p-4">

            <div class="mb-4">
                <label class="block mb-1 text-sm font-medium text-gray-700">Novo topico</label>
                <input wire:model="title" class="input w-full !outline-none" autocomplete="title" />
            </div>
            <div class="mb-4">
                <label class="block mb-1 text-sm font-medium text-gray-700">Turma</label>
                <select
                    class="w-full select select-bordered !outline-none focus:border-gray-300 bg-white rounded-lg border border-gray-300 px-3 py-2 text-gray-700"
                    wire:model="classe_id" placeholder="Selecione uma turma">
                    <option value="0" selected>Selecione uma turma</option>
                    @foreach($classes as $class)
                    <option value="{{ $class['id'] }}">{{ $class['name'] }}</option>
                    @endforeach
                </select>
                @error('classe_id')
                <span class="text-xs text-red-500">{{ $message }}</span>
                @enderror
            </div>
            <div class="mt-2 flex justify-end w-full">
                <button id="btn-cancel-register" type="button" class="btn btn-sm text-gray-800 capitalize bg-ghost"
                    x-on:click="$wire.cancel">
                    Cancelar
                </button>
                <button id="btn-save-register" class="btn btn-sm text-gray-50 capitalize bg-blue-400 hover:bg-blue-900"
                    type="submit">
                    Criar
                </button>
                <div wire:loading wire:target="save" class="flex flex-col justify-center w-full text-center">
                    <span class="text-xs tracking-wider text-blue-400">Criando topico</span>
                    <div class="mb-2">
                        <x-mary-loading class="text-blue-600 loading-dots" />
                    </div>
                </div>
            </div>

        </div>
    </form>
</x-mary-modal>
