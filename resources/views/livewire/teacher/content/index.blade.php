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
    <div class="flex items-center justify-between">
        <x-text-input placeholder="Pesquisar" wire:model.live="search" id="content-input-search" />
        <x-mary-button label="Disciplina" class="bg-pink-600 text-gray-50 content-discipline-new"
            id="content-create-discipline" icon-right="o-plus" link="{{ route('teacher.content.create') }}" />
    </div>
    <div class="relative grid w-full grid-cols-5 gap-3 mt-8">
        @forelse ($disciplines as $discipline)
            <div class="border-[1px] shadow-lg  rounded-sm flex flex-col">
                <div class="flex items-center justify-between">
                    <span>
                    </span>
                    <x-mary-dropdown>
                        <x-slot:trigger>
                            <x-mary-button icon="o-ellipsis-vertical" id="content-card-delete-edit"
                                class="bg-white border-none content-discipline-crud" />
                        </x-slot:trigger>
                        <x-mary-menu-item title="Editar" class="text-gray-600" icon="o-trash" />
                        <x-mary-menu-item title="Deletar" class="text-gray-600" icon="o-archive-box" />
                    </x-mary-dropdown>
                </div>
                <div class="p-6 text-center" id="content-card-discipline">
                    <a class="hover:text-blue-600 content-discipline-title"
                        href="{{ route('teacher.content.show', $discipline->id) }}">{{ $discipline->title }}</a>
                </div>
            </div>
        @endforeach
    </div>
    <div x-data class="fixed inset-0 z-40 bg-black bg-opacity-50" x-show="$store.tourguide.showOptionsPageContent">
        <x-mary-button label="Fechar" class="fixed py-2 bg-pink-600 top-4 right-4" x-show="$store.tourguide.openMai"
            @click="$store.tourguide.closeTour()" />
        <!-- Centralizando a div -->
        <div
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-4xl sm:w-2/3 bg-white rounded-lg shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">

            <h2 class="text-lg font-semibold text-gray-700">Aqui temos a pagina que lista as funcinalidades da
                disciplina</h2>
            <div class="flex items-center justify-center p-4 bg-gray-100 gap-3">
                <x-mary-button class="btn-outline" label="Pesquisar uma disciplina ?"
                    @click="$store.tourguide.contenSearchDiscipline()" />
                <x-mary-button class="btn-outline" label="Criar uma disciplina ?"
                    @click="$store.tourguide.contenCreateDiscipline()" />
                <x-mary-button class="btn-outline" label="Gerenciar uma disciplina ?"
                    @click="$store.tourguide.contenManagerDiscipline()" />
                <x-mary-button class="btn-outline" label="Deseja deletar / editar?"
                    @click="$store.tourguide.contenDeleteEditDiscipline()" />
            </div>
        </div>
        <img src="{{ asset('images/ai-sos.png') }}" class="fixed right-0 w-64 bottom-4 sm:w-48">
    </div>

</div>
