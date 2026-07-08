<?php
use Livewire\Component;
use Livewire\Attributes\{Layout, On, Computed};
use Livewire\WithPagination;
use App\Models\Teacher\Discipline;
use Mary\Traits\Toast;

new #[Layout('layouts::teacher')] class extends Component {
    use Toast, WithPagination;

    public string $search = '';
    public string $sortBy = 'recent'; // recent, oldest, name
    public int $perPage = 12;


    #[Computed]
    public function disciplines()
    {
        $teacherId = auth()->guard('teacher')->id();

        $query = Discipline::where('teacher_id', $teacherId);

        // Busca
        if ($this->search) {
            $query->where('title', 'like', '%' . $this->search . '%');
        }

        // Ordenação
        match($this->sortBy) {
            'recent' => $query->orderBy('created_at', 'desc'),
            'oldest' => $query->orderBy('created_at', 'asc'),
            'name' => $query->orderBy('title', 'asc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        return $query->paginate($this->perPage);
    }

    #[Computed]
    public function stats()
    {
        $teacherId = auth()->guard('teacher')->id();

        $totalDisciplines = Discipline::where('teacher_id', $teacherId)->count();

        return [
            'disciplines' => $totalDisciplines,
        ];
    }

    #[Computed]
    public function lessonCount()
    {

        return Discipline::
        withCount('lessons')
        ->where('teacher_id', $this->getTeacherGuard()->id())
        ->get();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'sortBy']);
        $this->resetPage();
    }

    public function createDiscipline(): void
    {
        $this->dispatch('create-content-modal', open: true);
    }

    public function editDiscipline($disciplineId): void
    {
        $this->dispatch('edit-content-modal', id: $disciplineId);
    }

    public function deleteDiscipline($disciplineId): void
    {
        $this->dispatch('delete-module-modal', id: $disciplineId);
    }

    public function detailsDiscipline($disciplineId): void
    {
        $this->dispatch('details-content-modal', id: $disciplineId);
    }

    #[On('discipline-event')]
    public function refresh($type, $msg): void
    {
        match($type) {
            'create' => $this->success($msg),
            'update' => $this->success($msg),
            'delete' => $this->error($msg),
            default => null,
        };
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSortBy(): void
    {
        $this->resetPage();
    }

    private function getTeacherGuard()
    {
        return auth()->guard('teacher');
    }
}; ?>

