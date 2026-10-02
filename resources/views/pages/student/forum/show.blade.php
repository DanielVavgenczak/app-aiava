<?php

use Livewire\Component;
use Livewire\Attributes\{Layout, On, Url, Computed};
use Livewire\WithPagination;
use App\Models\Teacher\{Discipline};
use App\Models\Forum\ForumTopic;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Auth;

new #[Layout(name: 'layouts::student')] class extends Component {
    use Toast;
    use WithPagination;

    public string $selectedTab = 'all-tap';
    public string $disciplineID = '';
    public $discipline;
    public $classes;

    #[Url(as: 'tab')]
    public string $activeTab = 'all';

    public int $perPage = 5;

    // ✅ Estados para CRUD
    public bool $showDeleteModal = false;
    public $topicToDelete = null;

    public function mount(string $id): void
    {
        $this->disciplineID = $id;
        $this->loadDisciplineData();
    }

    public function loadDisciplineData(): void
    {
        $this->discipline = Discipline::withCount('ForumTopics')
            ->with('Classes')
            ->findOrFail($this->disciplineID);

        $this->classes = $this->discipline->Classes;
    }

    #[On('forum-event')]
    public function handleForumEvent($type, $msg): void
    {
        $this->loadDisciplineData();
        $this->resetPage();

        if ($type === 'delete') {
            $this->error($msg);
        } else {
            $this->success($msg);
        }

        if ($type === 'create') {
            $this->selectedTab = 'mine-tap';
        }
    }

    public function openNewTopicModal(): void
    {
        $this->dispatch('student::new-topic-forum',
            discipline_id: $this->disciplineID,
        );
    }

    // ✅ Editar tópico
    public function editTopic($topicId): void
    {
        $this->dispatch('open-edit-topic-modal', topicId: $topicId);
    }

    // ✅ Confirmar deleção
    public function confirmDelete($topicId): void
    {
        $this->topicToDelete = ForumTopic::find($topicId);
        $this->showDeleteModal = true;
    }

    // ✅ Deletar tópico (usa policy)
    public function deleteTopic(): void
    {
        if ($this->topicToDelete) {
            if ($this->getCurrentUser()?->can('delete', $this->topicToDelete)) {
                $this->topicToDelete->delete();
                $this->showDeleteModal = false;
                $this->topicToDelete = null;

                $this->loadDisciplineData();
                $this->resetPage();

                $this->success('Tópico excluído com sucesso!');
            } else {
                $this->error('Você não tem permissão para excluir este tópico.');
            }
        }
    }

    // ✅ Cancelar deleção
    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->topicToDelete = null;
    }

    private function getCurrentUser()
    {
        return Auth::guard('teacher')->user() ?? Auth::guard('student')->user();
    }

    private function getCurrentGuard(): string
    {
        return Auth::guard('teacher')->check() ? 'teacher' : 'student';
    }

    #[Computed]
    public function allTopics()
    {
        return ForumTopic::where('discipline_id', $this->disciplineID)
            ->with(['classe', 'owner', 'discipline'])
            ->orderBy('created_at', 'DESC')
            ->paginate($this->perPage, pageName: 'allPage');
    }

    #[Computed]
    public function myTopics()
    {
        $user = $this->getCurrentUser();
        $guard = $this->getCurrentGuard();

        if (!$user) {
            return collect();
        }

        $ownerType = $guard === 'teacher'
            ? '%Teacher%'
            : '%Student%';

        return ForumTopic::where('discipline_id', $this->disciplineID)
            ->where('owner_type', 'LIKE', $ownerType)
            ->where('owner_id', $user->id)
            ->with(['classe', 'owner'])
            ->orderBy('created_at', 'DESC')
            ->paginate($this->perPage, pageName: 'myPage');
    }

    #[Computed]
    public function allTopicsCount()
    {
        return ForumTopic::where('discipline_id', $this->disciplineID)->count();
    }

    #[Computed]
    public function myTopicsCount()
    {
        $user = $this->getCurrentUser();
        $guard = $this->getCurrentGuard();

        if (!$user) {
            return 0;
        }

        $ownerType = $guard === 'teacher'
            ? '%Teacher%'
            : '%Student%';

        return ForumTopic::where('discipline_id', $this->disciplineID)
            ->where('owner_type', 'LIKE', $ownerType)
            ->where('owner_id', $user->id)
            ->count();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }
}; ?>

