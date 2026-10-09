<?php

use Livewire\Component;
use App\Models\Forum\ForumReplie as ForumReply;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;
use Livewire\Attributes\On;

new class extends Component
{
    use Toast;

    public ?string $deleteReplyId = null;
    public bool $showDeleteModal = false;

    #[On('confirm-delete-reply')]
    public function openModal(string $replyId): void
    {
        $teacher = Auth::guard('teacher')->user();
        $reply = \App\Models\Forum\ForumReplie::where('id', $replyId)
            ->whereHas('topic.discipline', function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id);
            })
            ->first();

        if (!$reply) {
            $this->error('Você não tem permissão para excluir esta resposta.');
            $this->showDeleteModal = false;
            $this->deleteReplyId = null;
            return;
        }

        $this->deleteReplyId = $replyId;
        $this->showDeleteModal = true;
    }

    public function executeDeleteReply(): void
    {
        $teacher = Auth::guard('teacher')->user();
        $reply = ForumReply::where('id', $this->deleteReplyId)
            ->whereHas('topic.discipline', function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id);
            })
            ->first();

        if (!$reply || !$teacher?->can('delete', $reply)) {
            $this->error('Você não tem permissão para excluir esta resposta.');
            $this->showDeleteModal = false;
            $this->deleteReplyId = null;
            return;
        }

        $reply->delete();
        $this->showDeleteModal = false;
        $this->deleteReplyId = null;

        $this->dispatch('reply-event', type: 'delete', message: 'Resposta excluída com sucesso!');
        $this->dispatch('forum-reply-deleted');
    }

    #[On('teacher::close-delete-reply-to-replay-forum-topic')]
    public function cancelDeleteReply(): void
    {
        $this->showDeleteModal = false;
        $this->deleteReplyId = null;
    }
};
?>

<div>
    <x-mary-modal wire:model="showDeleteModal" persistent class="backdrop-blur-sm">
        <div id="form-delete-reply" class="text-center">
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
                <button wire:click="cancelDeleteReply" id="btn-cancel-delete-reply" class="btn btn-ghost">
                    Cancelar
                </button>

                <button wire:click="executeDeleteReply" id="btn-confirm-delete-reply"
                    class="btn bg-red-600 text-white hover:bg-red-700 border-0">
                    <span wire:loading.remove wire:target="executeDeleteReply">
                        Excluir
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
