<?php

use Livewire\Component;
use Livewire\Attributes\{Layout, Computed, On};
use Mary\Traits\Toast;
use App\Models\Teacher\Classe;
use App\Models\Teacher\Student;
use App\Models\Chat\Message;
use App\Events\Chat\MessageSent;
new #[Layout(name: 'layouts::student')] class extends Component
{
    use Toast;

    public ?string $selectedRecipientId = null;
    public ?string $selectedRecipientType = null;
    public string $messageContent = '';
    public bool $isGroupChat = true; // Aluno começa no chat de grupo

    #[Computed]
    public function student()
    {
        return Student::with(['classe.teacher', 'classe.students'])
            ->findOrFail(auth()->guard('student')->id());
    }

    #[Computed]
    public function classe()
    {
        return $this->student->classe;
    }

    #[Computed]
    public function teacher()
    {
        return $this->classe?->teacher;
    }

    #[Computed]
    public function classmates()
    {
        if (!$this->classe) {
            return collect();
        }

        return $this->classe->students()
            ->where('id', '!=', auth()->guard('student')->id())
            ->get();
    }

    #[Computed]
    public function messages()
    {
        if (!$this->classe) {
            return collect();
        }

        $query = Message::forClasse($this->classe->id)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc');

        // Chat em grupo
        if ($this->isGroupChat) {
            $query->whereNull('receiver_id');
        }
        // Chat privado
        else if ($this->selectedRecipientId) {
            $studentId = auth()->guard('student')->id();
            $studentType = 'App\Models\Teacher\Student';

            $query->betweenUsers(
                $studentId,
                $studentType,
                $this->selectedRecipientId,
                $this->selectedRecipientType
            );
        }

        return $query->get();
    }

    public function selectGroupChat(): void
    {
        $this->isGroupChat = true;
        $this->selectedRecipientId = null;
        $this->selectedRecipientType = null;
    }

    public function selectTeacher(): void
    {
        $this->isGroupChat = false;
        $this->selectedRecipientId = $this->teacher->id;
        $this->selectedRecipientType = 'App\Models\Teacher\Teacher';
    }

    public function selectClassmate($classmateId): void
    {
        $this->isGroupChat = false;
        $this->selectedRecipientId = $classmateId;
        $this->selectedRecipientType = 'App\Models\Teacher\Student';
    }

    public function sendMessage(): void
    {
        if (empty(trim($this->messageContent))) {
            return;
        }

        if (!$this->classe) {
            $this->error('Você não está em nenhuma turma');
            return;
        }

        $message = Message::create([
            'classe_id' => $this->classe->id,
            'sender_id' => auth()->guard('student')->id(),
            'sender_type' => 'App\Models\Teacher\Student',
            'receiver_id' => $this->isGroupChat ? null : $this->selectedRecipientId,
            'receiver_type' => $this->isGroupChat ? null : $this->selectedRecipientType,
            'content' => $this->messageContent,
        ]);

        broadcast(new MessageSent($message))->toOthers();

        $this->messageContent = '';
        $this->dispatch('message-sent');
    }
    public function onMessageReceived($payload): void
    {
        unset($this->messages);
    }


};
?>