<div>
    <livewire:global.breadcrumb :items="[
        ['label' => 'Disciplinas', 'route' => 'student.discipline', 'icon' => 'o-home'],
        ['label' => 'Fórum', 'route' => 'student.forum.index'],
        ['label' => $discipline->title],
    ]" color="pink" tour-id="forum-show-breadcrumb" />

    {{-- Conteúdo Principal --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        {{-- Header Melhorado --}}
        <div class="p-6 border-b border-gray-200" id="forum-show-header">
            <div class="flex items-center justify-between">
                <div class="flex items-start gap-4">
                    {{-- Info da Disciplina referenciando com o forum --}}
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $discipline->title }}</h1>
                        <div class="flex items-center gap-4 mt-2 text-sm text-gray-600">
                            <div class="flex items-center gap-1">
                                <x-mary-icon name="o-chat-bubble-left-right" class="w-4 h-4" />
                                <span>{{ $this->allTopicsCount }} tópicos</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <x-mary-icon name="o-user-group" class="w-4 h-4" />
                                <span>{{ $this->myTopicsCount }} meus tópicos</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Controles --}}
                <div class="flex items-center gap-3" id="forum-show-controls">
                    {{-- Seletor de itens --}}
                    <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-lg border border-gray-200">
                        <x-mary-icon name="o-view-columns" class="w-4 h-4 text-gray-500" />
                        <select
                            wire:model.live="perPage"
                            class="text-sm border-0 focus:ring-0 bg-transparent"
                        >
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                    </div>

                    {{-- Botão Novo Tópico --}}
                    <button
                        id="forum-show-new-topic"
                        @click="$wire.openNewTopicModal"
                        class="btn btn-md bg-blue-700 text-white border-0"
                    >
                        <x-mary-icon name="o-plus-circle" class="w-5 h-5" />
                        Novo Tópico
                    </button>
                </div>
            </div>
        </div>

        {{-- Abas --}}
        <div class="p-6" id="forum-show-tabs">
            <x-mary-tabs wire:model="selectedTab">
                {{-- ==================== ABA: TODOS OS TÓPICOS ==================== --}}
                <x-mary-tab name="all-tap">
                    <x-slot:label>
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-globe-alt" class="w-4 h-4" />
                            <span class="text-sm font-medium">Todos os Tópicos</span>
                            <x-mary-badge
                                value="{{ $this->allTopicsCount }}"
                            />
                        </div>
                    </x-slot:label>

                    <div class="space-y-3 mt-4" id="forum-show-all-topics">
                        @forelse ($this->allTopics as $topic)
                            @php
                                $currentUser = Auth::guard('teacher')->user() ?? Auth::guard('student')->user();
                                $currentGuard = Auth::guard('teacher')->check() ? 'teacher' : 'student';
                                $isMyTopic = $topic->owner_id === ($currentUser?->id ?? '')
                                    && str_ends_with($topic->owner_type, $currentGuard === 'teacher' ? 'Teacher' : 'Student');
                                $isTeacher = str_ends_with($topic->owner_type, 'Teacher');
                                $isNew = $topic->created_at->diffInMinutes(now()) < 60;
                                $canDelete = $currentUser?->can('delete', $topic) ?? false;
                            @endphp

                            <div
                                class="group relative bg-white border border-gray-100 rounded-sm p-5 hover:shadow-sm hover:border-blue-300 transition-all duration-200 topic-item {{ $isMyTopic ? 'ring-[1px] ring-blue-200' : '' }}"
                                wire:key="all-topic-{{ $topic->id }}"
                            >
                                {{-- Badge "Novo" flutuante --}}
                                @if($isNew)
                                    <div class="absolute -top-2 -right-2">
                                        <span class="relative flex h-10 w-10">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-10 w-10 bg-blue-500 items-center justify-center text-white text-xs font-bold">
                                                Novo
                                            </span>
                                        </span>
                                    </div>
                                @endif

                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        {{-- Título --}}
                                        <a
                                            href="{{ route('student.forum.topic.replie', $topic->id) }}"
                                            wire:navigate
                                            class="text-lg font-semibold text-gray-900 hover:text-pink-700 transition-colors block truncate"
                                        >
                                            {{ $topic->title }}
                                        </a>

                                        {{-- Meta Info --}}
                                        <div class="flex flex-wrap items-center gap-3 mt-3">
                                            {{-- Autor --}}
                                            <div class="flex items-center gap-2">

                                                @php
                                                    $name = $topic->owner->full_name ?? $topic->owner->first_name;
                                                @endphp

                                                @if($topic->owner->profile?->avatar)
                                                    <img src="{{ asset('storage/' . $topic->owner->profile->avatar) }}" class="w-10 h-10 rounded-full object-cover" alt="{{ $name }}"/>

                                                @else
                                                    <livewire:user-avatar-forum-list initiais="{{ $name }}"/>

                                                @endif


                                                <div>
                                                    <p class="text-sm font-medium {{ $isTeacher ? 'text-blue-700' : 'text-green-700' }}">
                                                        {{ $topic->owner->full_name ?? $topic->owner->first_name ?? 'Desconhecido' }}
                                                    </p>
                                                    <p class="text-xs text-gray-500">
                                                        {{ $isTeacher ? 'Professor' : 'Aluno' }}
                                                    </p>
                                                </div>
                                            </div>

                                            {{-- Turma --}}
                                            <div class="flex items-center gap-1 px-2 py-1 bg-gray-100 rounded-lg">
                                                <x-mary-icon name="o-academic-cap" class="w-4 h-4 text-gray-600" />
                                                <span class="text-xs font-medium text-gray-700">
                                                    {{ $topic->classe->name ?? 'Sem turma' }}
                                                </span>
                                            </div>

                                            {{-- Data --}}
                                            <div class="flex items-center gap-1 text-xs text-gray-500">
                                                <x-mary-icon name="o-clock" class="w-4 h-4" />
                                                <span>{{ $topic->created_at->diffForHumans() }}</span>
                                            </div>

                                            {{-- Badge "Meu Tópico" --}}
                                            @if($isMyTopic)
                                                <span class="px-2 py-1 bg-pink-100 text-pink-700 text-xs font-semibold rounded-full">
                                                    <x-mary-icon name="o-star" class="w-3 h-3 inline" />
                                                    Meu Tópico
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Ações (aparecem no hover) --}}
                                    @if($isMyTopic || $canDelete)
                                        <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            @if($isMyTopic)
                                            <button
                                                wire:click="editTopic('{{ $topic->id }}')"
                                                class="btn btn-sm btn-ghost text-blue-600 hover:bg-blue-50"
                                                title="Editar"
                                            >
                                                <x-mary-icon name="o-pencil-square" class="w-4 h-4" />
                                            </button>
                                            @endif
                                            <button
                                                @click="$dispatch('student::delete-topic-modal', topicId: {{ $topic->id }})"
                                                class="btn btn-sm {{ $canDelete && !$isMyTopic ? 'bg-red-500 text-white hover:bg-red-700' : 'btn-ghost text-red-600 hover:bg-red-50' }}"
                                                title="{{ $canDelete && !$isMyTopic ? 'Excluir (moderação)' : 'Excluir' }}"
                                            >
                                                <x-mary-icon name="o-trash" class="w-4 h-4" />
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-16">
                                <div class="w-24 h-24 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                    <x-mary-icon name="o-chat-bubble-left-ellipsis" class="w-12 h-12 text-gray-400" />
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhum tópico ainda</h3>
                                <p class="text-gray-500 mb-4">Seja o primeiro a iniciar uma discussão!</p>
                                <button
                                    wire:click="$wire.openNewTopicModal"
                                    class="btn btn-primary">
                                    <x-mary-icon name="o-plus" class="w-5 h-5" />
                                    Criar Primeiro Tópico
                                </button>
                            </div>
                        @endforelse
                        {{-- Paginação --}}
                        @if($this->allTopics->hasPages())
                            <div class="mt-6">
                                {{ $this->allTopics->links() }}
                            </div>
                        @endif
                    </div>
                </x-mary-tab>

                {{-- ==================== ABA: MEUS TÓPICOS ==================== --}}
                <x-mary-tab name="mine-tap">
                    <x-slot:label>
                        <div class="flex items-center gap-2" id="my-topics-created">
                            <x-mary-icon name="o-user-circle" class="w-4 h-4" />
                            <span>Meus Tópicos</span>
                            <x-mary-badge
                                value="{{ $this->myTopicsCount }}"
                            />
                        </div>
                    </x-slot:label>

                    <div class="space-y-3 mt-4" id="forum-show-my-topics">
                        @forelse ($this->myTopics as $topic)
                            @php
                                $isNew = $topic->created_at->diffInMinutes(now()) < 60;
                            @endphp

                            <div
                                class="group relative border-[1px] border-blue-100 rounded-sm p-5 hover:shadow-sm hover:from-blue-200 hover:to-blue-200 transition-all duration-200"
                                wire:key="my-topic-{{ $topic->id }}"
                            >
                                {{-- Badge "Novo" --}}
                                @if($isNew)
                                    <div class="absolute -top-2 -right-2">
                                        <span class="relative flex h-10 w-10">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-10 w-10 bg-green-500 items-center justify-center text-white text-xs font-bold">
                                                Novo
                                            </span>
                                        </span>
                                    </div>
                                @endif

                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        {{-- Título com estrela --}}
                                        <div class="flex items-center gap-2">
                                          <a
                                                href="{{ route('student.forum.index', $topic->id) }}"
                                                wire:navigate
                                                class="text-lg font-bold text-gray-900 hover:text-blue-700 transition-colors truncate"
                                            >
                                                {{ $topic->title }}
                                            </a>
                                        </div>

                                        {{-- Meta Info --}}
                                        <div class="flex flex-wrap items-center gap-3 mt-3">
                                            {{-- Turma --}}
                                            <div class="flex items-center gap-1 px-3 py-1 bg-white rounded-lg shadow-sm">
                                                <x-mary-icon name="o-academic-cap" class="w-4 h-4 text-blue-400" />
                                                <span class="text-sm font-medium text-gray-700">
                                                    {{ $topic->classe->name ?? 'Sem turma' }}
                                                </span>
                                            </div>

                                            {{-- Data --}}
                                            <div class="flex items-center gap-1 text-sm text-gray-600">
                                                <x-mary-icon name="o-clock" class="w-4 h-4" />
                                                <span>{{ $topic->created_at->diffForHumans() }}</span>
                                            </div>

                                            {{-- Visualizações (se tiver) --}}
                                            @if(isset($topic->views_count))
                                                <div class="flex items-center gap-1 text-sm text-gray-600">
                                                    <x-mary-icon name="o-eye" class="w-4 h-4" />
                                                    <span>{{ $topic->views_count }} visualizações</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Ações Visíveis --}}
                                    <div class="flex items-center gap-2">
                                        <button
                                            x-on:click="$dispatch('student::open-edit-topic-forum', { topicId: '{{ $topic->id }}' })"
                                            class="btn btn-sm bg-blue-500 text-white hover:bg-blue-700 border-0"
                                            title="Editar"
                                        >
                                            <x-mary-icon name="o-pencil-square" class="w-4 h-4" />
                                            <span class="hidden sm:inline">Editar</span>
                                        </button>
                                        <button
                                            x-on:click="$dispatch('student::delete-topic-forum', {topicId: '{{ $topic->id }}'})"
                                            class="btn btn-sm bg-red-400 text-white hover:bg-red-700 border-0"
                                            title="Excluir"
                                        >
                                            <x-mary-icon name="o-trash" class="w-4 h-4" />
                                            <span class="hidden sm:inline">Excluir</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-16">
                                <div class="w-24 h-24 mx-auto rounded-full flex items-center justify-center mb-4">
                                    <x-mary-icon name="o-document-plus" class="w-12 h-12 text-blue-600" />
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Você ainda não tem tópicos</h3>
                                <p class="text-gray-500 mb-6">Crie seu primeiro tópico e compartilhe suas ideias!</p>
                                <button
                                   @click="$wire.openNewTopicModal"
                                    class="btn bg-blue-400 text-white border-0 text-gray-900"
                                >
                                    <x-mary-icon name="o-sparkles" class="w-5 h-5" />
                                    Criar Meu Primeiro Tópico
                                </button>
                            </div>
                        @endforelse

                        {{-- Paginação --}}
                        @if($this->myTopics->hasPages())
                            <div class="mt-6">
                                {{ $this->myTopics->links() }}
                            </div>
                        @endif
                    </div>
                </x-mary-tab>
            </x-mary-tabs>
        </div>
    </div>



    {{-- Tour Modal --}}
    <div class="fixed inset-0 z-40 bg-gray-900/50" x-data x-show="$store.tourguide.openMai">
        <div
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2
            -translate-y-1/2 w-full max-w-4xl sm:w-2/3 bg-white rounded-lg
            shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">
           <div class="flex justify-between items-center">
               <h2 class="text-sn font-bold text-gray-700">Opções do Fórum</h2>
                <div class="flex justify-end">
                    <x-mary-button icon="o-x-mark"
                        class="bg-white border-none btn-square hover:none hover:bg-blue-400 hover:text-gray-100"
                        x-on:click="$store.tourguide.cancelOptionsTourPage()" />
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <x-mary-button
                    class="font-medium text-blue-600 border-[1px] border-blue-600 btn hover:bg-blue-600 hover:text-gray-100"
                    label="Criar tópico"
                    x-on:click="$store.tourguide.start('StartTourStudentForumShowNewTopic')" />
              <x-mary-button
                    class="font-medium text-blue-600 border-[1px] border-blue-600 btn hover:bg-blue-600 hover:text-gray-100"
                    label="Selecionar meus tópicos"
                    x-on:click="$store.tourguide.start('StartTourStudentForumShowMyTopics')" />
            </div>
        </div>
        <img src="{{ asset('images/rb-aiava.png') }}" class="fixed right-0 w-64 bottom-4 sm:w-48" />
    </div>

    {{-- Modals (Criar e Editar) --}}
    <livewire:student.forum.modal-new-topic-forum />
    <livewire:student.forum.modal-edit-topic-forum />
    <livewire:student.forum.modal-delete-topic-forum  />
</div>
