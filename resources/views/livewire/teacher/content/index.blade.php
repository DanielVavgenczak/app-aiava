<?php

use Livewire\Volt\{Component};
use App\Models\{Discipline};
use Illuminate\Support\Facades\Auth;
new class extends Component {
    public string $search = '';

    public function with()
    {
        return [
            'disciplines' => Discipline::where('user_id', Auth::user()->id)
                ->when($this->search, function ($query) {
                    $query->where('title', 'like', '%' . $this->search . '%');
                })
                ->orderBy('created_at', 'desc')
                ->get(),
        ];
    }
    public function mount()
    {
        sleep(2);
    }
    public function placeholder()
    {
        return <<<'HTML'
            <div class="flex items-center justify-center gap-1 p-5 text-sm font-normal text-center text-gray-600 uppercase rounded">
                <x-mary-icon name="c-arrow-path" class="w-5 h-5 animate-spin" />
                <span>carregando disciplinas...</span>
            </div>
        HTML;
    }
}; ?>
@if (!$disciplines->count())
    <div class="flex flex-col items-center justify-center w-full gap-6 p-6 text-center">
        <p class="text-sm text-gray-600 uppercase">Você não possue nenhuma disciplina cadastrada</p>
        <x-mary-button label="Nova disciplina" icon-right="o-plus" tooltip-left="Registra uma nova disciplina"
            class="w-1/5 text-white no-underline bg-pink-600 hover:bg-pink-900"
            link="{{ route('teacher.content.create') }}" />
    </div>
@endif
<div class="flex flex-col w-3/4 gap-8">
    <div class="">
        <x-text-input placeholder="Pesquisar" wire:model.live="search" />
    </div>
    <div class="relative grid w-full grid-cols-4 gap-4 mt-8">
        @forelse ($disciplines as $discipline)
            <div
                class="text-center text-gray-800  border-gray-200 border-[1px] shadow-lg hover:bg-blue-500  rounded-md p-6 hover:text-white">
                <a href="{{ route('teacher.content.show', $discipline->id) }}"
                    wire:navigate>{{ $discipline->title }}</a>
            </div>
        @endforeach
    </div>
    <div>
        <a href="{{ route('teacher.content.create') }}" wire:navigate
            class="px-2 py-3 text-sm font-semibold text-white no-underline bg-pink-600 rounded-md">nova
            disciplina</a>
    </div>

</div>
