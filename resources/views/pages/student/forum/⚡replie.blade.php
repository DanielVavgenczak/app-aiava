<?php
// resources/views/livewire/pages/teacher/forum/topic/show.blade.php

use Livewire\Component;
use Livewire\Attributes\{Layout, On, Computed};
use Livewire\WithPagination;
use App\Models\Forum\{ForumTopic, ForumReplie as ForumReply};
use Mary\Traits\Toast;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts::student')] class extends Component {
    use Toast, WithPagination;

    public string $topicId;
    public ?ForumTopic $topic = null;
    public int $perPage = 5;

    // controll sub respostas
    public array $visibleChildrenCount = [];
    public bool $showDeleteModal = false;
    public ?string $deleteReplyId = null;

    public function mount(string $id): void
    {
        $this->topicId = $id;
        $this->loadTopic();

        // Inicializa contador de sub-respostas visíveis
        foreach ($this->repliesWithChildren as $item) {
            $replyId = $item['reply']->id;
            $this->visibleChildrenCount[$replyId] = 2; // Mostra 5 inicialmente
        }
    }

    /**
     * Helper para pegar o usuário autenticado (qualquer guard)
     */
    private function getCurrentUser()
    {
        return Auth::guard('teacher')->user() ?? Auth::guard('student')->user();
    }

    /**
     * Helper para pegar o guard atual
     */
    private function getCurrentGuard(): string
    {
        if (Auth::guard('teacher')->check()) return 'teacher';
        if (Auth::guard('student')->check()) return 'student';
        return 'student';
    }

    /**
     * Verifica se a reply é do usuário atual
     */
    private function isMyReply(ForumReply $reply): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;
        return $reply->author_id === $user->id && $reply->author_type === get_class($user);
    }

    /**
     * Verifica se pode deletar (autor ou teacher moderador)
     */
    public function canDeleteReply(ForumReply $reply): bool
    {
        return $this->getCurrentUser()?->can('delete', $reply) ?? false;
    }

    /**
     * Confirma deleção de resposta
     */
    public function confirmDeleteReply($replyId): void
    {
        $this->deleteReplyId = $replyId;
        $this->showDeleteModal = true;
    }

    /**
     * Executa deleção de resposta
     */
    public function executeDeleteReply(): void
    {
        $reply = ForumReply::find($this->deleteReplyId);

        if (!$reply || !$this->getCurrentUser()?->can('delete', $reply)) {
            $this->error('Você não tem permissão para excluir esta resposta.');
            $this->showDeleteModal = false;
            $this->deleteReplyId = null;
            return;
        }

        $reply->delete();
        $this->showDeleteModal = false;
        $this->deleteReplyId = null;

        unset($this->allReplies);
        unset($this->replies);
        unset($this->repliesWithChildren);

        $this->dispatch('reply-event', type: 'delete', message: 'Resposta excluída com sucesso!');
    }

    public function cancelDeleteReply(): void
    {
        $this->showDeleteModal = false;
        $this->deleteReplyId = null;
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
        $this->topic = ForumTopic::with(['discipline', 'classe', 'owner'])
            ->withCount('replies')
            ->orderBy('created_at', 'asc')
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
            ->orderBy('created_at', 'desc') // ✅ ordem cronológica para thread correto
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
            ->orderBy('created_at', 'desc') // ✅ ordem cronológica
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

        return $directReplies->map(function ($reply) use ($grouped) {
            return [
                'reply' => $reply,
                'children' => $grouped->get($reply->id, collect()),
            ];
        });
    }

    public function createReply(): void
    {
        $this->dispatch('student::open-reply-modal', topic_id: $this->topicId);
    }

    public function editReply($replyId): void
    {
        $this->dispatch('student::open-edit-reply-modal', replyId: $replyId);
    }

    public function replyToReply($parentReplyId): void
    {
        // ✅ CORRIGIDO: busca o reply pelo ID
        $reply = ForumReply::find($parentReplyId);

        if (!$reply) {
            $this->toast(type: 'error', title: 'Resposta não encontrada!');
            return;
        }

        // ✅ Sempre responder à resposta DIRETA (depth = 0)
        $targetParentId = $reply->parent_id ?? $parentReplyId;

        // Busca o nome do autor da resposta que está sendo respondida
        $mentionName = $reply->author_name;

        $this->dispatch('student::open-reply-to-reply-modal', topic_id: $this->topicId, parentReplyId: $targetParentId, mentionName: $mentionName);
    }

    #[On('reply-event')]
    public function refresh($type, $message): void
    {
        $this->loadTopic();

        // ✅ Limpa caches computados
        unset($this->allReplies);
        unset($this->replies);
        unset($this->repliesWithChildren);

        match ($type) {
            'create' => $this->success($message),
            'update' => $this->success($message),
            'delete' => $this->error($message),
            default => null,
        };
    }
}; ?>

