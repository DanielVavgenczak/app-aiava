<?php

use Livewire\Component;
use Livewire\Attributes\{On, Validate};
use App\Models\Forum\ForumReplie;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public bool $open = false;
    public string $topic_id = '';
    public string $parent_id = '';

    #[Validate('required', message: 'O campo Resposta é obrigatório!')]
    public string $content = '';

    #[On('teacher::open-reply-modal')]
    public function open(string $topic_id, ?string $parentId = null)
    {
        $this->reset(['content', 'parent_id']);
        $this->topic_id = $topic_id;
        $this->parent_id = $parentId ?? '';
        $this->open = true;
    }

    public function save()
    {
        $this->validate();

        $author = Auth::guard('teacher')->check()
            ? Auth::guard('teacher')->user()
            : Auth::guard('student')->user();

        ForumReplie::create([
            'topic_id' => $this->topic_id,
            'parent_id' => empty($this->parent_id) ? null : $this->parent_id,
            'content' => $this->content,
            'author_id' => $author->id,
            'author_type' => get_class($author),
            'depth' => empty($this->parent_id) ? 0 : 1,
        ]);

        $this->open = false;
        $this->reset('content');
        $this->dispatch('reply-created');
        $this->toast(type: 'success', title: 'Resposta enviada!');
    }

    #[On('teacher::close-reply-forum-topic')]
    public function cancel()
    {
        $this->open = false;
        $this->reset('content');
    }
};

?>

<x-mary-modal wire:model="open" title="Nova resposta" size="lg">
                <form wire:submit.prevent="save" id="form-reply-topic" class="space-y-4">
                    <x-mary-textarea
                        wire:model="content"
                        placeholder="Digite sua resposta..."
                        rows="6"
                        class="textarea textarea-bordered w-full !outline-none"
                    />

                    <div class="flex justify-end space-x-2">
                        <button type="button" id="btn-cancel-reply" class="btn btn-sm btn-ghost" wire:click="cancel">Cancelar</button>
                        <button type="submit" id="btn-save-reply" class="btn btn-sm btn-primary">Responder</button>
                    </div>
                </form>
</x-mary-modal>
