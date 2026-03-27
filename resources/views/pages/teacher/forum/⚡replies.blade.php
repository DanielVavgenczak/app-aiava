<?php
// resources/views/livewire/pages/teacher/forum/topic/show.blade.php

use Livewire\Component;
use Livewire\Attributes\{Layout, On, Computed};
use Livewire\WithPagination;
use App\Models\Forum\{ForumTopic, ForumReplie};
use Mary\Traits\Toast;

new #[Layout('layouts::teacher')] class extends Component {
    use Toast, WithPagination;

    public string $topicId;
    public ?ForumTopic $topic = null;
    public int $perPage = 10;

    public function mount(string $id): void
    {
        $this->topicId = $id;
        $this->loadTopic();
    }

    protected function loadTopic(): void
    {
        $this->topic = ForumTopic::with(['discipline', 'classe', 'owner'])
            ->withCount('replies')
            ->findOrFail($this->topicId);
    }

    #[Computed]
    public function replies()
    {
        if (!$this->topic) {
            return collect();
        }

        return ForumReplie::where('topic_id', $this->topic->id)
            ->directReplies()
            ->with(['author', 'children.author'])
            ->orderBy('created_at', 'asc')
            ->paginate($this->perPage);
    }

    public function createReply(): void
    {
        $this->dispatch('teacher::open-reply-modal', topic_id: $this->topicId);
    }

    public function editReply($replyId): void
    {
        $this->dispatch('teacher::open-edit-reply-modal', replyId: $replyId);
    }

    public function deleteReply($replyId): void
    {
        $this->dispatch('teacher::open-delete-reply-modal', replyId: $replyId);
    }

    public function replyToReply($parentReplyId): void
    {
        $this->dispatch('teacher::open-reply-to-reply-modal',
            topicId: $this->topicId,
            parentReplyId: $parentReplyId
        );
    }

    #[On('reply-event')]
    public function refresh($type, $message): void
    {
        $this->loadTopic();

        match($type) {
            'create' => $this->success($message),
            'update' => $this->success($message),
            'delete' => $this->error($message),
            default => null,
        };
    }
}; ?>

