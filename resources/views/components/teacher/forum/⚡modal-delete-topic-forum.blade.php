<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Forum\ForumTopic;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public bool $open = false;
    public ?ForumTopic $topic = null;

    #[On('teacher::delete-topic-forum')]
    public function openModal($topicId): void
    {
        $this->topic = ForumTopic::find($topicId);

        if ($this->topic && Auth::guard('teacher')->user()?->can('delete', $this->topic)) {
            $this->open = true;
        } else {
            $this->error('Você não tem permissão para excluir este tópico.');
        }
    }

    public function delete(): void
    {
        if (!$this->topic || !Auth::guard('teacher')->user()?->can('delete', $this->topic)) {
            $this->error('Você não tem permissão para excluir este tópico.');
            return;
        }

        $topicTitle = $this->topic->title;
        $this->topic->delete();

        $this->close();
        $this->success("Tópico '{$topicTitle}' excluído com sucesso!");

        $this->dispatch('forum-event', type: 'delete', msg: 'Tópico excluído!');
    }

    #[On('teacher::close-delete-forum-topic')]
    public function close(): void
    {
        $this->open = false;
        $this->reset('topic');
    }
}; ?>

<div>
    <x-mary-modal wire:model="open" persistent class="backdrop-blur-sm">
        <div id="form-delete-topic" class="text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                <x-mary-icon name="o-exclamation-triangle" class="h-10 w-10 text-red-600" />
            </div>

            <h3 class="text-xl font-bold text-gray-900 mb-2">
                Excluir Tópico?
            </h3>

            <div class="mb-6">
                <p class="text-gray-600 mb-3">
                    Tem certeza que deseja excluir o tópico:
                </p>

                @if($topic)
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-3">
                        <p class="font-semibold text-gray-900 text-lg">
                            "{{ $topic->title }}"
                        </p>

                        @if($topic->description)
                            <p class="text-sm text-gray-600 mt-2 line-clamp-2">
                                {{ $topic->description }}
                            </p>
                        @endif

                        <div class="flex items-center justify-center gap-4 mt-3 text-xs text-gray-500">
                            <span>Criado {{ $topic->created_at->diffForHumans() }}</span>

                            @if($topic->replies_count ?? 0 > 0)
                                <span>•</span>
                                <span>{{ $topic->replies_count }} respostas</span>
                            @endif
                        </div>

                        @if($topic->owner_id !== auth()->guard('teacher')->id())
                            <div class="mt-2 inline-flex items-center gap-1 px-2 py-1 bg-orange-100 text-orange-700 text-xs font-semibold rounded-full">
                                <x-mary-icon name="o-shield-exclamation" class="w-3 h-3" />
                                Moderação - Tópico de {{ $topic->owner->full_name ?? $topic->owner->first_name ?? 'aluno' }}
                            </div>
                        @endif
                    </div>
                @endif

                <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                    <div class="flex items-start gap-2">
                        <x-mary-icon name="o-exclamation-circle" class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
                        <p class="text-sm text-red-800 text-left">
                            <strong>Atenção:</strong> Esta ação não pode ser desfeita.
                            Todas as respostas e interações serão perdidas permanentemente.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 justify-center">
                <button
                    id="btn-cancel-delete"
                    wire:click="close"
                    class="btn btn-sm btn-ghost"
                >
                    Cancelar
                </button>

                <button
                    id="btn-confirm-delete"
                    wire:click="delete"
                    class="btn bg-red-400 text-white hover:bg-red-500 border-0"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="delete">
                        Sim, Excluir Tópico
                    </span>
                    <span wire:loading wire:target="delete" class="flex items-center gap-2">
                        <span class="loading loading-spinner loading-sm"></span>
                        Excluindo...
                    </span>
                </button>
            </div>
        </div>
    </x-mary-modal>
</div>
