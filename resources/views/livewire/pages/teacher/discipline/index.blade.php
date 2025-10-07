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

    public function with()
    {
        $this->js("console.log('foi chamando')");
        return $this->disciplines = $this->loadDisciplines();
    }
    public function mount()
    {
        $this->js("console.log('foi chamando o mount de disciplinas')");
        sleep(1);
    }

    private function loadDisciplines()
    {
        $teacher_id = Auth::guard('teacher')->user()->id;
        return Discipline::where('teacher_id', $teacher_id)
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

    #[On('discipline-event')]
    public function refresh($type, $msg)
    {
        $this->js("console.log('Com tour chamado')");
        match ($type) {
            'update', 'create' => $this->handleEventWithToast($type, $msg),
            'delete' => $this->handleEventWithToast($type, $msg),
            default => null,
        };
    }

    protected function handleEventWithToast($type, $msg)
    {
        if ($type !== 'delete') {
            $this->toast(type: 'success', title: "{$msg}", icon: 'o-check', css: 'alert-success');
            $this->loadDisciplines();
            return;
        }
        $this->error($msg, timeout: 4000, position: 'toast-top toast-end');
        return;
    }

    public function resetSearch()
    {
        $this->search = '';
        $this->disciplines = $this->loadDisciplines();
    }
    public function placeholder()
    {
        return Blade::render('<div class="flex flex-col items-center justify-center gap-3 p-5 text-sm font-normal text-center text-gray-600 rounded">
                <x-mary-loading />
                <span class="text-xs tracking-wider text-gray-800">Carregando</span>
            </div>');
    }
}; ?>
<div class="flex flex-col gap-8" id="content-intial">
    <livewire:pages.teacher.discipline.modals.create :key="'create-modal-' . Str::random(5)" />
    <div class="flex items-center justify-between">
        @if ($disciplines->count() > 0)
            <div id="discipline-input-search">
                <x-mary-input placeholder="Pesquisar disciplina" wire:model.live="search" class="!outline-none" />
            </div>
        @else
            <span></span>
        @endif
        <button class="btn btn-sm bg-pink-700 text-gray-50 discipline-new flex gap-1.5 items-center"
            id="discipline-create" x-on:click="$dispatch('create-content-modal',{open:true})"
            mary:key="create-button-{{ Str::uuid7() }}">
            <x-mary-icon name="o-plus" class="w-4 h-4" /> Disciplina
        </button>
    </div>
    @if ($disciplines->count() > 0)
        <div class="relative grid w-full grid-cols-5 gap-3 pb-5 mt-8" id="disciplines-all">
            @foreach ($disciplines as $discipline)
                <div class="border-[1px] border-gray-200 shadow-sm  rounded-sm flex flex-col discipline-card"
                    mary:key="discipline-card-{{ $discipline->id }}" id="discipline-{{ $discipline->id }}"
                    data-tour="{{ $discipline->id }}">
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
                        <a class="text-sm font-medium text-gray-600 hover:text-blue-00 content-title"
                            id="discipline-manager-content-{{ $discipline->id }}" wire:navigate
                            href="{{ route('teacher.discipline.show', $discipline) }}">{{ $discipline->title }}</a>
                    </div>
                </div>
                <livewire:pages.teacher.discipline.modals.details :key="'details-' . $discipline->id" :discipline="$discipline" />
                <livewire:pages.teacher.discipline.modals.edit :key="'edit-' . $discipline->id" :discipline="$discipline" />
                <livewire:pages.teacher.discipline.modals.delete :key="'delete-' . $discipline->id" :id="$discipline->id"
                    :title="$discipline->title" />
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center w-full gap-6 p-6 text-center" id="disciplines-empty">
            @if (!empty($search) && count($disciplines) === 0)
                <p class="text-sm font-medium tracking-wider text-gray-600">Nenhuma disciplina encontrada para
                    "{{ $search }}"</p>
                <button class="btn btn-sm btn-ghost" type="button" wire:click="resetSearch">voltar a lista</button>
            @else
                <p class="text-sm font-medium tracking-wider text-gray-600">Não á disciplina cadastrada.</p>
            @endif
        </div>
    @endif
    <div class="fixed inset-0 z-40 bg-gray-900/50" x-data x-show="$store.tourguide.openMai">
        <!-- Centralizando a div -->
        <div
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-4xl sm:w-2/3 bg-white rounded-lg shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">
                <div class="flex items-center justify-between">
                  <h2 class="text-sm font-medium tracking-wider text-gray-700">Você tem as seguintes opções</h2>
                    <x-mary-button 
                      icon="o-x-mark"
                      class="bg-white border-none btn-square hover:none hover:bg-pink-600 hover:text-gray-100"
                      x-on:click="$store.tourguide.cancelOptionsTourPage()" />
                </div>
            <div class="flex items-center gap-3 p-4 bg-gray-100">
                <x-mary-button
                    class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                    label="Criar disciplina" x-on:click="$store.tourguide.start('StartTourCreateDiscipline')" />
                @if ($disciplines->count() > 0)
                    <x-mary-button
                        class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                        label="Pesquisar disciplina" x-on:click="$store.tourguide.start('StartTourSearchDiscipline')" />
                    <x-mary-button
                        class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                        label="Gerenciar conteudo"
                        @click="$store.tourguide.start('StartTourManagerDisciplineContent')" />
                    <x-mary-button
                        class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                        label="Editar: Titulo" @click="$store.tourguide.start('StartTourEditDiscipline')" />
                    <x-mary-button
                        class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                        label="Deletar disciplina" @click="$store.tourguide.start('StartTourDeleteDiscipline')" />
                @endif
            </div>
        </div>
        <img src="{{ asset('images/ai-sos.png') }}" class="fixed right-0 w-64 bottom-4 sm:w-48">
    </div>
</div>
