<?php

use Livewire\Volt\{Component};
use App\Models\{Discipline};
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Illuminate\Database\Eloquent\Collection;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    public string $search = '';
    public Collection $disciplines;
    public int $totalDisciplines = 0;
    //TODO: criar um id fixo para isso
    public string $toKey;
    public function with()
    {
        return $this->disciplines = $this->loadDisciplines();
    }
    public function mount()
    {
        sleep(1);
        $this->toKey = Auth::user()->id;
    }

    private function loadDisciplines()
    {
        return Discipline::where('user_id', Auth::user()->id)
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }
    #[On('content-created')]
    public function updateDisciplineList($title, $action)
    {
        $this->success('Disciplina adicionada com sucesso!');
    }

    #[On('content-update')]
    #[On('content-deleted')]
    public function refreshDisciplineList($title, $action)
    {
        if ($action === 'update') {
            $this->toast(type: 'success', title: "{$title} atualizada com sucesso!", icon: 'o-check', css: 'alert-success');
        } elseif ($action === 'delete') {
            $this->error('Disciplina excluida com sucesso!');
        }
    }

    public function placeholder()
    {
        return Blade::render('<div class="flex flex-col items-center justify-center gap-3 p-5 text-sm font-normal text-center text-gray-600 rounded">
                <x-mary-loading />
                <span class="tracking-wider text-sm text-gray-600">Carregando disciplinas</span>
            </div>');
    }
}; ?>
<div class="flex flex-col gap-8" id="content-intial">
    <livewire:pages.teacher.discipline.modals.create />
    <div class="flex items-center justify-between">
        @if ($disciplines->count() > 0)
            <x-text-input placeholder="Pesquisar" wire:model.live="search" id="discipline-input-search"
                class="border-[1px] focus:border-pink-500 focus:outline focus:outline-pink-50 px-3 py-2 block  mt-1" />
        @else
            <span></span>
        @endif
        <button class="btn bg-pink-600 text-gray-50 discipline-new flex gap-1.5 items-center" id="discipline-create"
            x-on:click="$dispatch('create-content-modal',{open:true})" mary:key="discipline-create-button">
            <x-mary-icon name="o-plus" class="w-5 h-5" /> <span class="text-sm tracking-wide">Disciplina</span>
        </button>

    </div>
    @if ($disciplines->count() > 0)
        <div class="relative grid w-full grid-cols-5 gap-3 pb-5 mt-8" id="disciplines-all">
            @foreach ($disciplines as $discipline)
                <div class="border-[1px] border-gray-200 shadow-md  rounded-sm flex flex-col discipline-card"
                    id="discipline-{{ $discipline->id }}" data-tour="{{ $discipline->id }}">
                    <div class="flex items-center justify-between">
                        <span>
                        </span>
                        <x-mary-dropdown>
                            <x-slot:trigger id="tour-operations-{{ $discipline->id }}">
                                <x-mary-button icon="o-ellipsis-vertical" class="bg-white border-none" />
                            </x-slot:trigger>
                            <x-mary-menu-item title="Detalhes" class="text-gray-600" icon="o-document-magnifying-glass"
                                x-on:click="$dispatch('details-content-modal', { id: '{{ $discipline->id }}' })"
                                class="tour-discipline-details-{{ $discipline->id }}" />
                            <x-mary-menu-item title="Editar" class="text-gray-600" icon="o-pencil-square"
                                class="tour-discipline-edit-{{ $discipline->id }}"
                                x-on:click="$dispatch('edit-content-modal', { id: '{{ $discipline->id }}' })" />
                            <x-mary-menu-item title="Deletar" class="text-gray-600"
                                class="tour-discipline-delete-{{ $discipline->id }}" icon="o-trash"
                                x-on:click="$dispatch('delete-module-modal', { id: '{{ $discipline->id }}' })" />
                        </x-mary-dropdown>
                    </div>
                    <div class="p-6 text-center">
                        <a class="text-gray-600 hover:text-blue-600 content-title tracking-wide text-[15px] font-medium"
                            id="discipline-manager-content-{{ $discipline->id }}" wire:navigate
                            href="{{ route('teacher.discipline.show', $discipline) }}">{{ $discipline->title }}</a>
                    </div>
                </div>
                <livewire:pages.teacher.discipline.modals.details :discipline="$discipline" />
                <livewire:pages.teacher.discipline.modals.edit :discipline="$discipline" />
                <livewire:pages.teacher.discipline.modals.delete :id="$discipline->id" :title="$discipline->title" />
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center w-full gap-6 p-6 text-center ">
            <p class="text-lg font-medium text-gray-500">Você não possue nenhuma disciplina cadastrada</p>
        </div>
    @endif
    <div class="fixed inset-0 z-40 bg-gray-900/50" x-data x-show="$store.tourguide.openMai">
        <!-- Centralizando a div -->
        <div
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-4xl sm:w-2/3 bg-white rounded-lg shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">
            <div class="flex flex-col">
                <div class="flex justify-end">
                    <x-mary-button icon="o-x-mark"
                        class="bg-white border-none btn-square hover:none hover:bg-pink-600 hover:text-gray-100"
                        x-on:click="$store.tourguide.cancelOptionsTourPage()" />
                </div>
                <h2 class="text-lg font-normal text-gray-700">Você tem as seguintes opções:</h2>
            </div>
            <div class="flex items-center gap-3 p-4 bg-gray-100">
                <x-mary-button
                    class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                    label="Criar disciplina" x-on:click="$store.tourguide.start('StartTourCreateDiscipline')" />
                @if ($disciplines->count() > 0)
                    <x-mary-button
                        class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                        label="Pesquisar disciplina" @click="$store.tourguide.start('StartTourSearchDiscipline')" />
                    <x-mary-button
                        class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                        label="Gerenciar conteudo"
                        @click="$store.tourguide.start('StartTourManagerDisciplineContent')" />
                    <x-mary-button
                        class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                        label="Editar: Titulo,Descrição" @click="$store.tourguide.start('StartTourEditDiscipline')" />
                    <x-mary-button
                        class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                        label="Deletar disciplina" @click="$store.tourguide.start('StartTourDeleteDiscipline')" />
                @endif
            </div>
        </div>
        <img src="{{ asset('images/ai-sos.png') }}" class="fixed right-0 w-64 bottom-4 sm:w-48">
    </div>
</div>
