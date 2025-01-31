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
        sleep(1);
    }
    public function openNewDiscipline()
    {
        $this->dispatch('open-modal-new-discipline', true);
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
<div class="flex flex-col w-3/4 gap-8" id="content-intial">
    <div class="flex items-center justify-between">
        <x-text-input placeholder="Pesquisar" wire:model.live="search" id="content-input-search" />

        <x-mary-button label="Disciplina" class="bg-pink-600 text-gray-50 content-discipline-new" icon-right="o-plus"
            link="{{ route('teacher.content.create') }}" />
    </div>
    <div class="relative grid w-full grid-cols-4 gap-4 mt-8">
        @forelse ($disciplines as $discipline)
            <div class="border-[1px] shadow-lg  rounded-sm flex flex-col" id="content-card-discipline">
                <div class="flex items-center justify-between">
                    <span>
                    </span>
                    <x-mary-dropdown>
                        <x-slot:trigger>
                            <x-mary-button icon="o-ellipsis-vertical"
                                class="bg-white border-none content-discipline-crud" />
                        </x-slot:trigger>
                        <x-mary-menu-item title="Editar" class="text-gray-600" icon="o-trash" />
                        <x-mary-menu-item title="Deletar" class="text-gray-600" icon="o-archive-box" />
                    </x-mary-dropdown>
                </div>
                <div class="p-6 text-center">
                    <a class="hover:text-blue-600 content-discipline-title"
                        href="{{ route('teacher.content.show', $discipline->id) }}">{{ $discipline->title }}</a>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Overlay tourguide -->
    <div x-data x-show="$store.tourguide.openMai" class="fixed inset-0 z-50 "
        :class="!$store.tourguide.helperContentInit ? 'bg-black bg-opacity-80' : 'bg-none'">
        <img src="{{ asset('images/ai-sos.png') }}" class="absolute right-0 w-64 bottom-4 ">
    </div>
    <!-- Popover do Tour -->
    <div id="tour-popover" x-data x-show="$store.tourguide.popoverContent"
        class="absolute z-50 flex flex-col w-auto gap-4 p-6 text-gray-800 bg-white rounded-tl-lg rounded-tr-lg rounded-bl-lg">
        <p id="tour-message" class="break-all"></p>
        <div class="flex items-center justify-between gap-4 p-3 bg-gray-200" x-show="$store.tourguide.helperContent">
            <x-mary-button class="btn-outline" label="Pesquisar uma disciplina ?"
                @click="$store.tourguide.searchDiscipline()" />
            <x-mary-button class="btn-outline" label="Criar uma disciplina ?"
                @click="$store.tourguide.tourHelpNewDiscipline()" />
            <x-mary-button class="btn-outline" label="Gerenciar uma disciplina ?"
                @click="$store.tourguide.tourHelpManagerDiscipline()" />
            <x-mary-button class="btn-outline" label="Deseja deletar / editar?"
                @click="$store.tourguide.tourHelpEditOrDeleteDiscipline()" />
        </div>
    </div>
</div>
