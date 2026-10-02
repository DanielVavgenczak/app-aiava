<?php

use Livewire\Component;
use Livewire\Attributes\{On, Validate};
use App\Models\Forum\ForumTopic;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public bool $open = false;
    public ?ForumTopic $topic = null;

    #[Validate('required|string|min:3|max:255', message: 'O título é obrigatório (3-255 caracteres)')]
    public string $title = '';

    #[On('teacher::open-edit-topic-forum')]
    public function openModal($topicId): void
    {
        $this->topic = ForumTopic::find($topicId);

        if ($this->topic && Auth::guard('teacher')->user()?->can('update', $this->topic)) {
            $this->title = $this->topic->title;
            $this->open = true;
        } else {
            $this->error('Você não tem permissão para editar este tópico.');
        }
    }

    public function update(): void
    {
        if (!$this->topic || !Auth::guard('teacher')->user()?->can('update', $this->topic)) {
            $this->error('Você não tem permissão para editar este tópico.');
            return;
        }

        $this->validate();

        $this->topic->update([
            'title' => $this->title,
        ]);

        $this->close();
        $this->success('Tópico atualizado com sucesso!');

        $this->dispatch('forum-event', type: 'update', msg: 'Tópico atualizado!');
    }

    #[On('teacher::close-edit-forum-topic')]
    public function close(): void
    {
        $this->open = false;
        $this->reset(['title', 'topic']);
    }
}; ?>

<div>
    <x-mary-modal wire:model="open" title="Editar Tópico" persistent class="backdrop-blur-sm">
        <form id="form-edit-topic" wire:submit.prevent="update" class="space-y-4">
            <div id="input-edit-topic">
                <div>
                    <x-mary-input
                        wire:model="title"
                        class="!outline-none"
                        label="Título do Tópico"
                        icon="o-pencil-square"
                        placeholder="Digite o título..."
                        hint="3-255 caracteres"
                    />
                </div>

            @if($topic)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <div class="flex items-start gap-2">
                        <x-mary-icon name="o-information-circle" class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                        <div class="text-sm text-blue-800">
                            <p class="font-medium">Criado em: {{ $topic->created_at->format('d/m/Y H:i') }}</p>
                            @if($topic->updated_at != $topic->created_at)
                                <p class="mt-1">Última edição: {{ $topic->updated_at->diffForHumans() }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex justify-end items-center gap-2">
                <button type="button" class="btn btn-sm btn-ghost"
                    id="btn-cancel-edit"
                    x-on:click="$wire.close()">
                    Cancelar</button>

                    <button type="submit" class="btn btn-sm bg-blue-400  hover:*:bg-blue-500 text-white"
                    id="btn-save-edit"
                    x-on:click="$wire.update()">
                    Salvar Alterações</button>

            </div>
        </form>
    </x-mary-modal>
</div>
