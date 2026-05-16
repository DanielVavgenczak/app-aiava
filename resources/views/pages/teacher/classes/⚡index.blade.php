<?php

use Livewire\Component;
use Livewire\Attributes\{Layout, On, Computed};
use App\Models\Teacher\{Classe, Discipline};
use Mary\Traits\Toast;
use Livewire\WithPagination;

new #[Layout(name: 'layouts::teacher')] class extends Component {
    use Toast, WithPagination;

    public string $search = '';
    public string $disciplineFilter = '';
    public string $sortBy = 'recent'; // recent, oldest, name, students
    public int $perPage = 10;

    public function mount(): void
    {
        // Carregamento inicial
    }

    #[Computed]
    public function disciplines()
    {
        return Discipline::where('teacher_id', auth()->guard('teacher')->id())
            ->orderBy('title')
            ->get();
    }

    #[Computed]
    public function classes()
    {
        $query = Classe::with(['students', 'discipline'])
            ->where('teacher_id', auth()->guard('teacher')->id());

        // Filtro por disciplina
        if ($this->disciplineFilter) {
            $query->where('discipline_id', $this->disciplineFilter);
        }

        // Busca por nome
        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        // Ordenação
        match($this->sortBy) {
            'recent' => $query->orderBy('created_at', 'desc'),
            'oldest' => $query->orderBy('created_at', 'asc'),
            'name' => $query->orderBy('name', 'asc'),
            'students' => $query->withCount('students')->orderBy('students_count', 'desc'),
            default => $query->latest(),
        };

        return $query->paginate($this->perPage);
    }

    #[Computed]
    public function stats()
    {
        $teacherId = auth()->guard('teacher')->id();

        $totalClasses = Classe::where('teacher_id', $teacherId)->count();
        $totalStudents = Classe::where('teacher_id', $teacherId)
            ->withCount('students')
            ->get()
            ->sum('students_count');
        $totalDisciplines = Discipline::where('teacher_id', $teacherId)->count();

        return [
            'classes' => $totalClasses,
            'students' => $totalStudents,
            'disciplines' => $totalDisciplines,
        ];
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'disciplineFilter', 'sortBy']);
        $this->resetPage();
    }

    public function createClasse(): void
    {
        $this->js('console.log("Criando nova classe...")');
        $this->dispatch('teacher::create-classe', disciplineId: $this->disciplineFilter);
    }



    public function deleteClasse($classeId): void
    {
        $this->dispatch('delete-classe-modal', id: $classeId);
    }

    #[On('action-event-classe')]
    public function refresh($type): void
    {
        match($type) {
            'delete' => $this->handleToast('error', 'Turma excluída com sucesso!'),
            'create' => $this->handleToast('success', 'Turma criada com sucesso!'),
            'update' => $this->handleToast('success', 'Turma atualizada com sucesso!'),
            default => null,
        };
    }

    protected function handleToast(string $type, string $message): void
    {
        $type === 'error' ? $this->error($message) : $this->success($message);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDisciplineFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSortBy(): void
    {
        $this->resetPage();
    }
}; ?>

