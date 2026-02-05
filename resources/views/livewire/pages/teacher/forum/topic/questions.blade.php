<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout};
use Illuminate\Support\Collection;
use App\Models\Forum\{ForumTopic, QuestionTopic};

new #[Layout('layouts.app')] class extends Component {

    public Collection $questions;
    public $topic_id;
    public $topic;

    public function mount($id)
    {
        $this->topic_id = $id;
        $this->loadTopic($id); // Carrega o tópico primeiro
        $this->loadQuestions($id);
    }

    protected function loadTopic($topic_id)
    {
        $this->topic = ForumTopic::find($topic_id);
    }

    protected function loadQuestions($topic_id)
    {
        $this->questions = QuestionTopic::with(['ForumTopic'])
            ->where('forum_topic_id', $topic_id)
            ->orderBy('created_at', 'desc') // Adicione ordenação se quiser
            ->get();
    }

}; ?>

<div>
    <div class="py-4">
        <a href="{{ url()->previous() }}" wire:navigate class="flex items-center gap-1 hover:opacity-80 transition">
            <x-mary-icon name="o-arrow-left" class="w-4 text-gray-500" />
            <span class="text-xs font-semibold tracking-wider text-gray-500 uppercase">voltar</span>
        </a>
    </div>

    <div class="bg-white p-5 rounded-lg shadow">
        <!-- Título do tópico -->
        <div class="mb-6 pb-4 border-gray-200 border-b border-b-[1px] flex justify-between">
            @if($topic)
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">
                        {{ $topic->title }}
                    </h1>
                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                        <span class="flex items-center gap-1">
                            <x-mary-icon name="o-question-mark-circle" class="w-4" />
                            {{ $questions->count() }} questão(ões)
                        </span>
                        <span class="flex items-center gap-1">
                            <x-mary-icon name="o-calendar" class="w-4" />
                            Postado em: {{ $topic->created_at->format('d/m/Y') }}
                        </span>
                    </div>
                </div>
                    @else
                <div class="alert alert-warning">
                    <x-mary-icon name="o-exclamation-triangle" class="w-6" />
                    <span>Tópico não encontrado</span>
                </div>
            @endif
            <div>
                <button
                        @click="$dispatch('open-modal-new-question-topic', { topic_id: '{{ $topic->id }}' })"
                        class="btn btn-sm mt-4"
                    >
                        Nova questão
                    </button>
            </div>
            <livewire:pages.teacher.forum.modals.new-questions/>
        </div>

        <!-- Listagem de questões -->
        @if ($questions->count() > 0)
            <div class="space-y-4">
                @foreach($questions as $question)
                    <div class="p-2 bg-gray-100">
                        <div class="">
                            <div class="flex gap-1 items-center mb-4">
                               <div class="bg-gray-200 rounded-full w-8 h-8"></div>
                               <h4 class="text-sm text-gray-600">Nome do autor</h4>
                            </div>
                            <p class="text-sm text-gray-700 p-2">{{ $question->content }}</p>
                        </div>
                        <div>
                            <span class="text-xs ">Postado em: {{ $question->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Estado vazio -->
            <div class="text-center py-12">
                <x-mary-icon name="o-document-text" class="w-8 h-8 text-gray-700 mx-auto mb-4" />
                <h4 class="text-lg font-medium text-gray-700 mb-2">
                    Nenhuma questão cadastrada ainda
                </h4>
                <p class="text-gray-500 mb-6">
                    Este tópico ainda não possui    . Seja o primeiro a adicionar uma!
                </p>
            </div>
        @endif


    </div>
</div>
