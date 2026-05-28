<?php

use Livewire\Component;
use Livewire\Attributes\{Layout, On, Computed};
use Livewire\WithPagination;
use App\Models\Teacher\{Classe, Student};
use Mary\Traits\Toast;

new #[Layout(name: 'layouts::teacher')] class extends Component {
    use Toast, WithPagination;

    public Classe $classe;
    public string $search = '';
    public string $statusFilter = ''; // active, inactive, all
    public string $sortBy = 'name'; // name, email, recent, status
    public int $perPage = 10;

    public function mount(Classe $classe): void
    {
        $this->classe = $classe->load(['discipline', 'students']);
    }

    #[Computed]
    public function students()
    {
        $query = $this->classe->students();

        // Busca
        if ($this->search) {
            $query->where(function($q) {
                $q->where('full_name', 'like', '%' . $this->search . '%')
                  //->orWhere('last_name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        // Filtro de status
        if ($this->statusFilter === 'active') {
            $query->where('status', 'active');
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('status', 'inactive');
        }

         // 🔁 Ordenação: primeiro alfabética crescente (A→Z), depois o filtro do usuário
        $query->orderBy('full_name', 'asc');
        // Ordenação
        match($this->sortBy) {
            'full_name' => $query->orderBy('full_name', 'asc'),
            'email' => $query->orderBy('email', 'asc'),
            'recent' => $query->orderBy('created_at', 'desc'),
            'status' => $query->orderBy('status', 'asc'),
            default => $query->orderBy('full_name', 'asc'),
        };

        return $query->paginate($this->perPage);
    }

    #[Computed]
    public function stats()
    {
        $totalStudents = $this->classe->students()->count();
        $activeStudents = $this->classe->students()->where('status', 'active')->count();
        $inactiveStudents = $this->classe->students()->where('status', 'inactive')->count();

        return [
            'total' => $totalStudents,
            'active' => $activeStudents,
            'inactive' => $inactiveStudents,
        ];
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'sortBy']);
        $this->resetPage();
    }

    public function addStudent(): void
    {
        $this->dispatch('teacher::new-student',
            classe_id: $this->classe->id,
            discipline_id: $this->classe->discipline_id
        );
    }

    public function editStudent($studentId): void
    {
        $this->dispatch('teacher::edit-student-form', id: $studentId);
    }

    public function removeStudent($studentId): void
    {
        $this->dispatch('teacher::remove-student', id: $studentId);
    }

    public function toggleStatus($studentId): void
    {
        $student = Student::find($studentId);
        if ($student) {
            $student->update([
                'status' => $student->status === 'active' ? 'inactive' : 'active'
            ]);
            $this->success('Status do aluno atualizado!');
        }
    }

    #[On('student-event')]
    public function refresh($type, $message): void
    {
        match($type) {
            'update' => $this->success($message),
            'created' => $this->success($message),
            'add' => $this->success($message),
            'edit' => $this->success($message),
            'remove' => $this->error($message),
            default => null,
        };

        $this->classe->refresh();
    }


    public function update(){}

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
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
            <a href="{{ route('teacher.classes') }}" wire:navigate class="text-gray-500 hover:text-blue-700">
                Turmas
            </a>
            <x-mary-icon name="o-chevron-right" class="w-3 h-3 text-gray-400" />
            <span class="text-blue-700 font-medium">{{ $classe->name }}</span>
        </div>
    </div>

    {{-- Header da Turma --}}
    <div class="bg-blue-400 rounded-xl shadow-sm p-8 mb-6 text-white">
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-4">
                {{-- Ícone --}}
                <div class="w-16 h-16 bg-white bg-opacity-20 backdrop-blur-sm rounded-xl flex items-center justify-center flex-shrink-0">
                    <x-mary-icon name="o-user-group" class="w-8 h-8 text-blue-400" />
                </div>

                {{-- Info --}}
                <div>
                    <h1 class="text-3xl font-bold mb-2">{{ $classe->name }}</h1>
                    <div class="flex flex-wrap items-center gap-4 text-blue-100">
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-academic-cap" class="w-4 h-4" />
                            <span>{{ $classe->discipline->title }}</span>
                        </div>
                        <span>•</span>
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-calendar" class="w-4 h-4" />
                            <span>Castrada em {{ $classe->created_at->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Botão Adicionar --}}
            <button
                @click="$wire.addStudent"
                id="student-register"
                class="btn bg-white text-blue-400 hover:bg-blue-50 border-0 shadow-lg"
            >
                <x-mary-icon name="o-plus-circle" class="w-5 h-5" />
                Adicionar Aluno
            </button>
        </div>
    </div>

    {{-- Cards de Estatísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- Total de Alunos --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total de Alunos</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $this->stats['total'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-users" class="w-6 h-6 text-blue-600" />
                </div>
            </div>
        </div>

        {{-- Alunos Ativos --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Alunos Ativos</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">{{ $this->stats['active'] }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-check-circle" class="w-6 h-6 text-green-600" />
                </div>
            </div>
        </div>

        {{-- Alunos Inativos --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Alunos Inativos</p>
                    <p class="text-3xl font-bold text-gray-400 mt-2">{{ $this->stats['inactive'] }}</p>
                </div>
                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                    <x-mary-icon name="o-x-circle" class="w-6 h-6 text-gray-400" />
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
                        id="student-filter"
                        placeholder="Buscar por nome ou email..."
                        class="input input-bordered w-full pl-10 !outline-none focus:border-gray-200 focus:ring-gray-200"
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

            {{-- Filtro de Status --}}
            <div class="w-full lg:w-48">
                <select
                    wire:model.live="statusFilter"
                    class="select select-bordered w-full !outline-none focus:border-gray-200 focus:ring-gray-200"
                >
                    <option value="">Todos os Status</option>
                    <option value="active">Ativos</option>
                    <option value="inactive">Inativos</option>
                </select>
            </div>

            {{-- Ordenação --}}
            <div class="w-full lg:w-52">
                <select
                    wire:model.live="sortBy"
                    class="select select-bordered w-full !outline-none focus:border-gray-200 focus:ring-gray-200"
                >
                    <option value="name">Nome A-Z</option>
                    <option value="email">Email A-Z</option>
                    <option value="recent">Mais Recentes</option>
                    <option value="status">Status</option>
                </select>
            </div>

            {{-- Limpar Filtros --}}
            @if($search || $statusFilter || $sortBy !== 'name')
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
        @if($search || $statusFilter)
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

                @if($statusFilter)
                    <span class="badge badge-secondary gap-2">
                        Status: {{ $statusFilter === 'active' ? 'Ativos' : 'Inativos' }}
                        <button wire:click="$set('statusFilter', '')">
                            <x-mary-icon name="o-x-mark" class="w-3 h-3" />
                        </button>
                    </span>
                @endif
            </div>
        @endif
    </div>

    {{-- Lista de Alunos --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @if($this->students->count() > 0)
            {{-- Grid de Alunos --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-6" id="student-list">
                @foreach ($this->students as $student)
                    <div
                        class="group bg-white border-[1px] border-gray-100 rounded-xl p-5 hover:shadow-sm hover:border-blue-100 transition-all duration-200 card-student"
                        wire:key="student-{{ $student->id }}"
                        id="student-{{ $student->id }}"
                        data-tour="{{ $student->id }}"
                    >
                        {{-- Header do Card --}}
                        <div class="flex items-start justify-between mb-4">
                            {{-- Avatar e Nome --}}
                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                {{-- Avatar --}}
                                <div class="relative flex-shrink-0">
                                    @if($student->profile?->avatar)
                                        <img
                                            src="{{ asset('storage/' . $student->profile->avatar) }}"
                                            alt="{{ $student->first_name }}"
                                            class="w-12 h-12 rounded-full object-cover border-2 border-gray-200 group-hover:border-blue-100 transition-colors"
                                        />
                                    @else
                                        <div class="w-12 h-12 bg-blue-300 rounded-full flex items-center justify-center text-white font-bold text-sm border-2 border-gray-200 group-hover:border-blue-100 transition-colors">
                                            {{ substr($student->firstName(), 0, 1) }}{{ substr($student->lastName(), 0, 1) }}
                                        </div>
                                    @endif
                                </div>

                                {{-- Nome --}}
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-bold text-gray-900 truncate">
                                        {{ $student->full_name }}
                                    </h3>
                                    <p class="text-xs text-gray-500 truncate">
                                        {{ $student->email }}
                                    </p>
                                </div>
                            </div>

                            {{-- Status Badge (canto) --}}
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $student->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ $student->status === 'active' ? 'Ativo' : 'Inativo' }}
                            </span>
                        </div>

                        {{-- Info Adicional --}}
                        <div class="space-y-2 mb-4 text-sm">
                            @if($student->phone)
                                <div class="flex items-center gap-2 text-gray-600">
                                    <x-mary-icon name="o-phone" class="w-4 h-4" />
                                    <span class="truncate">{{ $student->phone }}</span>
                                </div>
                            @endif

                            <div class="flex items-center gap-2 text-gray-600">
                                <x-mary-icon name="o-calendar" class="w-4 h-4" />
                                <span>Desde {{ $student->created_at->format('d/m/Y') }}</span>
                            </div>
                        </div>

                        {{-- Ações --}}
                        <div class="flex gap-2 pt-4 border-t border-gray-200">
                            {{-- Toggle Status --}}
                            <button
                                wire:click="toggleStatus({{ $student->id }})"
                                class="btn btn-sm flex-1 {{ $student->status === 'active' ? 'btn-ghost' : 'bg-green-600 text-white hover:bg-green-700' }}"
                                title="{{ $student->status === 'active' ? 'Desativar' : 'Ativar' }}"
                            >
                                <x-mary-icon name="{{ $student->status === 'active' ? 'o-x-circle' : 'o-check-circle' }}" class="w-4 h-4" />
                                {{ $student->status === 'active' ? 'Desativar' : 'Ativar' }}
                            </button>

                            {{-- Editar --}}
                            <button
                                wire:click="editStudent('{{ $student->id }}')"
                                id="student-btn-edit-{{ $student->id }}"
                                class="btn btn-sm btn-ghost"
                                title="Editar"
                            >
                                <x-mary-icon name="o-pencil-square" class="w-4 h-4" />
                            </button>

                            {{-- Remover --}}
                            <button
                                wire:click="removeStudent('{{ $student->id }}')"
                                id="student-btn-delete-{{ $student->id }}"
                                class="btn btn-sm btn-ghost text-red-600 hover:bg-red-50"
                                title="Remover da turma"
                            >
                                <x-mary-icon name="o-trash" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    {{-- Modal de Edição --}}
                    <livewire:teacher.classe.edit-student-modal :student="$student" :key="'edit-student-'.$student->id"/>
                    <livewire:teacher.classe.delete-student-modal :student="$student" :key="'remove-student-'.$student->id"/>
                @endforeach
            </div>

            {{-- Paginação --}}
            @if($this->students->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $this->students->links() }}
                </div>
            @endif
        @else
            {{-- Estado Vazio --}}
            <div class="text-center py-16">
                <div class="w-24 h-24 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <x-mary-icon name="o-user-group" class="w-12 h-12 text-gray-400" />
                </div>

                @if($search || $statusFilter)
                    <h3 class="text-lg font-semibold text-gray-900 mb-2" id="student-no-result">Nenhum aluno encontrado</h3>
                    <p class="text-gray-500 mb-6">
                        Não encontramos alunos com os filtros aplicados.
                    </p>
                    <button
                        wire:click="clearFilters"
                        class="btn btn-primary"
                    >
                        <x-mary-icon name="o-arrow-path" class="w-5 h-5" />
                        Limpar Filtros
                    </button>
                @else
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhum aluno cadastrado</h3>
                    <p class="text-gray-500 mb-6">
                        Esta turma ainda não possui alunos. Adicione o primeiro aluno!
                    </p>
                    <button
                        @click="$wire.addStudent"
                        class="btn btn-primary"
                    >
                        <x-mary-icon name="o-plus-circle" class="w-5 h-5" />
                        Adicionar Primeiro Aluno
                    </button>
                @endif
            </div>
        @endif
    </div>

    {{-- Modais --}}
    <livewire:teacher.classe.new-student-modal />
    {{-- <livewire:teacher.classe.edit-student-modal /> --}}
    {{-- <livewire:teacher.classe.remove-student-modal /> --}}

    <div x-data class="fixed inset-0 z-50 bg-gray-900/50" x-show="$store.tourguide.openMai">
        <div
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-full sm:w-1/2 bg-white rounded-lg shadow-lg border-[1px] border-d-200 p-6 flex flex-col gap-5">
            <div class="flex items-center justify-between">
                <h2 class="text-[14px] tracking-wide font-medium text-gray-700">
                    Aqui você pode gerenciar os alunos da turma
                </h2>
                <x-mary-button
                    class="btn btn-sm btn-ghost"
                    x-on:click="$store.tourguide.cancelOptionsTourPage()">
                    <x-mary-icon name="o-x-mark" class="w-4 text-gray-700"/>
                </x-mary-button>
            </div>
            <div class="grid grid-cols-4 gap-3 p-4 bg-gray-100">
                <x-mary-button
                    class="text-blue-600 border-[1px] border-blue-600 btn hover:bg-blue-500 hover:text-gray-100"
                    label="Registrar aluno"
                    x-on:click="$store.tourguide.start('StartTourStudentRegister')" />
                <x-mary-button
                    class="font-medium text-blue-600 border-[1px] border-blue-600 btn hover:bg-blue-500 hover:text-gray-100"
                    label="Filtrar alunos"
                    x-on:click="$store.tourguide.start('StartTourStudentFilter')" />
                <x-mary-button
                    class="font-medium text-blue-500 border-[1px] border-blue-500 btn hover:bg-blue-500 hover:text-gray-100"
                    label="Editar aluno"
                    x-on:click="$store.tourguide.start('StartTourStudentEdit')" />
                <x-mary-button
                    class="font-medium text-blue-500 border-[1px] border-blue-500 btn hover:bg-blue-500 hover:text-gray-100"
                    label="Remover aluno"
                    x-on:click="$store.tourguide.start('StartTourStudentDelete')" />
            </div>
        </div>
        <img src="{{ asset('images/rb-aiava.png') }}" class="fixed right-0 w-64 bottom-4 sm:w-48">
    </div>
</div>
