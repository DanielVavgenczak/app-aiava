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
<div class="flex flex-col gap-8" id="content-intial">
    <livewire:teacher.content.modals.create-content />
    <div class="flex items-center justify-between">
        <x-text-input placeholder="Pesquisar" wire:model.live="search" id="content-input-search" />
        <x-mary-button label="Disciplina" class="bg-pink-600 text-gray-50 content-discipline-new"
            id="content-create-discipline" icon-right="o-plus" @click="$dispatch('create-content-modal',{open:true})" />
    </div>
    <div class="relative grid w-full grid-cols-5 gap-3 pb-5 mt-8" id="content-all-disciplines">
        @forelse ($disciplines as $discipline)
            <div class="border-[1px] shadow-lg  rounded-sm flex flex-col">
                <div class="flex items-center justify-between">
                    <span>
                    </span>
                    <x-mary-dropdown>
                        <x-slot:trigger id="content-delete-edit">
                            <x-mary-button icon="o-ellipsis-vertical" id="delete-edit"
                                class="bg-white border-none content-discipline-crud" />
                        </x-slot:trigger>
                        <x-mary-menu-item title="Editar" class="text-gray-600" icon="o-pencil-square" />
                        <x-mary-menu-item title="Deletar" class="text-gray-600" id="delete-content" icon="o-trash"
                            @click="$dispatch('delete-content-modal', { id: '{{ $discipline->id }}' })" />
                    </x-mary-dropdown>
                </div>
                <div class="p-6 text-center manager-discipline" id="content-manager-discipline">
                    <a class="hover:text-blue-600 content-title" wire:navigate
                        href="{{ route('teacher.content.show', $discipline->id) }}">{{ $discipline->title }}</a>
                </div>
            </div>
            <livewire:teacher.content.modal-delete-content :id="$discipline->id" :title="$discipline->title" />
        @endforeach
    </div>
    <div x-data class="fixed inset-0 z-40 bg-black bg-opacity-50" x-show="$store.tourguide.openMai">
        <!-- Centralizando a div -->
        <div
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-4xl sm:w-2/3 bg-white rounded-lg shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">
            <div class="flex flex-col">
                <div class="flex justify-end">
                    <x-mary-button icon="o-x-mark"
                        class="bg-white border-none btn-square hover:none hover:bg-pink-600 hover:text-gray-100"
                        @click="$store.tourguide.closeTour()" />
                </div>
                <h2 class="text-lg font-normal text-gray-700">Aqui temos a pagina que lista as funcinalidades da
                    disciplina</h2>
            </div>
            <div class="flex items-center justify-center gap-3 p-4 bg-gray-100">
                <x-mary-button
                    class="font-light text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                    label="Pesquisar uma disciplina ?" @click="$store.tourguide.contenSearchDiscipline()" />
                <x-mary-button
                    class="font-light text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                    label="Criar" @click="$store.tourguide.contenCreateDiscipline()" />
                <x-mary-button
                    class="font-light text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                    label="Gerenciar" @click="$store.tourguide.contenManagerDiscipline()" />
                <x-mary-button
                    class="font-light text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                    label="Editar" @click="$store.tourguide.contenDeleteEditDiscipline()" />
                <x-mary-button
                    class="font-light text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                    label="Deletar" @click="$store.tourguide.startTourContentDelete()" />
            </div>
        </div>
        <img src="{{ asset('images/ai-sos.png') }}" class="fixed right-0 w-64 bottom-4 sm:w-48">
    </div>
</div>
