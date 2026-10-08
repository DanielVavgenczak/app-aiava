<?php
// resources/views/livewire/pages/teacher/forum/topic/show.blade.php

use Livewire\Component;
use Livewire\Attributes\{Layout, On, Computed};
use Livewire\WithPagination;
use App\Models\Forum\{ForumTopic, ForumReplie as ForumReply};
use Mary\Traits\Toast;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts::teacher')] class extends Component {
    use Toast, WithPagination;

    public string $topicId;
    public ?ForumTopic $topic = null;
    public int $perPage = 2;
    public array $visibleChildrenCount = [];

    public function mount(string $id): void
    {
        $this->topicId = $id;
        $this->loadTopic();

        // Inicializa contador de sub-respostas visíveis
        foreach ($this->repliesWithChildren as $item) {
            $replyId = $item['reply']->id;
            $this->visibleChildrenCount[$replyId] = 2; // Mostra 2 inicialmente
        }
    }

    /**
     * Verifica se pode deletar uma resposta (autor ou teacher moderador)
     */
    public function canDeleteReply(ForumReply $reply): bool
    {
        return Auth::guard('teacher')->user()?->can('delete', $reply) ?? false;
    }

    /**
     * Confirma deleção de resposta
     */
    public function confirmDeleteReply($replyId): void
    {
        $this->dispatch('confirm-delete-reply', $replyId)->to('teacher.forum.modal-reply-delete-to-replay');
    }


    #[On('forum-reply-deleted')]
    public function refreshRepliesAfterDelete(): void
    {
        unset($this->allReplies);
        unset($this->replies);
        unset($this->repliesWithChildren);
    }


    /**
     * Carrega mais sub-respostas para uma resposta específica
     */
    public function loadMoreChildren($replyId): void
    {
        // Incrementa +5 sub-respostas
        $this->visibleChildrenCount[$replyId] += 2;
    }

    /**
     * Verifica se ainda tem mais sub-respostas para carregar
     */
    public function hasMoreChildren($replyId): bool
    {
        $children = $this->getChildrenForReply($replyId);
        return $children->count() > ($this->visibleChildrenCount[$replyId] ?? 2);
    }

    /**
     * Pega as sub-respostas visíveis para uma resposta
     */
    public function getVisibleChildren($replyId): Collection
    {
        $children = $this->getChildrenForReply($replyId);
        $limit = $this->visibleChildrenCount[$replyId] ?? 2;

        return $children->take($limit);
    }

    /**
     * Pega todas as sub-respostas de uma resposta (do agrupamento)
     */
    private function getChildrenForReply($replyId): Collection
    {
        $item = $this->repliesWithChildren->firstWhere('reply.id', $replyId);
        return $item ? $item['children'] : collect();
    }

    protected function loadTopic(): void
    {
        $teacher = auth()->guard('teacher')->user();
        $this->topic = ForumTopic::with(['discipline', 'classe', 'owner'])
            ->withCount('replies')
            ->whereHas('discipline', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->findOrFail($this->topicId);
    }

    /**
     * ✅ Carrega TODAS as respostas para agrupamento (ORDEM CRONOLÓGICA)
     */
    #[Computed]
    public function allReplies(): Collection
    {
        if (!$this->topic) {
            return collect();
        }

        return ForumReply::where('topic_id', $this->topic->id)
            ->with('author')
            ->orderBy('created_at', 'asc')  // ✅ ordem cronológica para thread correto
            ->get();
    }

    /**
     * ✅ Respostas paginadas (apenas diretas)
     */
    #[Computed]
    public function replies()
    {
        if (!$this->topic) {
            return collect();
        }

        return ForumReply::where('topic_id', $this->topic->id)
            ->whereNull('parent_id')
            ->with(['author'])
            ->orderBy('created_at', 'asc')  // ✅ ordem cronológica
            ->paginate($this->perPage);
    }

    /**
     * ✅ Agrupa sub-respostas para cada resposta direta
     */
    #[Computed]
    public function repliesWithChildren()
    {
        $directReplies = $this->replies;

        if ($directReplies->isEmpty()) {
            return collect();
        }

        // Agrupa todas as respostas por parent_id
        $grouped = $this->allReplies->groupBy('parent_id');

        return $directReplies->map(function($reply) use ($grouped) {
            return [
                'reply' => $reply,
                'children' => $grouped->get($reply->id, collect())
            ];
        });
    }

    public function createReply(): void
    {
        $this->dispatch('teacher::open-reply-modal', topic_id: $this->topicId);
    }

    public function editReply($replyId): void
    {
        $this->dispatch('teacher::open-edit-reply-modal', replyId: $replyId);
    }

    public function replyToReply($parentReplyId): void
    {
        $reply = ForumReply::find($parentReplyId);

        if (!$reply) {
            $this->toast(type: 'error', title: 'Resposta não encontrada!');
            return;
        }

        // ✅ Sempre responder à resposta DIRETA (depth = 0)
        $targetParentId = $reply->parent_id ?? $parentReplyId;

        // Busca o nome do autor da resposta que está sendo respondida
        $mentionName = $reply->author_name;

        $this->dispatch('teacher::open-reply-to-reply-modal',
            topic_id: $this->topicId,
            parentReplyId: $targetParentId,
            mentionName: $mentionName
        );
    }

    #[On('reply-event')]
    public function refresh($type, $message): void
    {
        $this->loadTopic();

        // ✅ Limpa caches computados
        unset($this->allReplies);
        unset($this->replies);
        unset($this->repliesWithChildren);

        match($type) {
            'create' => $this->success($message),
            'update' => $this->success($message),
            'delete' => $this->error($message),
            default => null,
        };
    }

    // Os modais de resposta disparam 'reply-created'; sem este listener a lista
    // não era recarregada após criar uma resposta.
    #[On('reply-created')]
    public function refreshAfterCreate(): void
    {
        $this->refresh('create', 'Resposta enviada com sucesso!');
    }

};?>

