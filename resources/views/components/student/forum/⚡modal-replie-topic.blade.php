<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Forum\ForumReplie;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public bool $open = false;
    public string $topic_id = '';
    public string $parent_id = '';
    public string $content = '';

    #[On('student::open-reply-modal')]
    public function open(string $topic_id, ?string $parentId = null)
    {
        $this->reset(['content', 'parent_id']);
        $this->topic_id = $topic_id;
        $this->parent_id = $parentId ?? '';
        $this->open = true;
    }

    public function save()
    {
        $this->validate([
            'content' => 'required|min:3|max:5000',
        ]);

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

    public function cancel()
    {
        $this->open = false;
        $this->reset('content');
    }
};

?>

<x-mary-modal wire:model="open" title="Nova resposta" size="lg">
    <form wire:submit.prevent="save" class="space-y-4">
        <textarea
            wire:model="content"
            placeholder="Digite sua resposta..."
            rows="6"
            class="textarea textarea-bordered w-full !outline-none"
        ></textarea>

        <div class="flex justify-end space-x-2">
            <button type="button" class="btn btn-ghost" wire:click="cancel">Cancelar</button>
            <button type="submit" class="btn btn-primary">Enviar</button>
        </div>
    </form>
</x-mary-modal>
