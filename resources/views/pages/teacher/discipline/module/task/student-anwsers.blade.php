<?php

use Livewire\Component;
use Livewire\Attributes\{On, Locked};
use App\Models\Teacher\{StudentAnwser, QuestionFeedback, Task};
use Illuminate\Support\Facades\{Auth, DB};
use Mary\Traits\Toast;
new class extends Component {
    use Toast;
    #[Locked]
    public $studentAnswer;
    public string $selectedTab = 'to-correct-tab';
    public bool $show = false;
    #[Locked]
    public string $task_id = '';
    public array $feedbacks = [];

    #[On('event-teacher-show-student-answer')]
    public function open($task_id)
    {
        $teacher = Auth::guard('teacher')->user();

        $task = Task::query()
            ->where('id', $task_id)
            ->whereHas('activitTask', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher?->id)
                    ->whereHas('discipline', function ($disciplineQuery) use ($teacher) {
                        $disciplineQuery->where('teacher_id', $teacher?->id);
                    });
            })
            ->first();

        if (! $task) {
            $this->reset(['studentAnswer', 'task_id', 'feedbacks']);
            $this->show = false;
            $this->error('Tarefa não encontrada ou você não tem permissão para acessá-la.');
            return;
        }

        $this->task_id = $task->id;
        $this->studentAnswer = StudentAnwser::where('task_id', $task->id)
            ->orderBy('created_at')
            ->get();
        $this->show = true;
    }

    public function sendFeedback(string $studentAnswerId)
    {
        $this->validate(
            [
                "feedbacks.$studentAnswerId" => 'required|string|min:6|max:1000',
            ],
            [
                "feedbacks.$studentAnswerId.required" => 'O feedback é obrigatório.',
                "feedbacks.$studentAnswerId.min" => 'O feedback deve ter pelo menos :min caracteres.',
                "feedbacks.$studentAnswerId.max" => 'O feedback deve ter no máximo :max caracteres.',
            ],
        );

        $teacher = Auth::guard('teacher')->user();

        // Nunca confiar no ID enviado pelo cliente: a resposta precisa pertencer
        // à tarefa carregada e a tarefa precisa pertencer ao professor logado.
        $answer = StudentAnwser::query()
            ->where('id', $studentAnswerId)
            ->where('task_id', $this->task_id)
            ->whereHas('task', function ($query) use ($teacher) {
                $query->whereHas('activitTask', function ($activitQuery) use ($teacher) {
                    $activitQuery->where('teacher_id', $teacher?->id)
                        ->whereHas('discipline', function ($disciplineQuery) use ($teacher) {
                            $disciplineQuery->where('teacher_id', $teacher?->id);
                        });
                });
            })
            ->first();

        if (! $answer) {
            $this->addError("feedbacks.$studentAnswerId", 'Resposta inválida ou sem permissão.');
            return;
        }

        $feedback = $this->feedbacks[$studentAnswerId] ?? null;
        if (empty($feedback)) {
            return;
        }

        try {
            DB::transaction(function () use ($answer, $feedback) {
                QuestionFeedback::create([
                    'student_anwser_id' => $answer->id,
                    'task_id' => $answer->task_id,
                    'student_id' => $answer->student_id,
                    'feedback' => $feedback,
                    'teacher_id' => Auth::guard('teacher')->user()->id,
                ]);

                StudentAnwser::where('student_id', $answer->student_id)
                    ->where('task_id', $answer->task_id)
                    ->update(['corrected' => true]);
            });
        } catch (\Exception $e) {
            $this->addError("feedbacks.$studentAnswerId", $e->getMessage());
            return;
        }

        unset($this->feedbacks[$studentAnswerId]);
        $this->success('Feedback enviado com sucesso.');
    }
}; ?>

<x-mary-modal wire:model="show" box-class="w-full max-w-4xl">
    @if ($studentAnswer)
        <x-mary-tabs wire:model="selectedTab">
            <x-mary-tab name="to-correct-tab" label="Correções" icon="o-exclamation-triangle">
                @foreach ($studentAnswer as $answer)
                    @if (!$answer->corrected)
                        <div x-on:click="$dispatch('event-teacher-show-student-answer', {task_id: '{{ $answer->task_id }}'})"
                            class="p-4 bg-white rounded-md shadow-sm  cursor-pointer transition-all ease-in-out">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    {{-- asset('storage/' . $answer->student->profile->avatar ?? '') ?? --}}
                                    <div class="flex-shrink-0">
                                        <img class="h-12 w-12 rounded-full"
                                            src="{{ asset('images/user-noavatar.png') }}" alt="Aluno">
                                    </div>
                                    <div class="flex flex-col">
                                        <h3 class="text-gray-800 font-semibold ">
                                            {{ $answer->student->full_name }}
                                        </h3>
                                        <p class=" text-gray-600 mb-2 uppercase font-bold text-xs">
                                            {{ $answer->created_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-100 p-5 flex flex-col gap-2.5 text-gray-700" x-data="expandeText(`{{ $answer->answer_text }}`)">
                                <div>
                                    <p class="text-sm tracking-wide text-gray-700" x-text="truncatedText" x-transition>
                                    </p>
                                    <button x-show="shouldShowToggle" x-on:click="toggleText()"
                                        class="text-blue-500 hover:underline text-sm mt-2">
                                        <span class="font-semibold tracking-wide"
                                            x-text="showFull ? 'Mostrar menos' : 'Mostrar mais'"></span>
                                    </button>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <x-mary-textarea class="!outline-none border-0" placeholder="Insira o feedback"
                                        wire:model="feedbacks.{{ $answer->id }}" />
                                    <div class="flex justify-end gap-2 items-center">

                                        <x-mary-input class="!outline-none border-0"
                                            placeholder="Insira a nota ex: 1,0" />
                                        <button class="btn btn-sm btn-ghost mt-2 self-end bg-gray-200"
                                            wire:loading.attr="disabled"
                                            x-on:click.prevent="$wire.sendFeedback('{{ $answer->id }}')">
                                            Finalizar correção
                                            <x-mary-loading wire:loading
                                                wire:target="sendFeedback('{{ $answer->id }}')" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </x-mary-tab>
            <x-mary-tab name="corrected-tab" label="Corrigidas" icon="o-check">
                <div>
                    @foreach ($studentAnswer as $answer)
                        @if ($answer->corrected)
                            <div
                                class="p-4 border-[1px] border-gray-200 rounded-md  my-2 cursor-pointer transition-all ease-in-out">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-3">
                                        {{-- asset('storage/' . $answer->student->profile->avatar ?? '') ?? --}}
                                        <div class="flex-shrink-0">
                                            <img class="h-12 w-12 rounded-full"
                                                src="{{ asset('images/user-noavatar.png') }}" alt="Aluno">
                                        </div>
                                        <div class="flex flex-col">
                                            <h3 class="text-gray-800 font-semibold ">
                                                {{ $answer->student->full_name }}
                                            </h3>
                                            <p class=" text-gray-600 mb-2 uppercase font-bold text-xs">
                                                {{ $answer->created_at->format('d/m/Y H:i') }}</p>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        @endif
                    @endforeach
                </div>
            </x-mary-tab>
        </x-mary-tabs>
    @endif
</x-mary-modal>