<div>
    @php
        $breadcrumbItems = [
            ['label' => 'Disciplinas', 'route' => 'teacher.discipline', 'icon' => 'o-academic-cap'],
            ['label' => 'Fóruns', 'route' => 'teacher.forum'],
        ];
        if ($topic) {
            $breadcrumbItems[] = ['label' => $topic->discipline->title, 'route' => 'teacher.forum.show', 'params' => ['id' => $topic->discipline_id]];
            $breadcrumbItems[] = ['label' => Str::limit($topic->title, 30)];
        }
    @endphp
    <livewire:global.breadcrumb :items="$breadcrumbItems" tour-id="forum-replies-breadcrumb" />

    @if($topic)
    {{-- Header do Tópico --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6" id="forum-replies-header">
        <div class="p-8">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4 flex-1">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-100 to-purple-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <x-mary-icon name="o-chat-bubble-left-right" class="w-8 h-8 text-blue-600" />
                    </div>

                    <div class="flex-1">
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $topic->title }}</h1>
                        <div class="flex flex-wrap items-center gap-4 text-gray-600 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ $topic->owner->full_name ?? $topic->owner->first_name }}</span>
                                @if($topic->isOwnedByTeacher())
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">Professor</span>
                                @else
                                <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-semibold rounded-full">Aluno</span>
                                @endif
                            </div>
                            <span>•</span>
                            <div class="flex items-center gap-2">
                                <x-mary-icon name="o-calendar" class="w-4 h-4" />
                                <span>{{ $topic->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <span>•</span>
                            <div class="flex items-center gap-2">
                                <x-mary-icon name="o-chat-bubble-bottom-center" class="w-4 h-4" />
                                <span>{{ $topic->getRepliesCountAttribute() }} {{ Str::plural('resposta', $topic->getRepliesCountAttribute()) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <button wire:click="createReply"
                    id="forum-replies-new-btn"
                    class="btn btn-sm bg-blue-400 text-white hover:bg-blue-500 border-0 shadow-lg flex-shrink-0">
                    <x-mary-icon name="o-plus-circle" class="w-5 h-5" />
                    <span class="hidden sm:inline">Nova Resposta</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Lista de Respostas --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" id="forum-replies-list">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-gray-900 flex items-center gap-2">
                    <x-mary-icon name="o-chat-bubble-bottom-center" class="w-5 h-5 text-blue-600" />
                    Respostas ({{ $topic->getRepliesCountAttribute() }})
                </h2>
            </div>
        </div>

        @if($this->repliesWithChildren->count() > 0)
        <div class="divide-y divide-gray-200">
            @foreach($this->repliesWithChildren as $item)
            @php
                $reply = $item['reply'];
                $children = $item['children'];
            @endphp

            {{-- Resposta Principal --}}
            <div class="p-6" wire:key="reply-{{ $reply->id }}" data-tour="reply-item">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                            {{ substr($reply->author_name, 0, 1) }}
                        </div>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-semibold text-gray-900">{{ $reply->author_name }}</span>

                                    @if($reply->isTeacher())
                                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">
                                        Professor
                                    </span>
                                    @else
                                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-semibold rounded-full">
                                        Aluno
                                    </span>
                                    @endif

                                    @if($reply->isEdited())
                                    <span class="text-xs text-gray-400">(editado)</span>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2 mt-1 text-sm text-gray-500">
                                    <x-mary-icon name="o-clock" class="w-3 h-3" />
                                    <span>{{ $reply->created_at->diffForHumans() }}</span>
                                </div>
                            </div>

                            @if($reply->isMyReply(auth()->guard('teacher')->id(), 'teacher') || $this->canDeleteReply($reply))
                            <div class="flex items-center gap-2">
                                @if($reply->isMyReply(auth()->guard('teacher')->id(), 'teacher'))
                                <button wire:click="editReply('{{ $reply->id }}')"
                                    class="btn btn-sm bg-blue-100 text-blue-600" title="Editar" data-tour="edit-reply">
                                    <x-mary-icon name="o-pencil-square" class="w-4 h-4" />
                                </button>
                                @endif

                                <button wire:click="confirmDeleteReply('{{ $reply->id }}')"
                                    class="btn btn-sm {{ $this->canDeleteReply($reply) && !$reply->isMyReply(auth()->guard('teacher')->id(), 'teacher') ? 'bg-red-500 text-white' : 'bg-red-100 text-red-600' }}" title="{{ $this->canDeleteReply($reply) && !$reply->isMyReply(auth()->guard('teacher')->id(), 'teacher') ? 'Excluir (moderação)' : 'Excluir' }}" data-tour="delete-reply">
                                    <x-mary-icon name="o-trash" class="w-4 h-4" />
                                </button>
                            </div>
                            @endif
                        </div>

                        <div class="bg-gray-50 rounded-lg p-4 mb-3 border border-gray-200">
                            <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $reply->content }}</p>
                        </div>

                        <button wire:click="replyToReply('{{ $reply->id }}')"
                            data-tour="reply-to-reply"
                            class="btn btn-sm btn-ghost text-blue-600 hover:bg-blue-50">
                            <x-mary-icon name="o-chat-bubble-left" class="w-4 h-4" />
                            Responder
                        </button>

                        {{-- ✅ SUB-RESPOSTAS - vem do agrupamento --}}
                        {{-- Sub-respostas com LOAD MORE --}}
                        @php
                            $allChildren = $children; // Todas as sub-respostas
                            $visibleCount = $visibleChildrenCount[$reply->id] ?? 2;
                            $visibleChildren = $allChildren->take($visibleCount);
                            $hiddenCount = $allChildren->count() - $visibleCount;
                            $hasMore = $hiddenCount > 0;
                        @endphp
                        @if($children->count() > 0)
                        <div class="mt-6">
                            <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
                                <div class="h-px bg-gray-300 flex-1"></div>
                                <span class="font-medium">{{ $children->count() }} {{ Str::plural('resposta', $children->count()) }}</span>
                                <div class="h-px bg-gray-300 flex-1"></div>
                            </div>

                            <div class="space-y-3">
                                @foreach($visibleChildren as $subReply)
                                <div class="flex items-start gap-3 bg-blue-50 rounded-lg p-4 border-l-4 border-blue-400"
                                    wire:key="sub-reply-{{ $subReply->id }}">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-gradient-to-br from-{{ $subReply->isTeacher() ? 'blue' : 'green' }}-500 to-{{ $subReply->isTeacher() ? 'purple' : 'blue' }}-600 rounded-full flex items-center justify-center text-white font-bold">
                                            {{ substr($subReply->author_name, 0, 1) }}
                                        </div>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="font-semibold text-sm text-gray-900">{{ $subReply->author_name }}</span>

                                                @if($subReply->isTeacher())
                                                <span class="px-1.5 py-0.5 bg-blue-600 text-white text-xs font-semibold rounded">
                                                    Professor
                                                </span>
                                                @endif

                                                <span class="text-xs text-gray-500">{{ $subReply->created_at->diffForHumans() }}</span>
                                            </div>

                                            @if($subReply->isMyReply(auth()->guard('teacher')->id(), 'teacher') || $this->canDeleteReply($subReply))
                                            <div class="flex items-center gap-1">
                                                @if($subReply->isMyReply(auth()->guard('teacher')->id(), 'teacher'))
                                                <button wire:click="editReply('{{ $subReply->id }}')" class="btn btn-xs bg-gray-100 text-blue-600">
                                                    <x-mary-icon name="o-pencil" class="w-3 h-3" />
                                                </button>
                                                @endif

                                                <button wire:click="confirmDeleteReply('{{ $subReply->id }}')" class="btn btn-xs {{ $this->canDeleteReply($subReply) && !$subReply->isMyReply(auth()->guard('teacher')->id(), 'teacher') ? 'bg-red-500 text-white' : 'btn-ghost text-red-600' }}">
                                                    <x-mary-icon name="o-trash" class="w-3 h-3" />
                                                </button>
                                            </div>
                                            @endif
                                        </div>

                                        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $subReply->content }}</p>

                                        <button wire:click="replyToReply('{{ $subReply->id }}')"
                                            class="btn btn-xs btn-ghost text-blue-600 mt-2">
                                            <x-mary-icon name="o-chat-bubble-left" class="w-3 h-3" />
                                            Responder
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            @if ($hasMore)
                                                    <div class="flex justify-center mt-4">
                                                        <button wire:click="loadMoreChildren('{{ $reply->id }}')"
                                                            wire:loading.attr="disabled"
                                                            wire:target="loadMoreChildren('{{ $reply->id }}')"
                                                            class="btn btn-ghost btn-sm text-blue-600 hover:bg-blue-50 transition-all gap-2">
                                                            <span wire:loading.remove
                                                                wire:target="loadMoreChildren('{{ $reply->id }}')">
                                                                <x-mary-icon name="o-chevron-down" class="w-4 h-4" />
                                                                Ver mais {{ $hiddenCount }}
                                                                {{ Str::plural('resposta', $hiddenCount) }}
                                                            </span>
                                                            <span wire:loading
                                                                wire:target="loadMoreChildren('{{ $reply->id }}')">
                                                                <x-mary-loading class="loading-spinner loading-sm" />
                                                                Carregando...
                                                            </span>
                                                        </button>
                                                    </div>
                                                @endif

                                                {{-- Indicador que mostrou todas --}}
                                                @if (!$hasMore && $allChildren->count() > 5)
                                                    <div class="flex justify-center mt-4">
                                                        <span class="text-xs text-gray-400 flex items-center gap-1">
                                                            <x-mary-icon name="o-check" class="w-3 h-3" />
                                                            Todas as {{ $allChildren->count() }} respostas carregadas
                                                        </span>
                                                    </div>
                                                @endif

                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Paginação --}}
        @if($this->replies->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $this->replies->links() }}
        </div>
        @endif
        @else
        {{-- Estado Vazio --}}
        <div class="text-center py-16">
            <div class="w-24 h-24 mx-auto bg-gradient-to-br from-blue-100 to-purple-100 rounded-full flex items-center justify-center mb-4">
                <x-mary-icon name="o-chat-bubble-left" class="w-12 h-12 text-blue-600" />
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma resposta ainda</h3>
            <p class="text-gray-500 mb-6">
                Seja o primeiro a responder este tópico e iniciar a discussão!
            </p>
            <button wire:click="createReply"
                class="btn bg-gradient-to-r from-blue-600 to-purple-600 text-white border-0">
                <x-mary-icon name="o-plus-circle" class="w-5 h-5" />
                Adicionar Primeira Resposta
            </button>
        </div>
        @endif
    </div>
    @else
    {{-- Tópico não encontrado --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
        <x-mary-icon name="o-exclamation-triangle" class="w-16 h-16 mx-auto text-yellow-500 mb-4" />
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Tópico não encontrado</h3>
        <p class="text-gray-500 mb-6">
            O tópico que você está procurando não existe ou foi removido.
        </p>
        <a href="{{ route('teacher.forum') }}" wire:navigate class="btn btn-primary">
            <x-mary-icon name="o-arrow-left" class="w-5 h-5" />
            Voltar aos Fóruns
        </a>
    </div>
    @endif

    <livewire:teacher.forum.modal-replie-topic />
    <livewire:teacher.forum.modal-replie-to-replie-topic />
    <livewire:teacher.forum.modal-edit-replie-topic />
    <livewire:teacher.forum.modal-reply-delete-to-replay />

    {{-- Tour Modal --}}
    <div class="fixed inset-0 z-40 bg-gray-900/50" x-data x-show="$store.tourguide.openMai">
        <div
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2
            -translate-y-1/2 w-full max-w-4xl sm:w-2/3 bg-white rounded-lg
            shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">
            <div class="flex justify-between items-center">
                <h2 class="text-sn font-bold text-gray-700">Opções do Tópico</h2>
                <div class="flex justify-end">
                    <x-mary-button icon="o-x-mark"
                        class="bg-white border-none btn-square hover:none hover:bg-blue-400 hover:text-gray-100"
                        x-on:click="$store.tourguide.cancelOptionsTourPage()" />
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2">

                <x-mary-button
                    class="font-medium text-blue-600 border-[1px] border-blue-600 btn hover:bg-blue-600 hover:text-gray-100"
                    label="Nova resposta"
                    x-on:click="$store.tourguide.start('StartTourTeacherForumRepliesNewReply')" />

                <x-mary-button
                    class="font-medium text-blue-600 border-[1px] border-blue-600 btn hover:bg-blue-600 hover:text-gray-100"
                    label="Responder"
                    x-on:click="$store.tourguide.start('StartTourTeacherForumRepliesReplyToReply')" />

                <x-mary-button
                    class="font-medium text-blue-600 border-[1px] border-blue-600 btn hover:bg-blue-600 hover:text-gray-100"
                    label="Editar resposta"
                    x-on:click="$store.tourguide.start('StartTourTeacherForumRepliesEditReply')" />

                <x-mary-button
                    class="font-medium text-red-600 border-[1px] border-red-600 btn hover:bg-red-600 hover:text-gray-100"
                    label="Excluir resposta"
                    x-on:click="$store.tourguide.start('StartTourTeacherForumRepliesDeleteReply')" />
            </div>
        </div>
        <img src="{{ asset('images/rb-aiava.png') }}" class="fixed right-0 w-64 bottom-4 sm:w-48" />
    </div>

    
</div>
{{-- ```


Ex:

Minha respostas aqui para a interação

btn responder

   Aqui sera a resposta da resposta
   Aqui mais outra resposta
   Aqui outra e outr

Aqui outra lista de respostas aqui para a interação

btn responder

   Aqui sera a resposta da resposta
   Aqui mais outra resposta
   Aqui outra e outr




---

## 📋 Mudanças Principais

### ✅ **Estrutura Plana**
```
Resposta Principal (depth = 0)
└─ Sub-resposta 1 (depth = 1) ← mesmo nível visual
└─ Sub-resposta 2 (depth = 1) ← mesmo nível visual
└─ Sub-resposta 3 (depth = 1) ← mesmo nível visual

Resposta Principal 2 (depth = 0)
└─ Sub-resposta 1 (depth = 1)
```

### ✅ **Visual**
- Sub-respostas com `bg-blue-50` e `border-l-4 border-blue-400`
- Todas no mesmo nível de indentação (`ml-16`)
- Separador visual mostrando quantidade

### ✅ **Comportamento**
- Responder a uma sub-resposta **sempre cria** outra sub-resposta no **mesmo nível**
- Não há profundidade > 1

---

## 🎨 Preview Visual
```
┌─────────────────────────────────────┐
│ 👤 João Silva (Professor) │
│ ┌─────────────────────────────────┐ │
│ │ Qual a fórmula de Bhaskara? │ │
│ └─────────────────────────────────┘ │
│ [Responder] │
│ │
│ ─────── 2 respostas ────── │
│ ┌─────────────────────────────┐ │
│ │ 👤 Maria (Aluno) │ │
│ │ É x = (-b ± √Δ)/2a │ │
│ │ [Responder] │ │
│ └─────────────────────────────┘ │
│ ┌─────────────────────────────┐ │
│ │ 👤 Pedro (Aluno) │ │
│ │ Concordo com Maria! │ │
│ │ [Responder] │ │
│ └─────────────────────────────┘ │
└─────────────────────────────────────┘ --}}