<div>
    @php
        $breadcrumbItems = [
            ['label' => 'Disciplinas', 'route' => 'student.discipline', 'icon' => 'o-home'],
            ['label' => 'Fóruns', 'route' => 'student.forum.index'],
        ];
        if ($topic) {
            $breadcrumbItems[] = ['label' => $topic->discipline->title, 'route' => 'student.forum.show', 'params' => ['id' => $topic->discipline_id]];
            $breadcrumbItems[] = ['label' => Str::limit($topic->title, 30)];
        }
    @endphp
    <livewire:global.breadcrumb :items="$breadcrumbItems" tour-id="forum-replies-breadcrumb" />

    @if ($topic)
        {{-- Header do Tópico --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6" id="forum-replies-header">
            <div class="p-8">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-4 flex-1">
                        <div
                            class="w-16 h-16 bg-gradient-to-br from-blue-100 to-purple-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <x-mary-icon name="o-chat-bubble-left-right" class="w-8 h-8 text-blue-600" />
                        </div>

                        <div class="flex-1">
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $topic->title }}</h1>
                            <div class="flex flex-wrap items-center gap-4 text-gray-600 text-sm">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="font-medium">{{ $topic->owner->full_name ?? $topic->owner->first_name }}</span>
                                    @if ($topic->isOwnedByTeacher())
                                        <span
                                            class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">Professor</span>
                                    @else
                                        <span
                                            class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-semibold rounded-full">Aluno</span>
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
                                    <span>{{ $topic->getRepliesCountAttribute() }}
                                        {{ Str::plural('resposta', $topic->getRepliesCountAttribute()) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button id="forum-replies-new-btn" wire:click="createReply"
                        class="btn btn-sm bg-blue-600 text-white hover:bg-blue-700 border-0 shadow-lg flex-shrink-0">
                        <x-mary-icon name="o-plus-circle" class="w-5 h-5" />
                        <span class="hidden sm:inline">Nova Resposta</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Lista de Respostas --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-gray-900 flex items-center gap-2">
                        <x-mary-icon name="o-chat-bubble-bottom-center" class="w-5 h-5 text-blue-600" />
                        Respostas ({{ $topic->getRepliesCountAttribute() }})
                    </h2>
                </div>
            </div>

            @if ($this->repliesWithChildren->count() > 0)
                <div class="divide-y divide-gray-200" id="forum-replies-list">
                    @foreach ($this->repliesWithChildren as $item)
                        @php
                            $reply = $item['reply'];
                            $children = $item['children'];
                        @endphp

                        {{-- Resposta Principal --}}
                        <div class="p-6" wire:key="reply-{{ $reply->id }}">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                        {{ substr($reply->author_name, 0, 1) }}
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between mb-3">
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span
                                                    class="font-semibold text-gray-900">{{ $reply->author_name }}</span>

                                                @if ($reply->isTeacher())
                                                    <span
                                                        class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">
                                                        Professor
                                                    </span>
                                                @else
                                                    <span
                                                        class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-semibold rounded-full">
                                                        Aluno
                                                    </span>
                                                @endif

                                                @if ($reply->isEdited())
                                                    <span class="text-xs text-gray-400">(editado)</span>
                                                @endif
                                            </div>

                                            <div class="flex items-center gap-2 mt-1 text-sm text-gray-500">
                                                <x-mary-icon name="o-clock" class="w-3 h-3" />
                                                <span>{{ $reply->created_at->diffForHumans() }}</span>
                                            </div>
                                        </div>

                                        @if ($this->isMyReply($reply) || $this->canDeleteReply($reply))
                                            <div class="flex items-center gap-2">
                                                @if ($this->isMyReply($reply))
                                                <button wire:click="editReply('{{ $reply->id }}')"
                                                    class="btn btn-sm bg-blue-100 text-blue-600" title="Editar">
                                                    <x-mary-icon name="o-pencil-square" class="w-4 h-4" />
                                                </button>
                                                @endif

                                                <button wire:click="confirmDeleteReply('{{ $reply->id }}')"
                                                    class="btn btn-sm {{ $this->canDeleteReply($reply) && !$this->isMyReply($reply) ? 'bg-red-500 text-white' : 'bg-red-100 text-red-600' }}" title="{{ $this->canDeleteReply($reply) && !$this->isMyReply($reply) ? 'Excluir (moderação)' : 'Excluir' }}">
                                                    <x-mary-icon name="o-trash" class="w-4 h-4" />
                                                </button>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="bg-gray-50 rounded-lg p-4 mb-3 border border-gray-200">
                                        <p class="text-gray-700 leading-relaxed">
                                            {{ $reply->content }}</p>
                                    </div>

                                    <button wire:click="replyToReply('{{ $reply->id }}')"
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
                                    @if ($children->count() > 0)
                                        <div class="mt-6">
                                            <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
                                                <div class="h-px bg-gray-300 flex-1"></div>
                                                <span class="font-medium">{{ $children->count() }}
                                                    {{ Str::plural('resposta', $children->count()) }}</span>
                                                <div class="h-px bg-gray-300 flex-1"></div>
                                            </div>

                                            <div class="space-y-3">
                                                @foreach ($visibleChildren as $subReply)
                                                    <div class="flex items-start gap-3 bg-blue-50 rounded-lg p-4 border-l-4 border-blue-400"
                                                        wire:key="sub-reply-{{ $subReply->id }}">
                                                        <div class="flex-shrink-0">
                                                            <div
                                                                class="w-10 h-10 bg-gradient-to-br from-{{ $subReply->isTeacher() ? 'blue' : 'green' }}-500 to-{{ $subReply->isTeacher() ? 'purple' : 'blue' }}-600 rounded-full flex items-center justify-center text-white font-bold">
                                                                {{ substr($subReply->author_name, 0, 1) }}
                                                            </div>
                                                        </div>

                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex items-center justify-between mb-2">
                                                                <div class="flex items-center gap-2 flex-wrap">
                                                                    <span
                                                                        class="font-semibold text-sm text-gray-900">{{ $subReply->author_name }}</span>

                                                                    @if ($subReply->isTeacher())
                                                                        <span
                                                                            class="px-1.5 py-0.5 bg-blue-600 text-white text-xs font-semibold rounded">
                                                                            Professor
                                                                        </span>
                                                                    @endif

                                                                    <span
                                                                        class="text-xs text-gray-500">{{ $subReply->created_at->diffForHumans() }}</span>
                                                                </div>

                                                                @if ($this->isMyReply($subReply) || $this->canDeleteReply($subReply))
                                                                    <div class="flex items-center gap-1">
                                                                        @if ($this->isMyReply($subReply))
                                                                        <button
                                                                            wire:click="editReply('{{ $subReply->id }}')"
                                                                            class="btn btn-xs bg-gray-100 text-blue-600">
                                                                            <x-mary-icon name="o-pencil"
                                                                                class="w-3 h-3" />
                                                                        </button>
                                                                        @endif

                                                                        <button
                                                                            wire:click="confirmDeleteReply('{{ $subReply->id }}')"
                                                                            class="btn btn-xs {{ $this->canDeleteReply($subReply) && !$this->isMyReply($subReply) ? 'bg-red-500 text-white' : 'btn-ghost text-red-600' }}">
                                                                            <x-mary-icon name="o-trash"
                                                                                class="w-3 h-3" />
                                                                        </button>
                                                                    </div>
                                                                @endif
                                                            </div>

                                                            <p class="text-sm text-gray-700 whitespace-pre-wrap">
                                                                {{ $subReply->content }}</p>

                                                            <button wire:click="replyToReply('{{ $subReply->id }}')"
                                                                class="btn btn-xs btn-ghost text-blue-600 mt-2">
                                                                <x-mary-icon name="o-chat-bubble-left"
                                                                    class="w-3 h-3" />
                                                                Responder
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endforeach
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
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Paginação --}}
                @if ($this->replies->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                        {{ $this->replies->links() }}
                    </div>
                @endif
            @else
                {{-- Estado Vazio --}}
                <div class="text-center py-16">
                    <div
                        class="w-24 h-24 mx-auto bg-gradient-to-br from-blue-100 to-purple-100 rounded-full flex items-center justify-center mb-4">
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
            <a href="{{ route('student.forum.index') }}" wire:navigate class="btn btn-primary">
                <x-mary-icon name="o-arrow-left" class="w-5 h-5" />
                Voltar aos Fóruns
            </a>
        </div>
    @endif

    <livewire:student.forum.modal-replie-topic />
    <livewire:student.forum.modal-replie-to-replie-forum />

    {{-- Modal de confirmação de exclusão de resposta --}}
    <x-mary-modal wire:model="showDeleteModal" persistent class="backdrop-blur-sm">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                <x-mary-icon name="o-exclamation-triangle" class="h-10 w-10 text-red-600" />
            </div>

            <h3 class="text-xl font-bold text-gray-900 mb-2">
                Excluir Resposta?
            </h3>

            <p class="text-gray-600 mb-6">
                Tem certeza que deseja excluir esta resposta? Esta ação não pode ser desfeita.
            </p>

            <div class="flex gap-3 justify-center">
                <button wire:click="cancelDeleteReply" class="btn btn-ghost">
                    <x-mary-icon name="o-x-mark" class="w-5 h-5" />
                    Cancelar
                </button>

                <button wire:click="executeDeleteReply"
                    class="btn bg-red-600 text-white hover:bg-red-700 border-0"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="executeDeleteReply">
                        <x-mary-icon name="o-trash" class="w-5 h-5" />
                        Sim, Excluir
                    </span>
                    <span wire:loading wire:target="executeDeleteReply" class="flex items-center gap-2">
                        <span class="loading loading-spinner loading-sm"></span>
                        Excluindo...
                    </span>
                </button>
            </div>
        </div>
    </x-mary-modal>
</div>
