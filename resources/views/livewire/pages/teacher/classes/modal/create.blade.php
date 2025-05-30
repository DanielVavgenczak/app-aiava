<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use App\Models\Teacher\{Classe};
use App\Models\{Discipline};
use Illuminate\Support\Collection;

new class extends Component {
    public string $name = '';
    public bool $open = false;
    public string $disciplineId = '';
    public array $disciplines = [];
    public string $classId = '';
    public string $classNameAdded = '';
    public string $studentName = '';

    #[On('open-form-class-create')]
    public function openForm($open)
    {
        $this->open = $open;
        $this->getDisciplines();
    }

    public function close()
    {
        $this->open = false;
    }

    public function store()
    {
        try {
            $class = Classe::create([
                'name' => $this->name,
                'discipline_id' => $this->disciplineId,
                'teacher_id' => Auth::user()->id,
            ]);
            sleep(5);
            $this->classId = $class->id;
            $this->classNameAdded = $class->name;
        } catch (\Exception $e) {
            dd('Deu erro ao cadastrar', $e->getMessage());
        }
    }

    private function getDisciplines()
    {
        $this->disciplines = Discipline::where('user_id', Auth::user()->id)
            ->get()
            ->toArray();
    }
}; ?>

<x-mary-modal wire:model="open" class="">
    <div class="py-4">
        <h2 class="text-gray-800 text-lg font-semibold tracking-wide">Nova turma</h2>
    </div>
    @if ($classId === '')
        <form wire:submit.prevent="store">
            <div>
                <div class="mb-4">
                    <label class="block mb-1 text-sm font-medium text-gray-700">Disciplina</label>
                    <x-mary-select wire:model="disciplineId" placeholder="Selecione a disciplina" :options="$disciplines"
                        option-label="title" option-value="id" placeholder-value="0" class="w-full" />
                </div>
            </div>
            <div class="my-3" id="classe-input-name">
                <x-mary-input wire:model="name" label="Turma" class="class-input-name" autocomplete="title" />
            </div>
            <div class="flex gap-2 items-center justify-end my-3" id="discipline-save">
                <div class="flex items-center gap-2" wire:loading.remove>
                    <button class="text-gray-500 btn-ghost cursor-pointer" x-on:click.prevent="$wire.open = false"
                        id="create-cancel">
                        Cancelar
                    </button>
                </div>
                <div class="flex items-center gap-2" wire:loading.remove>
                    <button class="btn text-white capitalize bg-pink-600 hover:bg-pink-900" type="submit"
                        id="create-confirm">
                        cadastrar
                    </button>
                </div>
                <div wire:loading wire:target="store" class="flex items-center gap-2">
                    <span class="text-xs uppercase font-semibold tracking-wider text-pink-700">cadastrando</span>
                    <x-mary-loading class="loading-dots text-pink-700" />
                </div>
            </div>
        </form>
    @endif
    @if ($classId != '')
        <div>
            <h3 class="text-sm font-semibold tracking-wide">{{ $classNameAdded }}</h3>
            <p class="text-xs uppercase font-semibold">adicionar alunos</p>
            <form>
                <div>
                    <x-mary-input label="Nome do aluno">
                        <x-slot:append>
                            {{-- Add `join-item` to all appended elements --}}
                            <x-mary-button icon="o-plus" class="join-item btn-primary" />
                        </x-slot:append>
                    </x-mary-input>
                </div>
                <div>
                    <button class="btn btn-ghost">cancelar</button>
                    <button class="btn bg-pink-700">registrar</button>
                </div>
            </form>
            {{-- <x-mary-input label="Nome do aluno" wire:model="studentName" placeholder="Nome do aluno"
                    icon="o-user" /> --}}
        </div>
    @endif
</x-mary-modal>
