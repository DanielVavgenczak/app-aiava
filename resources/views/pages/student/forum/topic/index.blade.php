<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Forum\ForumTopic;

new #[Layout(name: 'layouts.student-panel')] class extends Component {

    public $topic_id;
    public $topic;
    public function mount(string $id)
    {
        $this->topic_id = $id;
        // Lógica de inicialização, se necessário
        $this->getTopic();
    }

    protected function getTopic()
    {
        $this->topic = ForumTopic::where('id', $this->topic_id)
        ->with('questions')
        ->first();
    }
}; ?>

<div>
    <div class="p-5">

    </div>
    <div class="p-5  bg-white flex flex-col gap-4">
        <header>
            {{ $topic->title }}
        </header>
        <div>
            Lista as questoes dos topicos
        </div>
    </div>
</div>
