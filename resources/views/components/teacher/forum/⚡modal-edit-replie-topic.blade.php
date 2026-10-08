<?php

use Livewire\Component;
use Livewire\Attributes\{On, Validate};
use App\Models\Forum\ForumReplie;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public bool $open = false;
    public ?ForumReplie $reply = null;

    #[Validate('required|string|min:1', message: 'O campo Resposta é obrigatório!')]
    public string $content = '';

    #[On('teacher::open-edit-reply-modal')]
    public function openModal($replyId): void
    {
        $teacher = Auth::guard('teacher')->user();
        $this->reply = ForumReplie::where('id', $replyId)
            ->whereHas('topic.discipline', function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id);
            })
            ->first();

        if ($this->reply && $teacher?->can('update', $this->reply)) {
            $this->content = $this->reply->content;
            $this->open = true;
        } else {
            $this->reset(['content', 'reply']);
            $this->open = false;
            $this->error('Você não tem permissão para editar esta resposta.');
        }
    }

    public function update(): void
    {
        if (!$this->reply || !Auth::guard('teacher')->user()?->can('update', $this->reply)) {
            $this->error('Você não tem permissão para editar esta resposta.');
            return;
        }

        $this->validate();

        $this->reply->update([
            'content' => $this->content,
        ]);

        $this->close();
        $this->success('Resposta atualizada com sucesso!');

        $this->dispatch('reply-event', type: 'update', message: 'Resposta atualizada!');
    }

    public function cancel(): void
    {
        $this->close();
    }

    #[On('teacher::close-edit-reply-forum-topic')]
    public function close(): void
    {
        $this->open = false;
        $this->reset(['content', 'reply']);
    }

    #[On('tour::close-opened-modals')]
    public function closeForTour(): void
    {
        $this->close();
    }
}; ?>

<div>
    <x-mary-modal wire:model="open" title="Editar Resposta" persistent class="backdrop-blur-sm">
        <form id="form-edit-reply" wire:submit.prevent="update" class="space-y-4">
            <div id="input-edit-reply">
                <x-mary-textarea
                    wire:model="content"
                    label="Resposta"
                    placeholder="Digite a resposta..."
                    class="textarea textarea-bordered w-full !outline-none"
                    rows="6"
                    hint="O texto da resposta será atualizado"
                />
            </div>

            @if($reply)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <div class="flex items-start gap-2">
                        <x-mary-icon name="o-information-circle" class="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" />
                        <div class="text-sm text-blue-800">
                            <p class="font-medium">Criada em: {{ $reply->created_at->format('d/m/Y H:i') }}</p>
                            @if($reply->isEdited())
                                <p class="mt-1">Última edição: {{ $reply->updated_at->diffForHumans() }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex justify-end items-center gap-2">
                <button
                    type="button"
                    class="btn btn-sm btn-ghost"
                    id="btn-cancel-edit-reply"
                    @click="$wire.cancel()"
                >Cancelar</button>
                <button
                    type="submit"
                    id="btn-save-edit-reply"
                    class="btn btn-sm bg-blue-400 hover:bg-blue-500 text-white">
                    Alterar resposta
                </button>
            </div>
        </form>
    </x-mary-modal>
</div>