<div>
    {{-- Breadcrumb --}}
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('teacher.index') }}" wire:navigate class="text-gray-500 hover:text-blue-700 transition-colors">
                <x-mary-icon name="o-home" class="w-4 h-4" />
            </a>
            <x-mary-icon name="o-chevron-right" class="w-3 h-3 text-gray-400" />
            <a href="{{ route('teacher.forum') }}" wire:navigate class="text-gray-500 hover:text-blue-700">
                Fóruns
            </a>
            @if($topic)
                <x-mary-icon name="o-chevron-right" class="w-3 h-3 text-gray-400" />
                <a href="{{ route('teacher.forum.show', $topic->discipline_id) }}" wire:navigate class="text-gray-500 hover:text-blue-700">
                    {{ $topic->discipline->title }}
                </a>
                <x-mary-icon name="o-chevron-right" class="w-3 h-3 text-gray-400" />
                <span class="text-blue-700 font-medium">{{ Str::limit($topic->title, 30) }}</span>
            @endif
        </div>
    </div>

    @if($topic)
        {{-- Header do Tópico --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
            {{-- Banner com Gradiente --}}
            <div class="p-8 text-gray-800">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-4 flex-1">
                        {{-- Ícone --}}
                        <div class="w-16 h-16 bg-white bg-opacity-20 backdrop-blur-sm rounded-xl flex items-center justify-center flex-shrink-0">
                            <x-mary-icon name="o-chat-bubble-left-right" class="w-8 h-8" />
                        </div>

                        {{-- Info --}}
                        <div class="flex-1">
                            <h1 class="text-3xl font-bold mb-2">{{ $topic->title }}</h1>

                            @if($topic->description)
                                <p class="text-blue-100 mb-4">{{ $topic->description }}</p>
                            @endif

                            <div class="flex flex-wrap items-center gap-4 text-gray-600">
                                {{-- Autor --}}
                                <div class="flex items-center gap-2">
                                    {{-- <livewire:components.user-avatar
                                        :image="$topic->owner->profile?->avatar ? asset('storage/' . $topic->owner->profile->avatar) : null"
                                        :name="$topic->owner->full_name ?? 'Desconhecido'"
                                        size="sm"
                                        :color="$topic->isOwnedByTeacher() ? 'blue' : 'green'"
                                        :wire:key="'topic-owner-' . $topic->id"
                                    /> --}}
                                    {{ $topic->author_type }}
                                    <span>{{ $topic->owner->full_name ?? $topic->owner->first_name  }}</span>
                                    @if($topic->isOwnedByTeacher())
                                        <span class="px-2 py-0.5 bg-white bg-opacity-20 rounded-full text-xs">Professor</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-white bg-opacity-20 rounded-full text-xs">Aluno</span>
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
                                    <span>{{ $topic->replies_count }} {{ Str::plural('resposta', $topic->replies_count) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Botão Nova Resposta --}}
                    <button
                        wire:click="createReply"
                        class="btn bg-blue-700 text-white hover:bg-blue-900 shadow-sm flex-shrink-0"
                    >
                        <x-mary-icon name="o-plus-circle" class="w-5 h-5" />
                        <span class="hidden sm:inline">Nova Resposta</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Lista de Respostas --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            {{-- Header --}}
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-gray-900 flex items-center gap-2">
                        <x-mary-icon name="o-chat-bubble-bottom-center" class="w-5 h-5 text-blue-600" />
                        Respostas ({{ $topic->replies_count }})
                    </h2>

                    {{-- @if($topic->latestReply)
                        <span class="text-sm text-gray-500">
                            Última resposta {{ $topic->latestReply->created_at->diffForHumans() }}
                        </span>
                    @endif --}}
                </div>
            </div>

            {{-- Respostas --}}
            @if($this->replies->count() > 0)
                <div class="divide-y divide-gray-200">
                    @foreach($this->replies as $reply)
                        <div class="p-6 hover:bg-gray-50 transition-colors" wire:key="reply-{{ $reply->id }}">
                            {{-- Resposta Principal --}}
                            <div class="flex items-start gap-4">
                                {{-- Avatar --}}
                                <div class="flex-shrink-0">
                                  --avatar
                                    {{-- <livewire:components.user-avatar
                                        :image="$reply->author_avatar"
                                        :name="$reply->author_name"
                                        size="md"
                                        :color="$reply->author_badge_color"
                                        :wire:key="'avatar-' . $reply->id"
                                    />
                                </div> --}}

                                {{-- Conteúdo --}}
                                <div class="flex-1 min-w-0">
                                    {{-- Header da Resposta --}}
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
                                                <span>•</span>
                                                <span>{{ $reply->created_at->format('d/m/Y H:i') }}</span>
                                            </div>
                                        </div>

                                        {{-- Ações (se for o autor) --}}
                                        @if($reply->isMyReply(auth()->guard('teacher')->id(), 'teacher'))
                                            <div class="flex items-center gap-2">
                                                <button
                                                    wire:click="editReply('{{ $reply->id }}')"
                                                    class="btn btn-sm btn-ghost text-blue-600 hover:bg-blue-50"
                                                    title="Editar"
                                                >
                                                    <x-mary-icon name="o-pencil-square" class="w-4 h-4" />
                                                </button>

                                                <button
                                                    wire:click="deleteReply('{{ $reply->id }}')"
                                                    class="btn btn-sm btn-ghost text-red-600 hover:bg-red-50"
                                                    title="Excluir"
                                                >
                                                    <x-mary-icon name="o-trash" class="w-4 h-4" />
                                                </button>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Conteúdo da Resposta --}}
                                    <div class="bg-gray-50 rounded-lg p-4 mb-4 border border-gray-200">
                                        <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $reply->content }}</p>
                                    </div>

                                    {{-- Botão Responder --}}
                                    <button
                                        wire:click="replyToReply('{{ $reply->id }}')"
                                        class="btn btn-sm btn-ghost text-blue-600 hover:bg-blue-50"
                                    >
                                        <x-mary-icon name="o-chat-bubble-left" class="w-4 h-4" />
                                        Responder
                                    </button>

                                    {{-- Sub-respostas --}}
                                    @if($reply->hasReplies())
                                        <div class="mt-6 space-y-4">
                                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                                <div class="h-px bg-gray-300 flex-1"></div>
                                                <span class="font-medium">{{ $reply->children->count() }} {{ Str::plural('resposta', $reply->children->count()) }}</span>
                                                <div class="h-px bg-gray-300 flex-1"></div>
                                            </div>

                                            @foreach($reply->children as $subReply)
                                                <div class="flex items-start gap-3 pl-6 border-l-2 border-blue-200" wire:key="sub-reply-{{ $subReply->id }}">
                                                    {{-- Avatar --}}
                                                    <div class="flex-shrink-0">
                                                       --vatar
                                                        {{-- <livewire:components.user-avatar
                                                            :image="$subReply->author_avatar"
                                                            :name="$subReply->author_name"
                                                            size="sm"
                                                            :color="$subReply->author_badge_color"
                                                            :wire:key="'sub-avatar-' . $subReply->id"
                                                        /> --}}
                                                    </div>

                                                    {{-- Conteúdo --}}
                                                    <div class="flex-1 bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                                                        <div class="flex items-center justify-between mb-2">
                                                            <div class="flex items-center gap-2 flex-wrap">
                                                                <span class="font-semibold text-sm text-gray-900">{{ $subReply->author_name }}</span>

                                                                @if($subReply->isTeacher())
                                                                    <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 text-xs font-semibold rounded">
                                                                        Prof
                                                                    </span>
                                                                @endif

                                                                <span class="text-xs text-gray-500">{{ $subReply->created_at->diffForHumans() }}</span>
                                                            </div>

                                                            {{-- Ações --}}
                                                            @if($subReply->isMyReply(auth()->guard('teacher')->id(), 'teacher'))
                                                                <div class="flex items-center gap-1">
                                                                    <button
                                                                        wire:click="editReply('{{ $subReply->id }}')"
                                                                        class="btn btn-xs btn-ghost"
                                                                    >
                                                                        <x-mary-icon name="o-pencil" class="w-3 h-3" />
                                                                    </button>

                                                                    <button
                                                                        wire:click="deleteReply('{{ $subReply->id }}')"
                                                                        class="btn btn-xs btn-ghost text-red-600"
                                                                    >
                                                                        <x-mary-icon name="o-trash" class="w-3 h-3" />
                                                                    </button>
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $subReply->content }}</p>
                                                    </div>
                                                </div>
                                            @endforeach
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
                    <button
                        wire:click="createReply"
                        class="btn bg-gradient-to-r from-blue-600 to-purple-600 text-white border-0 hover:from-blue-700 hover:to-purple-700"
                    >
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

    {{-- Modal de Criar Resposta --}}
    <livewire:teacher.forum.modal-replie-topic />
</div>
