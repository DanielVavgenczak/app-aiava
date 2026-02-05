<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Validate, On};
use App\Models\Forum\{QuestionTopic};

new class extends Component {
    public $open = false;
    public $loged;
    /** TODO: Verificar a nomelatura se vai ser Perguntas e Respostas ou outra coisa **/
    #[Validate('required', message: 'O campo respotas  topico é obrigatório0!')]
    public $content = '';
    public $topic_id = '';
    #[On('open-modal-new-question-topic')]
    public function open($topic_id)
    {
        $this->open = true;
        $this->topic_id = $topic_id;
    }

    public function store()
    {
        $this->validate([
            'content' => 'required|string|min:3',
        ]);

        $isTeacher = Auth::guard('teacher')->check();
        QuestionTopic::create([
            'content' => $this->content,
            'teacher_id' => $isTeacher ? Auth::guard('teacher')->id() : null,
            'student_id' => !$isTeacher ? Auth::guard('student')->id() : null,
            'forum_topic_id' => $this->topic_id,
        ]);

        $this->open = false;
    }

}; ?>
<x-mary-modal wire:model="open" title="Nova questão aqui no forum">
    <div>
        <form wire:submit.prevent="store" class="flex flex-col gap-3">
            <div>
                <x-mary-textarea wire:model="content" label="Nova questão" class="class-input-name !outline-none"
                    autocomplete="content" />
            </div>
            <div class="flex justify-end">
                <button class="btn btn-md bg-pink-700 text-gray-50">Criar</button>
            </div>

        </form>
    </div>

</x-mary-modal>
