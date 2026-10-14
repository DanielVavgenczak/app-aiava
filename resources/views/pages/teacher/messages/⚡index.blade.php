<?php

use Livewire\Component;
use Livewire\Attributes\{Layout, Computed,On};
use Mary\Traits\Toast;
use App\Models\Teacher\Classe;
use App\Models\Chat\Message;
use App\Events\Chat\MessageSent;
use App\Notifications\MessageNotification;

new #[Layout(name: 'layouts::teacher')] class extends Component
{
     use Toast;

    public ?string $selectedClasseId = null;
    public ?string $selectedStudentId = null;
    public string $messageContent = '';
    public bool $isGroupChat = false;
    public array $notifications = [];

    #[Computed]
    public function countNotifications($studentId)
    {
        return $this->notifications[$studentId] ?? 0;
    }
    #[Computed]
    public function countDisciplines(): int
    {
        return auth()->guard('teacher')->user()->disciplines()->count();
    }

    #[Computed]
    public function classes()
    {
        return Classe::where('teacher_id', auth()->guard('teacher')->id())
            ->with(['students'])
            ->withCount('students')
            ->has('students')
            ->get();
    }

    #[Computed]
    public function selectedClasse()
    {
        if (!$this->selectedClasseId) {
            return null;
        }

        return Classe::with('students')
            ->where('teacher_id', auth()->guard('teacher')->id())
            ->find($this->selectedClasseId);
    }

    #[Computed]
    public function students()
    {
        if (!$this->selectedClasse) {
            return collect();
        }
        return $this->selectedClasse->students;
    }

    #[Computed]
    public function messages()
    {
        if (!$this->selectedClasseId) {
            return collect();
        }

        $query = Message::forClasse($this->selectedClasseId)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc');

        // Chat privado com aluno específico
        if ($this->selectedStudentId && !$this->isGroupChat) {
            $teacherId = auth()->guard('teacher')->id();
            $teacherType = 'App\Models\Teacher\Teacher';
            $studentType = 'App\Models\Teacher\Student';

            $query->betweenUsers(
                $teacherId,
                $teacherType,
                $this->selectedStudentId,
                $studentType
            );
        }

        // Chat em grupo (toda turma)
        if ($this->isGroupChat) {
            $query->whereNull('receiver_id');
        }

        return $query->get();
    }

    public function selectClasse($classeId): void
    {
        $ownsClasse = Classe::where('teacher_id', auth()->guard('teacher')->id())
            ->whereKey($classeId)
            ->exists();

        if (!$ownsClasse) {
            $this->error('Turma não encontrada');
            return;
        }

        $this->selectedClasseId = $classeId;
        $this->selectedStudentId = null;
        $this->isGroupChat = false;
    }

    public function selectStudent($studentId): void
    {
        if (!$this->selectedClasse || !$this->selectedClasse->students()->whereKey($studentId)->exists()) {
            $this->error('Aluno não encontrado nesta turma');
            return;
        }

        $this->selectedStudentId = $studentId;
        $this->isGroupChat = false;
        if (isset($this->notifications[$studentId])) {
            $this->notifications[$studentId] = 0;
        }
    }

    public function selectGroupChat(): void
    {
        $this->isGroupChat = true;
        $this->selectedStudentId = null;
    }

    public function sendMessage(): void
    {
        if (empty(trim($this->messageContent))) {
            return;
        }

        if (!$this->selectedClasseId) {
            $this->error('Selecione uma turma');
            return;
        }

        $classe = $this->selectedClasse;

        if (!$classe) {
            $this->error('Turma não encontrada');
            return;
        }

        $receiverId = null;
        $receiverType = null;

        if (!$this->isGroupChat) {
            if (!$this->selectedStudentId
                || !$classe->students()->whereKey($this->selectedStudentId)->exists()) {
                $this->error('Destinatário inválido');
                return;
            }

            $receiverId = $this->selectedStudentId;
            $receiverType = \App\Models\Teacher\Student::class;
        }

        $message = Message::create([
            'classe_id' => $classe->id,
            'sender_id' => auth()->guard('teacher')->id(),
            'sender_type' => 'App\Models\Teacher\Teacher',
            'receiver_id' => $receiverId,
            'receiver_type' => $receiverType,
            'content' => $this->messageContent,
        ]);

        // Broadcast mensagem
        broadcast(new MessageSent($message))->toOthers();

        // Enviar notificação ao destinatário
        if ($this->isGroupChat) {
            foreach ($classe->students as $student) {
                $student->notify(new MessageNotification($message));
            }
        } elseif ($receiverId) {
            $student = \App\Models\Teacher\Student::find($receiverId);
            if ($student) {
                $student->notify(new MessageNotification($message));
            }
        }

        $this->messageContent = '';
        $this->dispatch('message-sent');
    }

    public function onMessageReceived($payload): void
    {
        // Atualiza a lista de mensagens quando recebe nova mensagem
        unset($this->messages);
    }

    public function incrementNotification($studentId): void
    {
        $count = $this->notifications[$studentId] ?? 0;
        $this->notifications[$studentId] = $count + 1;
    }

};
?>

