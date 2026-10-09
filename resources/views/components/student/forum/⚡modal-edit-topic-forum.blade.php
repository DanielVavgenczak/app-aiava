<?php
// resources/views/livewire/pages/student/forum/components/modal-edit-topic.blade.php

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

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    public function getCurrentUser()
    {
        return Auth::guard('teacher')->user() ?? Auth::guard('student')->user();
    }

    #[On('student::open-edit-topic-forum')]
    public function openModal($topicId): void
    {
        $user = $this->getCurrentUser();
        $guard = Auth::guard('teacher')->check() ? 'teacher' : 'student';

        $query = ForumTopic::where('id', $topicId);
        if ($guard === 'teacher') {
            $query->whereHas('discipline', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            });
        } elseif ($guard === 'student') {
            $query->whereHas('discipline', function ($q) use ($user) {
                $q->where('id', $user->discipline_id);
            });
        }
        $this->topic = $query->first();

        if ($this->topic && $user?->can('update', $this->topic)) {
            $this->title = $this->topic->title;
            $this->description = $this->topic->description ?? '';
            $this->open = true;
        } else {
            $this->reset(['title', 'description', 'topic']);
            $this->open = false;
            $this->error('Você não tem permissão para editar este tópico.');
        }
    }

    public function update(): void
    {
        $this->validate();

        if (!$this->topic || !$this->getCurrentUser()?->can('update', $this->topic)) {
            $this->error('Você não tem permissão para editar este tópico.');
            return;
        }

        $this->topic->update([
            'title' => $this->title,
            'description' => $this->description,
        ]);

        $this->close();
        $this->success('Tópico atualizado com sucesso!');

        // ✅ Notifica componente pai para recarregar
        $this->dispatch('forum-event', type: 'update', msg: 'Tópico atualizado!');
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['title', 'description', 'topic']);
    }
}; ?>

<div>
    <x-mary-modal wire:model="open" title="Editar Tópico" persistent class="backdrop-blur-sm">
        <form wire:submit="update" class="space-y-4">
            {{-- Título --}}
            <div>
                <x-mary-input
                    wire:model="title"
                    label="Título do Tópico"
                    icon="o-pencil-square"
                    placeholder="Digite o título..."
                    hint="3-255 caracteres"
                />
            </div>

            {{-- Descrição --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Descrição (Opcional)
                </label>
                <textarea
                    wire:model="description"
                    rows="5"
                    class="textarea textarea-bordered w-full focus:border-pink-500 focus:ring-2 focus:ring-pink-200"
                    placeholder="Descreva melhor seu tópico..."
                ></textarea>
                <div class="flex justify-between items-center mt-1">
                    <p class="text-xs text-gray-500">Máximo 1000 caracteres</p>
                    <p class="text-xs text-gray-500">{{ strlen($description) }}/1000</p>
                </div>
                @error('description')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Info de última edição --}}
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

            <x-slot:actions>
                <x-mary-button
                    label="Cancelar"
                    @click="$wire.close()"
                    icon="o-x-mark"
                />

                <x-mary-button
                    label="Salvar Alterações"
                    class="btn-primary"
                    type="submit"
                    spinner="update"
                    icon="o-check-circle"
                />
            </x-slot:actions>
        </form>
    </x-mary-modal>
</div>