<div class="h-[calc(100vh-8rem)]">
    {{-- Breadcrumb --}}
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm p-4">
            <a href="{{ route('student.index') }}" wire:navigate class="text-gray-500 hover:text-green-700 transition-colors">
                <x-mary-icon name="o-home" class="w-4 h-4" />
            </a>
            <x-mary-icon name="o-chevron-right" class="w-3 h-3 text-gray-400" />
            <span class="text-green-700 font-medium">Chat</span>
        </div>
    </div>

    {{-- Header --}}
    <div class="mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Chat da Turma</h1>
            <p class="text-gray-600 mt-1">Converse com seu professor e colegas</p>
        </div>
    </div>

    @if($this->classe)
        <div class="grid grid-cols-12 gap-6 h-[calc(100%-8rem)]">
            {{-- Sidebar: Contatos --}}
            <div class="col-span-3 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                <div class="p-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="font-bold text-gray-900">Conversas</h2>
                </div>

                <div class="flex-1 overflow-y-auto">
                    {{-- Chat em Grupo --}}
                    <button
                        wire:click="selectGroupChat"
                        class="w-full p-4 hover:bg-gray-50 border-b border-gray-100 text-left transition-colors {{ $isGroupChat ? 'bg-green-50 border-l-4 border-l-green-600' : '' }}"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-blue-600 rounded-full flex items-center justify-center text-white">
                                <x-mary-icon name="o-user-group" class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900">Turma {{ $this->classe->name }}</h3>
                                <p class="text-xs text-gray-500">Chat em grupo</p>
                            </div>
                        </div>
                    </button>

                    {{-- Professor --}}
                    @if($this->teacher)
                        <button
                            wire:click="selectTeacher"
                            class="w-full p-4 hover:bg-gray-50 border-b border-gray-100 text-left transition-colors {{ !$isGroupChat && $selectedRecipientId === $this->teacher->id ? 'bg-blue-50 border-l-4 border-l-blue-600' : '' }}"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                                    {{ substr($this->teacher->full_name ?? $this->teacher->first_name, 0, 1) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-sm text-gray-900 truncate">
                                        Prof. {{ $this->teacher->full_name ?? $this->teacher->first_name }}
                                    </h3>
                                    <p class="text-xs text-gray-500">Professor</p>
                                </div>
                            </div>
                        </button>
                    @endif

                    {{-- Colegas de Turma --}}
                    @if($this->classmates->count() > 0)
                        <div class="p-3 bg-gray-50 border-b border-gray-200">
                            <p class="text-xs font-semibold text-gray-600 uppercase">Colegas</p>
                        </div>

                        @foreach($this->classmates as $classmate)
                            <button
                                wire:click="selectClassmate('{{ $classmate->id }}')"
                                class="w-full p-4 hover:bg-gray-50 border-b border-gray-100 text-left transition-colors {{ !$isGroupChat && $selectedRecipientId === $classmate->id ? 'bg-blue-50 border-l-4 border-l-blue-600' : '' }}"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                        {{ substr($classmate->full_name ?? $classmate->first_name, 0, 1) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-semibold text-sm text-gray-900 truncate">
                                            {{ $classmate->full_name ?? $classmate->first_name }}
                                        </h3>
                                        <p class="text-xs text-gray-500 truncate">{{ $classmate->email }}</p>
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    @endif
                </div>
            </div>

            {{-- Área de Chat --}}
            <div class="col-span-9 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                {{-- Header do Chat --}}
                <div class="p-4 border-b border-gray-200 bg-gradient-to-r from-blue-700 to-blue-600 text-white">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                            @if($isGroupChat)
                                <x-mary-icon name="o-user-group" class="w-6 h-6" />
                            @else
                                <span class="font-bold text-lg">
                                    @if($selectedRecipientType === 'App\Models\Teacher\Teacher')
                                        {{ substr($this->teacher->full_name ?? 'P', 0, 1) }}
                                    @else
                                        {{ substr($this->classmates->firstWhere('id', $selectedRecipientId)?->full_name ?? 'A', 0, 1) }}
                                    @endif
                                </span>
                            @endif
                        </div>
                        <div>
                            <h3 class="font-bold">
                                @if($isGroupChat)
                                    Chat em Grupo - Turma {{ $this->classe->name }}
                                @elseif($selectedRecipientType === 'App\Models\Teacher\Teacher')
                                    Prof. {{ $this->teacher->full_name ?? $this->teacher->first_name }}
                                @else
                                    {{ $this->classmates->firstWhere('id', $selectedRecipientId)?->full_name }}
                                @endif
                            </h3>
                            <p class="text-sm text-green-100">
                                @if($isGroupChat)
                                    {{ $this->classmates->count() + 1 }} participantes
                                @else
                                    Chat privado
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Mensagens --}}
                <div
                    class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50"
                    id="messages-container"
                    x-data
                    x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
                >
                    @forelse($this->messages as $message)
                        <div
                            class="flex {{ $message->sender_id === auth()->guard('student')->id() ? 'justify-end' : 'justify-start' }}"
                            wire:key="message-{{ $message->id }}"
                        >
                            <div class="max-w-[70%]">
                                {{-- Nome do remetente --}}
                                @if($isGroupChat && $message->sender_id !== auth()->guard('student')->id())
                                    <p class="text-xs text-gray-500 mb-1 ml-2">
                                        {{ $message->sender_name }}
                                        @if($message->isSentByTeacher())
                                            <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 text-xs font-semibold rounded">Prof</span>
                                        @endif
                                    </p>
                                @endif

                                {{-- Mensagem --}}
                                <div class="{{ $message->sender_id === auth()->guard('student')->id()
                                    ? 'bg-blue-200 text-gray-800'
                                    : ($message->isSentByTeacher() ? 'bg-blue-600 text-white' : 'bg-white text-gray-900 border border-gray-200')
                                }} rounded-2xl px-4 py-3 shadow-sm">
                                    <p class="text-sm whitespace-pre-wrap">{{ $message->content }}</p>
                                    <p class="{{ $message->sender_id === auth()->guard('student')->id()
                                        ? 'text-gray-600'
                                        : ($message->isSentByTeacher() ? 'text-blue-100' : 'text-gray-800')
                                    }} text-xs mt-1">
                                        {{ $message->created_at->format('H:i') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="flex items-center justify-center h-full">
                            <div class="text-center">
                                <x-mary-icon name="o-chat-bubble-left-right" class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                                <p class="text-gray-500">Nenhuma mensagem ainda</p>
                                <p class="text-sm text-gray-400">Envie a primeira mensagem!</p>
                            </div>
                        </div>
                    @endforelse
                </div>

                {{-- Input de Mensagem --}}
                <div class="p-4 border-t border-gray-200 bg-white">
                    <form wire:submit.prevent="sendMessage" class="flex gap-2">
                        <input
                            type="text"
                            wire:model="messageContent"
                            placeholder="Digite sua mensagem..."
                            class="input input-bordered flex-1 !outline"
                            autofocus
                        />
                        <button
                            type="submit"
                            class="btn bg-green-600 text-white hover:bg-green-700 border-0"
                            wire:loading.attr="disabled"
                        >
                            <x-mary-icon name="o-paper-airplane" class="w-5 h-5" />
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @else
        {{-- Sem turma --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-16 text-center">
            <x-mary-icon name="o-exclamation-triangle" class="w-16 h-16 mx-auto text-yellow-500 mb-4" />
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Você não está em nenhuma turma</h3>
            <p class="text-gray-500">Entre em contato com seu professor para ser adicionado a uma turma.</p>
        </div>
    @endif
</div>

@script
<script>
     $wire.on('message-sent', (e) => {
        console.log('verificando o evento que nos bate',e)
        const container = document.getElementById('messages-container');
        if (container) {
            setTimeout(() => {
                container.scrollTop = container.scrollHeight;
            }, 50);
        }
    });

    // Escutar mensagens em tempo real via Echo
    if (window.Echo) {
        console.log('Echo disponível, configurando listeners...');
        const studentId = @js(auth()->guard('student')->id());
        console.log('ID do estudante:', studentId);
        // Canal privado do professor
        Echo.private(`chat-teacher-student.${studentId}`)
            .listen('.message.sent', (e) => {
                console.log('Nova mensagem recebida:', e);
                $wire.onMessageReceived(e);
                // Auto-scroll
                const container = document.getElementById('messages-container');
                if (container) {
                    setTimeout(() => {
                        container.scrollTop = container.scrollHeight;
                    }, 50);
                }
            }).listenForWhisper('type', (e) => {
                console.log('tipo aqui para ver', e)
            })
            ;
    }
</script>
@endscript