<div>
    {{-- Breadcrumb --}}
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('teacher.discipline') }}" wire:navigate class="text-gray-500 hover:text-blue-700 transition-colors">
                <x-mary-icon name="o-academic-cap" class="w-4 h-4" />
            </a>
            <x-mary-icon name="o-chevron-right" class="w-3 h-3 text-gray-400" />
            <span class="text-blue-700 font-medium">Turmas</span>
        </div>
    </div>

    {{-- Header com Título e Ação --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Minhas Turmas</h1>
                <p class="text-gray-600 mt-1">Gerencie suas turmas e alunos</p>
            </div>

            @if($this->disciplines->count() > 0)
                <button
                    @click="$wire.createClasse"
                    class="btn bg-gradient-to-r from-blue-600 to-blue-700 text-white border-0 hover:from-blue-700 hover:to-blue-500 shadow-sm"
                >
                    <x-mary-icon name="o-plus-circle" class="w-5 h-5" />
                    Nova Turma
                </button>
            @endif
        </div>
    </div>

    {{-- Cards de Estatísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- Total de Turmas --}}
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Total de Turmas</p>
                    <p class="text-3xl font-bold mt-2">{{ $this->stats['classes'] }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <x-mary-icon name="o-academic-cap" class="w-8 h-8 text-blue-900  " />
                </div>
            </div>
        </div>

        {{-- Total de Alunos --}}
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Total de Alunos</p>
                    <p class="text-3xl font-bold mt-2">{{ $this->stats['students'] }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <x-mary-icon name="o-users" class="w-8 h-8 text-blue-900" />
                </div>
            </div>
        </div>

        {{-- Disciplinas Ativas --}}
        <div class="bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl p-6 text-white shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-pink-100 text-sm font-medium">Disciplinas Ativas</p>
                    <p class="text-3xl font-bold mt-2">{{ $this->stats['disciplines'] }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <x-mary-icon name="o-book-open" class="w-8 h-8 text-blue-900" />
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
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Buscar turma..."
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

            {{-- Filtro de Disciplina --}}
            <div class="w-full lg:w-64">
                <select
                    wire:model.live="disciplineFilter"
                    class="select select-bordered w-full !outline-none"
                >
                    <option value="">Todas as Disciplinas</option>
                    @foreach($this->disciplines as $discipline)
                        <option value="{{ $discipline->id }}">{{ $discipline->title }}</option>
                    @endforeach
                </select>
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
                    <option value="students">Mais Alunos</option>
                </select>
            </div>

            {{-- Limpar Filtros --}}
            @if($search || $disciplineFilter || $sortBy !== 'recent')
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
        @if($search || $disciplineFilter)
            <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-gray-200">
                <span class="text-sm text-gray-600">Filtros ativos:</span>

                @if($search)
                    <span class="badge badge-primary gap-2">
                        Busca: "{{ $search }}"
                        <button wire:click="$set('search', '')">
                            <x-mary-icon name="o-x-mark" class="w-3 h-3" />
                        </button>
                    </span>
                @endif

                @if($disciplineFilter)
                    @php
                        $selectedDiscipline = $this->disciplines->firstWhere('id', $disciplineFilter);
                    @endphp
                    <span class="badge badge-secondary gap-2">
                        Disciplina: {{ $selectedDiscipline?->title }}
                        <button wire:click="$set('disciplineFilter', '')">
                            <x-mary-icon name="o-x-mark" class="w-3 h-3" />
                        </button>
                    </span>
                @endif
            </div>
        @endif
    </div>

    {{-- Lista de Turmas --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @if($this->classes->count() > 0)
            {{-- Header da Tabela --}}
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <div class="grid grid-cols-12 gap-4 items-center">
                    <div class="col-span-4 lg:col-span-3">
                        <span class="text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Turma
                        </span>
                    </div>
                    <div class="col-span-3 lg:col-span-2 text-center">
                        <span class="text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Alunos
                        </span>
                    </div>
                    <div class="col-span-5 lg:col-span-4">
                        <span class="text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Disciplina
                        </span>
                    </div>
                    <div class="hidden lg:block lg:col-span-3 text-right">
                        <span class="text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Ações
                        </span>
                    </div>
                </div>
            </div>

            {{-- Lista --}}
            <div class="divide-y divide-gray-200">
                @foreach ($this->classes as $classe)
                    <div
                        class="group px-6 py-4 hover:bg-blue-50 transition-colors"
                        wire:key="classe-{{ $classe->id }}"
                    >
                        <div class="grid grid-cols-12 gap-4 items-center">
                            {{-- Nome da Turma --}}
                            <div class="col-span-4 lg:col-span-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <x-mary-icon name="o-user-group" class="w-5 h-5 text-white" />
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $classe->name }}</p>
                                        <p class="text-xs text-gray-500">Criada {{ $classe->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Alunos --}}
                            <div class="col-span-3 lg:col-span-2 flex justify-center">
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold">
                                    {{ $classe->students->count() }}
                                    {{ Str::plural('aluno', $classe->students->count()) }}
                                </span>
                            </div>

                            {{-- Disciplina --}}
                            <div class="col-span-5 lg:col-span-4">
                                <div class="flex items-center gap-2">
                                    <x-mary-icon name="o-academic-cap" class="w-4 h-4 text-gray-400" />
                                    <span class="text-gray-700 text-sm">{{ $classe->discipline->title }}</span>
                                </div>
                            </div>

                            {{-- Ações --}}
                            <div class="col-span-12 lg:col-span-3 flex justify-end gap-2 mt-3 lg:mt-0">
                                {{-- Acessar --}}
                                <a
                                    href="{{ route('teacher.classes.show', $classe) }}"
                                    wire:navigate
                                    class="btn btn-sm bg-blue-600 text-white hover:bg-blue-700 border-0"
                                    title="Acessar turma"
                                >
                                    <x-mary-icon name="o-eye" class="w-4 h-4" />
                                    <span class="hidden sm:inline">Acessar</span>
                                </a>

                                {{-- Editar --}}
                                <button
                                    x-on:click="$dispatch('edit-classe-modal',{id: '{{ $classe->id }}'})"
                                    class="btn btn-sm bg-green-200 text-gray-700 hover:bg-green-400 border-0"
                                    title="Editar turma"
                                >
                                    <x-mary-icon name="o-pencil-square" class="w-4 h-4" />
                                    <span class="hidden sm:inline">Editar</span>
                                </button>

                                {{-- Excluir --}}
                                <button
                                    wire:click="deleteClasse({{ $classe->id }})"
                                    class="btn btn-sm bg-red-600 text-white hover:bg-red-700 border-0"
                                    title="Excluir turma"
                                >
                                    <x-mary-icon name="o-trash" class="w-4 h-4" />
                                    <span class="hidden sm:inline">Excluir</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Modais --}}
                    <livewire:teacher.classe.edit-classe :classe="$classe" :wire:key="'edit-' . $classe->id" />
                    {{-- <livewire:pages::teacher.modal-delete-classe :classe="$classe" :wire:key="'delete-' . $classe->id" /> --}}
                @endforeach
            </div>

            {{-- Paginação --}}
            @if($this->classes->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $this->classes->links() }}
                </div>
            @endif
        @else
            {{-- Estado Vazio --}}
            <div class="text-center py-16">
                <div class="w-24 h-24 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <x-mary-icon name="o-user-group" class="w-12 h-12 text-gray-400" />
                </div>

                @if($search || $disciplineFilter)
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma turma encontrada</h3>
                    <p class="text-gray-500 mb-6">
                        Não encontramos turmas com os filtros aplicados.
                    </p>
                    <button
                        wire:click="clearFilters"
                        class="btn btn-primary"
                    >
                        <x-mary-icon name="o-arrow-path" class="w-5 h-5" />
                        Limpar Filtros
                    </button>
                @else
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma turma cadastrada</h3>
                    <p class="text-gray-500 mb-6">
                        Você ainda não possui turmas cadastradas. Crie sua primeira turma!
                    </p>

                    @if($this->disciplines->count() > 0)
                        <button
                            @click="$wire.createClasse"
                            class="btn btn-primary"
                        >
                            <x-mary-icon name="o-plus-circle" class="w-5 h-5" />
                            Criar Primeira Turma
                        </button>
                    @else
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 max-w-md mx-auto">
                            <div class="flex items-start gap-3">
                                <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" />
                                <div class="text-left">
                                    <p class="text-sm text-yellow-800 font-medium">
                                        Você precisa cadastrar disciplinas primeiro
                                    </p>
                                    <p class="text-xs text-yellow-700 mt-1">
                                        As turmas são vinculadas a disciplinas.
                                    </p>
                                </div>
                            </div>
                            <a
                                href="{{ route('teacher.discipline') }}"
                                wire:navigate
                                class="btn btn-sm btn-primary mt-4"
                            >
                                Ir para Disciplinas
                            </a>
                        </div>
                    @endif
                @endif
            </div>
        @endif
    </div>
    <livewire:teacher.classe.modal-new-classe />

</div>