<div class="h-[calc(100vh-8rem)]">
    <livewire:global.breadcrumb :items="[
        ['label' => 'Disciplinas', 'route' => 'teacher.discipline', 'icon' => 'o-home'],
        ['label' => 'Chat'],
    ]" />

    {{-- Header --}}
    <div class="mb-6" id="teacher-messages-header-{{ Auth::guard('teacher')->id() }}">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Chat com Alunos</h1>
                <p class="text-gray-600 mt-1">Converse com seus alunos em tempo real</p>
            </div>
        </div>
    </div>

    @if($this->countDisciplines > 0)
    <div class="grid grid-cols-12 gap-6 h-[calc(100%-2rem)]" id="teacher-messages-{{ Auth::guard('teacher')->id() }}">
        {{-- Sidebar: Lista de Turmas --}}
        <div class="col-span-3 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col" id="classes-sidebar-{{ Auth::guard('teacher')->id() }}">
            {{-- Header Sidebar --}}
            <div class="p-4 border-b border-gray-200 bg-gray-50">
                <h2 class="font-bold text-gray-900">Minhas Turmas</h2>
                {{ count($this->classes) }}
            </div>

            {{-- Lista de Turmas --}}
            <div class="flex-1 overflow-y-auto" id="classe-listing-all">
                @forelse($this->classes as $classe)
                    <button
                        id="classe-chat-{{ $classe->id }}"
                        wire:click="selectClasse('{{ $classe->id }}')"
                        class="classe-item w-full p-4 hover:bg-gray-50 border-b border-gray-100 text-left transition-colors {{ $selectedClasseId === $classe->id ? 'bg-blue-50 border-l-4 border-l-blue-600' : '' }}"
                        data-classe-id="{{ $classe->id }}"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                                {{ substr($classe->name, 0, 1) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-gray-900 truncate">{{ $classe->name }}</h3>
                                <p class="text-sm text-gray-500">{{ $classe->students_count }} alunos</p>
                            </div>
                            @if($selectedClasseId === $classe->id)
                                <x-mary-icon name="o-check-circle" class="w-5 h-5 text-blue-600"/>
                            @endif
                        </div>
                    </button>

                @empty
                    <div class="p-8 text-center">
                        <x-mary-icon name="o-user-group" class="w-12 h-12 text-gray-400 mx-auto mb-2" />
                        <p class="text-sm text-gray-500">Nenhuma turma cadastrada</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Sidebar: Lista de Alunos (quando turma selecionada) --}}
        @if($selectedClasseId)
            <div class="col-span-3 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col"
            id="classe-chat-sidebar-{{$selectedClasseId }}">
                {{-- Header --}}
                <div class="p-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="font-bold text-gray-900">Alunos da Turma</h2>
                </div>

                {{-- Botão Chat em Grupo --}}
                @if (count($this->students) > 1)

                <button
                    wire:click="selectGroupChat"
                    id="group-chat-{{ $selectedClasseId }}"
                    class="w-full p-4 hover:bg-blue-50 border-b border-blue-100 text-left transition-colors {{ $isGroupChat ? 'bg-blue-50 border-l-4 border-l-blue-400' : '' }}"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-400 rounded-full flex items-center justify-center text-white">
                            <x-mary-icon name="o-user-group" class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Chat em Grupo</h3>
                            <p class="text-xs text-gray-500"> {{  $this->selectedClasse->name  }}</p>
                        </div>
                    </div>
                </button>
                @endif
                {{-- Lista de Alunos --}}
                <div class="flex-1 overflow-y-auto" id="students-list-{{$selectedClasseId }}" x-data="notifications({{ Auth::guard('teacher')->user()}})">
                    @forelse($this->students as $student)
                        <button
                            id="student-conversation-{{ $student->id }}"
                            wire:click="selectStudent('{{ $student->id }}')"
                            class="student-chat-{{ $student->id }} student-item w-full p-4 hover:bg-gray-50 border-b border-gray-100 text-left transition-colors {{ $selectedStudentId === $student->id && !$isGroupChat ? 'bg-blue-50 border-l-4 border-l-blue-400' : '' }}"
                            data-student-id="{{ $student->id }}"
                        >
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                        @if ($student->hasAvatar())
                                            <img src="{{ $student->avatar_url }}"
                                                alt="{{ $student->full_name }}"
                                                class="w-full h-full object-cover rounded-full">
                                        @else
                                            {{ substr($student->full_name ?? $student->first_name, 0, 1) }}
                                        @endif
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-sm text-gray-900 truncate">
                                        {{ $student->full_name ?? $student->first_name }}
                                    </h3>
                                    <p class="text-xs text-gray-500 truncate">{{ $student->email }}</p>
                                    <div>
                                        @if ($this->countNotifications($student->id) > 0)
                                            <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full">
                                                {{ $this->countNotifications($student->id) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </button>
                    @empty
                        <div class="p-8 text-center">
                            <p class="text-sm text-gray-500">Nenhum aluno nesta turma</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif

        {{-- Área de Chat --}}
        <div class="{{ $selectedClasseId ? 'col-span-6' : 'col-span-9' }} bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col"
            id="chat-area-{{ $selectedClasseId }}">
            @if($selectedClasseId && ($selectedStudentId || $isGroupChat))
                {{-- Header do Chat --}}
                <div class="p-4 border-b border-gray-200 bg-gradient-to-r from-blue-600 to-purple-600 text-white">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                            @if($isGroupChat)
                                <x-mary-icon name="o-user-group" class="w-6 h-6" />
                            @else
                                <span class="font-bold text-lg">
                                    {{ substr($this->students->firstWhere('id', $selectedStudentId)?->full_name ?? 'A', 0, 1) }}
                                </span>
                            @endif
                        </div>
                        <div>
                            <h3 class="font-bold">
                                @if($isGroupChat)
                                    Chat em Grupo - {{ $this->selectedClasse->name }}
                                @else
                                    {{ $this->students->firstWhere('id', $selectedStudentId)?->full_name }}

                                @endif
                            </h3>
                            <p class="text-sm text-blue-100">
                                @if($isGroupChat)
                                    {{ $this->students->count() }} alunos
                                @else
                                    {{ $this->students->firstWhere('id', $selectedStudentId)?->email }}
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
                            class="flex {{ $message->sender_id === auth()->guard('teacher')->id() ? 'justify-end' : 'justify-start' }}"
                            wire:key="message-{{ $message->id }}"
                        >
                            <div class="max-w-[70%]">
                                {{-- Nome do remetente (em chat de grupo) --}}
                                @if($isGroupChat && $message->sender_id !== auth()->guard('teacher')->id())
                                    <p class="text-xs text-gray-500 mb-1 ml-2">{{ $message->sender_name }}</p>
                                @endif

                                {{-- Mensagem --}}
                                <div class="{{ $message->sender_id === auth()->guard('teacher')->id()
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-white text-gray-900 border border-gray-200'
                                }} rounded-2xl px-4 py-3 shadow-sm">
                                    <p class="text-sm whitespace-pre-wrap">{{ $message->content }}</p>
                                    <p class="{{ $message->sender_id === auth()->guard('teacher')->id()
                                        ? 'text-blue-100'
                                        : 'text-gray-400'
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
                <div class="p-4 border-t border-gray-200 bg-white" id="message-input-{{ Auth::guard('teacher')->id() }}" >
                    <form wire:submit.prevent="sendMessage" class="flex gap-2"
                            id="message-sender-{{ $selectedClasseId }}">
                        <input
                            type="text"
                            id="message-input"
                            wire:model="messageContent"
                            placeholder="Digite sua mensagem..."
                            class="input input-bordered flex-1 !outline-none"
                            autofocus
                        />
                        <button
                            type="submit"
                            class="btn bg-blue-400 text-white hover:bg-blue-500 border-0"
                            wire:loading.attr="disabled"
                        >
                            <x-mary-icon name="o-paper-airplane" class="w-5 h-5" />
                        </button>
                    </form>
                </div>
            @else
                {{-- Estado vazio --}}
                <div class="flex items-center justify-center h-full">
                    <div class="text-center">
                        <x-mary-icon name="o-chat-bubble-left-ellipsis" class="w-24 h-24 text-gray-300 mx-auto mb-4" />
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Selecione uma conversa</h3>
                        <p class="text-gray-500">
                            @if(!$selectedClasseId)
                                Escolha uma turma para começar
                            @else
                                Escolha um aluno ou chat em grupo
                            @endif
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @else
    <div class="flex items-center justify-center h-[calc(100%-2rem)]">
        <div class="text-center">
            <x-mary-icon name="o-chat-bubble-bottom-center" class="w-16 h-16 text-blue-500 mx-auto mb-4" />
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma Disciplina Cadastrada</h3>
            <p class="text-gray-500">Cadastre uma disciplina para usar o chat.</p>
            <a href="{{ route('teacher.discipline') }}" class="btn btn-sm bg-blue-400 text-blue-50 hover:bg-blue-500 mt-4">Cadastrar Disciplina</a>
        </div>
    </div>
    @endif

    {{-- Tour Modal --}}
    <div class="fixed inset-0 z-40 bg-gray-900/50" x-data x-show="$store.tourguide.openMai">
        <div
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-4xl sm:w-2/3 bg-white rounded-lg shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">
            <div class="flex justify-between items-center">
                <h2 class="text-sm font-normal text-gray-500">Opções do Chat</h2>
                    <x-mary-button icon="o-x-mark"
                        class="bg-white border-none btn-square text-gray-500 hover:text-blue-500"
                        x-on:click="$store.tourguide.cancelOptionsTourPage()" />
            </div>
            <div class="flex items-center gap-3 p-4 bg-gray-50">
                @if ($this->countDisciplines == 0)
                    <div class="flex flex-col items-center gap-2 mx-auto">
                        <x-mary-icon name="o-user-group" class="w-6 h-6 text-gray-400" />
                        <p class="text-sm text-gray-500">Você ainda não possui disciplina cadastrada.</p>
                        <button class="btn btn-sm font-medium text-blue-500 border-[1px] border-blue-500 hover:bg-blue-400 hover:border-0 hover:text-blue-100" x-on:click="$store.tourguide.start('StartTourTeacherMessageToDiscipline')">Cadastrar disciplina</button>
                    </div>
                @else
                    @if (count($this->classes) === 0)
                        <div class="flex flex-col items-center gap-2 mx-auto">
                            <x-mary-icon name="o-user-group" class="w-6 h-6 text-gray-400" />
                            <p class="text-sm text-gray-500">Você ainda não possui turmas cadastradas.</p>
                            <button
                                class="btn btn-sm font-medium text-blue-500 border-[1px] border-blue-500 hover:bg-blue-400 hover:border-0 hover:text-blue-100" x-on:click="$store.tourguide.start('StartTourTeacherMessageToClasse')">Cadastrar Turma</button>
                        </div>
                    @else
                        <x-mary-button
                        class="font-medium text-blue-500 border-[1px] border-blue-500 btn hover:bg-blue-400 hover:border-0 hover:text-blue-100"
                        label="Iniciar uma conversa" x-on:click="$store.tourguide.start('StartTourInitChatInClasse')" />
                    @endif
                @endif
            </div>
        </div>
        <img src="{{ asset('images/rb-aiava.png') }}" class="fixed right-0 w-64 bottom-4 sm:w-48" >
    </div>
</div>

@script
<script>
    // Auto-scroll quando nova mensagem chega
    $wire.on('message-sent', () => {
        const container = document.getElementById('messages-container');
        if (container) {
            setTimeout(() => {
                container.scrollTop = container.scrollHeight;
            }, 100);
        }
    });

    // Escutar mensagens em tempo real via Echo
    if (window.Echo) {
        const teacherId = @js(auth()->guard('teacher')->id());
        // Canal privado do professor
        Echo.private(`chat-teacher-student.${teacherId}`)
            .listen('.message.sent', (e) => {
                $wire.onMessageReceived(e);
                // Auto-scroll
                const container = document.getElementById('messages-container');
                if (container) {
                    setTimeout(() => {
                        container.scrollTop = container.scrollHeight;
                    }, 100);
                }
            }).notification((notification) => {
                if(notification.classe_id === $wire.selectedClasseId && notification.sender_id) {
                    $wire.incrementNotification(notification.sender_id);
                }
            });
    }
</script>
@endscript
