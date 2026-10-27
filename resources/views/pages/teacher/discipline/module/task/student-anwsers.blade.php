<?php

use Livewire\Component;
use Livewire\Attributes\{On, Locked};
use App\Models\Teacher\{ActivitTask, StudentAnwser, QuestionFeedback, Task};
use App\Support\{ActivityNotificationService, ActivityValue};
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
    public array $grades = [];
    /** Feedbacks já lançados, indexados pelo id da resposta (para exibição). */
    #[Locked]
    public array $savedFeedbacks = [];
    /** Soma das notas corrigidas por aluno (id do aluno => soma). */
    #[Locked]
    public array $studentTotals = [];
    /** Valor total configurado para a atividade (null se não avaliativa). */
    #[Locked]
    public $activityTotalValue = null;

    #[On('event-teacher-show-student-answer')]
    public function open($task_id)
    {
        $teacher = Auth::guard('teacher')->user();

        $task = Task::query()
            ->with('activitTask')
            ->whereKey($task_id)
            ->ownedByTeacher($teacher)
            ->first();

        if (! $task) {
            $this->reset(['studentAnswer', 'task_id', 'feedbacks', 'grades', 'savedFeedbacks', 'studentTotals', 'activityTotalValue']);
            $this->show = false;
            $this->error('Tarefa não encontrada ou você não tem permissão para acessá-la.');
            return;
        }

        // Ao reabrir a MESMA tarefa, preserva o que o professor já digitou
        // (notas/feedbacks em preenchimento) em vez de descartar.
        $isSameTask = $this->task_id === $task->id;

        $this->task_id = $task->id;
        $this->studentAnswer = StudentAnwser::with(['student', 'answerOption', 'task'])
            ->where('task_id', $task->id)
            ->orderBy('created_at')
            ->get();

        $this->savedFeedbacks = QuestionFeedback::whereIn('student_anwser_id', $this->studentAnswer->pluck('id'))
            ->orderBy('created_at')
            ->get()
            ->keyBy('student_anwser_id')
            ->map(fn (QuestionFeedback $feedback) => $feedback->feedback)
            ->toArray();

        $this->activityTotalValue = $task->activitTask?->total_value;

        $this->studentTotals = $this->studentAnswer
            ->filter(fn (StudentAnwser $answer) => (bool) $answer->corrected && $answer->grade !== null)
            ->groupBy('student_id')
            ->map(fn ($answers) => $answers->sum(fn (StudentAnwser $answer) => (float) $answer->grade))
            ->toArray();

        if (! $isSameTask) {
            $this->grades = [];
            $this->feedbacks = [];
        }

        $this->show = true;
    }

    /** Formata a nota para exibição pt-BR. */
    public function formatGrade($grade): string
    {
        return ActivityValue::format($grade);
    }

    public function sendFeedback(string $studentAnswerId)
    {
        // Carrega a resposta já validando posse antes de validar a nota, para
        // que o teto seja o valor da questão (task.value).
        $teacher = Auth::guard('teacher')->user();

        $answer = StudentAnwser::query()
            ->with('task')
            ->whereKey($studentAnswerId)
            ->where('task_id', $this->task_id)
            ->whereHas('task', function ($query) use ($teacher) {
                $query->ownedByTeacher($teacher);
            })
            ->first();

        if (! $answer) {
            $this->addError("feedbacks.$studentAnswerId", 'Resposta inválida ou sem permissão.');
            return;
        }

        $maxGrade = $answer->task?->value !== null ? (float) $answer->task->value : 100.0;

        $this->validate(
            [
                "feedbacks.$studentAnswerId" => 'required|string|min:6|max:1000',
                "grades.$studentAnswerId" => [
                    'nullable',
                    function ($attribute, $value, $fail) use ($maxGrade) {
                        if ($value === null || $value === '') {
                            return;
                        }

                        $raw = trim((string) $value);

                        if (! preg_match('/^\d{1,3}$/', $raw)) {
                            $fail('A nota deve ser um número inteiro entre 0 e 100.');

                            return;
                        }

                        $numeric = ActivityValue::toFloat($raw);

                        if ($numeric > 100) {
                            $fail('A nota máxima é 100.');

                            return;
                        }

                        if ($numeric > $maxGrade + 0.01) {
                            $fail('A nota da questão não pode ser maior que o valor da questão ('.ActivityValue::format($maxGrade).').');
                        }
                    },
                ],
            ],
            [
                "feedbacks.$studentAnswerId.required" => 'O feedback é obrigatório.',
                "feedbacks.$studentAnswerId.min" => 'O feedback deve ter pelo menos :min caracteres.',
                "feedbacks.$studentAnswerId.max" => 'O feedback deve ter no máximo :max caracteres.',
            ],
        );

        $feedback = $this->feedbacks[$studentAnswerId] ?? null;
        if (empty($feedback)) {
            return;
        }

        $rawGrade = trim((string) ($this->grades[$studentAnswerId] ?? ''));
        $grade = $rawGrade === '' ? null : ActivityValue::toFloat($rawGrade);

        try {
            DB::transaction(function () use ($answer, $feedback, $grade) {
                QuestionFeedback::create([
                    'student_anwser_id' => $answer->id,
                    'task_id' => $answer->task_id,
                    'student_id' => $answer->student_id,
                    'feedback' => $feedback,
                    'teacher_id' => Auth::guard('teacher')->user()->id,
                ]);

                StudentAnwser::whereKey($answer->id)
                    ->update(['corrected' => true, 'grade' => $grade]);
            });
        } catch (\Exception $e) {
            report($e);
            $this->addError("feedbacks.$studentAnswerId", 'Não foi possível salvar a correção. Tente novamente.');
            return;
        }

        try {
            $activitTask = $answer->activit_task_id
                ? ActivitTask::find($answer->activit_task_id)
                : null;

            if ($activitTask) {
                ActivityNotificationService::correctionCompleted($activitTask, $answer->student_id);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        unset($this->feedbacks[$studentAnswerId], $this->grades[$studentAnswerId]);
        $this->success('Feedback enviado com sucesso.');
        $this->dispatch('activity-notifications-updated');
    }
}; ?>

<x-mary-modal wire:model="show" box-class="w-full max-w-4xl">
    @if ($studentAnswer)
        <x-mary-tabs wire:model="selectedTab">
            <x-mary-tab name="to-correct-tab" label="Correções" icon="o-exclamation-triangle">
                @foreach ($studentAnswer as $answer)
                    @if (!$answer->corrected)
                        <div wire:key="answer-{{ $answer->id }}"
                            class="p-4 bg-white rounded-md shadow-sm transition-all ease-in-out">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    {{-- avatar do aluno com fallback validado --}}
                                    <div class="flex-shrink-0">
                                        <img class="h-12 w-12 rounded-full"
                                            src="{{ $answer->student->avatar_url }}" alt="Aluno">
                                    </div>
                                    <div class="flex flex-col">
                                        <h3 class="text-gray-800 font-semibold ">
                                            {{ $answer->student->full_name }}
                                        </h3>
                                        <p class=" text-gray-600 mb-2 uppercase font-bold text-xs">
                                            {{ $answer->created_at->format('d/m/Y H:i') }}</p>
                                        @if ($activityTotalValue !== null)
                                            <p class="text-xs text-gray-500">
                                                Total do aluno:
                                                <span class="font-semibold text-gray-700">
                                                    {{ $this->formatGrade($studentTotals[$answer->student_id] ?? 0) }}
                                                </span>
                                                / {{ $this->formatGrade($activityTotalValue) }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-100 p-5 flex flex-col gap-2.5 text-gray-700">
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">
                                        Resposta do aluno
                                    </p>
                                    @if ($answer->task?->type === 'multiple_choice')
                                        <p class="text-sm tracking-wide text-gray-700">
                                            {{ $answer->answerOption?->text ?? 'Sem alternativa selecionada.' }}
                                        </p>
                                    @else
                                        <p class="text-sm tracking-wide text-gray-700 whitespace-pre-line">
                                            {{ $answer->answer_text ?: 'Sem resposta.' }}
                                        </p>
                                    @endif
                                </div>
                                <div class="flex flex-col gap-1">
                                    <x-mary-textarea class="!outline-none border-0" placeholder="Insira o feedback"
                                        wire:model="feedbacks.{{ $answer->id }}" />
                                    <div class="flex justify-end gap-2 items-center">

                                        <div class="flex flex-col" x-data="{ ...window.moneyMask }">
                                            <x-mary-input class="!outline-none border-0"
                                                placeholder="Insira a nota ex: 8"
                                                inputmode="numeric"
                                                wire:model="grades.{{ $answer->id }}"
                                                x-on:input.capture="mask($event)" />
                                            @error("grades.{$answer->id}")
                                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                            @enderror
                                        </div>
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
                                        {{-- avatar do aluno com fallback validado --}}
                                        <div class="flex-shrink-0">
                                            <img class="h-12 w-12 rounded-full"
                                                src="{{ $answer->student->avatar_url }}" alt="Aluno">
                                        </div>
                                        <div class="flex flex-col">
                                            <h3 class="text-gray-800 font-semibold ">
                                                {{ $answer->student->full_name }}
                                            </h3>
                                            <p class=" text-gray-600 mb-2 uppercase font-bold text-xs">
                                                {{ $answer->created_at->format('d/m/Y H:i') }}</p>
                                            @if ($activityTotalValue !== null)
                                                <p class="text-xs text-gray-500">
                                                    Total do aluno:
                                                    <span class="font-semibold text-gray-700">
                                                        {{ $this->formatGrade($studentTotals[$answer->student_id] ?? 0) }}
                                                    </span>
                                                    / {{ $this->formatGrade($activityTotalValue) }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-1 flex flex-col gap-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs uppercase tracking-wide text-gray-500">Nota:</span>
                                        <x-mary-badge
                                            value="{{ $answer->grade !== null ? $this->formatGrade($answer->grade) : 'Sem nota' }}"
                                            class="{{ $answer->grade !== null ? 'badge-primary' : 'badge-ghost' }}" />
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        <span class="font-semibold">Feedback:</span>
                                        {{ $this->savedFeedbacks[$answer->id] ?? 'Sem feedback registrado.' }}
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