<div>
    <livewire:global.breadcrumb :items="[
        ['label' => 'Disciplinas', 'icon' => 'o-home'],
    ]" color="gray" />

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Minhas Disciplinas</h1>
                <p class="text-gray-600 mt-1">Gerencie suas disciplinas e conteúdos</p>
            </div>

            <button
                id="discipline-create"
                wire:click="createDiscipline"
                class="btn bg-blue-400 text-white border-0 hover:bg-blue-600 shadow-sm"
            >
                <x-mary-icon name="o-plus-circle" class="w-5 h-5" />
                Nova Disciplina
            </button>
        </div>
    </div>

    {{-- Card de Estatísticas --}}
    <div class="mb-6">
        <div class="bg-blue-400 rounded-sm p-6 text-white shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Total de Disciplinas</p>
                    <p class="text-3xl font-bold mt-2">{{ $this->stats['disciplines'] }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <x-mary-icon name="o-academic-cap" class="w-8 h-8 text-blue-500" />
                </div>
            </div>
        </div>
    </div>

    {{-- Filtros e Controles --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col lg:flex-row gap-4">
            {{-- Busca --}}
            <div class="flex-1">
                <div class="relative">
                    <x-mary-icon name="o-magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                    <input
                        id="discipline-input-search"
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Buscar disciplina..."
                        class="input input-bordered w-full pl-10 !outline-none"
                    />
                    @if($search)
                        <button
                            wire:click="$set('search', '')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                        >
                            <x-mary-icon name="o-x-mark" class="w-5 h-5" />
                        </button>
                    @endif
                </div>
            </div>

            {{-- Ordenação --}}
            <div class="w-full lg:w-52">
                <select
                    wire:model.live="sortBy"
                    class="select select-bordered w-full !outline-none"
                >
                    <option value="recent">Mais Recentes</option>
                    <option value="oldest">Mais Antigas</option>
                    <option value="name">Nome A-Z</option>
                </select>
            </div>

            {{-- Limpar Filtros --}}
            @if($search || $sortBy !== 'recent')
                <button
                    wire:click="clearFilters"
                    class="btn btn-ghost"
                    title="Limpar filtros"
                >
                    <x-mary-icon name="o-x-circle" class="w-5 h-5" />
                    Limpar
                </button>
            @endif
        </div>

        {{-- Filtros Ativos --}}
        @if($search)
            <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-gray-200">
                <span class="text-sm text-gray-600">Filtros ativos:</span>

                <span class="badge badge-primary gap-2">
                    Busca: "{{ $search }}"
                    <button wire:click="$set('search', '')">
                        <x-mary-icon name="o-x-mark" class="w-3 h-3" />
                    </button>
                </span>
            </div>
        @endif
    </div>

    {{-- Lista de Disciplinas --}}
    <div id="discipline-card">
        @if($this->disciplines->count() > 0)
            <div id="disciplines-all" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($this->disciplines as $discipline)
                    <div
                        class="group bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-xl hover:border-blue-300 transition-all duration-200 overflow-hidden discipline-card"
                        wire:key="discipline-{{ $discipline->id }}"
                        id="discipline-{{ $discipline->id }}"
                        data-tour="{{ $discipline->id }}"
                    >
                        {{-- Header com Cor --}}
                        <div class="h-32 bg-blue-400 relative">
                            <div class="absolute inset-0 bg-blue-400 bg-opacity-10 group-hover:bg-opacity-0 transition-opacity"></div>

                            {{-- Menu de Ações --}}
                            <div class="absolute top-3 right-3 z-10 bg-blue-400">
                                <div class="dropdown dropdown-end">
                                    <button
                                        id="tour-operations-{{ $discipline->id }}"
                                        tabindex="0"
                                        class="btn btn-sm btn-circle bg-blue-400 hover:bg-white hover:text-gray-400 border-0 shadow-sm"
                                    >
                                        <x-mary-icon name="o-ellipsis-vertical" class="w-5 h-5 text-white" />
                                    </button>
                                    <ul tabindex="0" class="dropdown-content menu p-2 shadow-xl bg-white rounded-box w-52 border border-gray-200 mt-2">
                                        <li>
                                            <button wire:click="detailsDiscipline('{{ $discipline->id }}')">
                                                <x-mary-icon name="o-document-magnifying-glass" class="w-4 h-4" />
                                                Ver Detalhes
                                            </button>
                                        </li>
                                        <li>
                                            <button wire:click="editDiscipline('{{ $discipline->id }}')"
                                                class="tour-discipline-edit-{{ $discipline->id }}">
                                                <x-mary-icon name="o-pencil-square" class="w-4 h-4" />
                                                Editar
                                            </button>
                                        </li>
                                        <li>
                                            <button
                                                wire:click="deleteDiscipline('{{ $discipline->id }}')"
                                                class="text-red-600 hover:bg-red-50 tour-discipline-delete-{{ $discipline->id }}"
                                            >
                                                <x-mary-icon name="o-trash" class="w-4 h-4" />
                                                Excluir
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            {{-- Ícone Central --}}
                            <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2">
                                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-lg ring-4 ring-white">
                                    <x-mary-icon name="o-academic-cap" class="w-8 h-8 text-blue-600" />
                                </div>
                            </div>
                        </div>

                        {{-- Conteúdo --}}
                        <div class="pt-10 px-5 pb-5">
                            {{-- Título --}}
                            <h3 class="text-center text-lg font-bold text-gray-900 mb-2 line-clamp-2 min-h-[3.5rem]">
                                {{ $discipline->title }}
                            </h3>

                            {{-- Info --}}
                            <div class="flex items-center justify-center gap-2 text-xs text-gray-500 mb-4">
                                <x-mary-icon name="o-calendar" class="w-3 h-3" />
                                <span id="card-discipline-count-module-{{ $discipline->id }}">
                                    Aulas: {{ $discipline->modules->count() }}
                                </span>
                                <span>Criada {{ $discipline->created_at->diffForHumans() }}</span>
                            </div>

                            {{-- Botão de Acesso --}}
                            <a
                                id="discipline-manager-content-{{ $discipline->id }}"
                                href="{{ route('teacher.discipline.show', $discipline) }}"
                                wire:navigate
                                class="btn  btn-block btn-sm bg-blue-400 text-gray-50"
                            >
                                <x-mary-icon name="o-arrow-right-circle" class="w-4 h-4" />
                                Gerenciar Conteúdo
                            </a>
                        </div>
                    </div>

                    {{-- Modais --}}
                    <livewire:teacher.discipline.modals.modal-details-discipline
                        :discipline="$discipline"
                        :wire:key="'details-' . $discipline->id"
                    />
                    <livewire:teacher.discipline.modals.modal-edit-discipline
                        :discipline="$discipline"
                        :wire:key="'edit-' . $discipline->id"
                    />
                    <livewire:teacher.discipline.modals.modal-delete-discipline
                        :id="$discipline->id"
                        :title="$discipline->title"
                        :wire:key="'delete-' . $discipline->id"
                    />
                @endforeach

            </div>

            {{-- Paginação --}}
            @if($this->disciplines->hasPages())
                <div class="mt-8">
                    {{ $this->disciplines->links() }}
                </div>
            @endif
        @else
            {{-- Estado Vazio --}}
            <div id="disciplines-empty" class="bg-white rounded-xl shadow-sm border border-gray-200 p-16 text-center">
                <div class="w-24 h-24 mx-auto bg-gradient-to-br from-blue-100 to-purple-100 rounded-full flex items-center justify-center mb-4">
                    <x-mary-icon name="o-academic-cap" class="w-12 h-12 text-blue-600" />
                </div>

                @if($search)
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma disciplina encontrada</h3>
                    <p class="text-gray-500 mb-6">
                        Não encontramos disciplinas com o termo "{{ $search }}".
                    </p>
                    <button
                        wire:click="clearFilters"
                        class="btn btn-primary"
                    >
                        <x-mary-icon name="o-arrow-path" class="w-5 h-5" />
                        Limpar Busca
                    </button>
                @else
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma disciplina cadastrada</h3>
                    <p class="text-gray-500 mb-6">
                        Você ainda não possui disciplinas cadastradas. Crie sua primeira disciplina!
                    </p>
                    <button
                        wire:click="createDiscipline"
                        class="btn bg-blue-400 hover:bg-blue-500 text-white border-0"
                    >
                        <x-mary-icon name="o-plus-circle" class="w-5 h-5" />
                        Criar Primeira Disciplina
                    </button>
                @endif
            </div>
        @endif
    </div>

     {{-- Tour Modal --}}
    <div class="fixed inset-0 z-40 bg-gray-900/50" x-data x-show="$store.tourguide.openMai">
        <div
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2
            -translate-y-1/2 w-full max-w-4xl sm:w-2/3 bg-white rounded-lg
            shadow-lg border-[1px] border-blue-200 p-6 flex flex-col gap-5">
           <div class="flex justify-between items-center">
               <h2 class="text-lg font-normal text-gray-700">Opção das disciplinas</h2>
                <div class="flex justify-end">
                    <x-mary-button icon="o-x-mark"
                        class="bg-white border-none btn-square hover:none hover:bg-blue-400 hover:text-gray-100"
                        x-on:click="$store.tourguide.cancelOptionsTourPage()" />
                </div>
            </div>

            <div class="grid grid-cols-4 gap-2">
                <x-mary-button
                class="font-medium text-blue-600 border-[1px] border-blue-600 btn hover:bg-blue-600 hover:text-gray-100"
                label="Criar disciplina"
                x-on:click="$store.tourguide.start('StartTourCreateDiscipline')" />
                @if(count($this->disciplines) > 0)

                <x-mary-button
                    class="font-medium text-blue-600 border-[1px] border-blue-600 btn hover:bg-blue-600 hover:text-blue-100"
                    label="Editar  disciplina"
                    x-on:click="$store.tourguide.start('StartTourEditDiscipline')" />

                 {{-- <x-mary-button
                    class="font-medium text-blue-600 border-[1px] border-blue-600 btn hover:bg-blue-600 hover:text-gray-100"
                    label="Filtrar disciplina"
                    x-on:click="$store.tourguide.start('StartTourSearchDiscipline')" /> --}}
                 <x-mary-button
                    class="font-medium text-blue-600 border-[1px] border-blue-600 btn hover:bg-blue-600 hover:text-gray-100"
                    label="Gerenciar conteúdo"
                    x-on:click="$store.tourguide.start('StartTourManagerDisciplineContent')" />
                <x-mary-button
                    class="font-medium text-blue-600 border-[1px] border-blue-600 btn hover:bg-blue-600 hover:text-gray-100"
                    label="Deletar disciplina"
                    x-on:click="$store.tourguide.start('StartTourDeleteDiscipline')" />
                    @endif
                </div>
        </div>
        <img src="{{ asset('images/rb-aiava.png') }}" class="fixed right-0 w-64 bottom-4 sm:w-48" />
    </div>

    {{-- Modal de Criação --}}
    <livewire:teacher.discipline.modals.modal-new-discipline />
</div>
