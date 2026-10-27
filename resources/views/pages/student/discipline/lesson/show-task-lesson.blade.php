<?php
use Livewire\Component;
use Livewire\Attributes\{On, Locked};
use App\Models\Teacher\{ActivitTask, ActivityAccess, Task, TaskOptions, StudentAnwser};
use App\Support\{ActivityNotificationService, ActivityValue};
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public bool $show = false;
    public ?string $activit_task_id = null;
    public ?string $activityTitle = null;
    public ?string $moduleTitle = null;
    public ?string $activityTotalValue = null;
    public ?string $activityDeadline = null;
    // Estrutura pública segura: id/question/type/options(id,text) — sem o gabarito.
    public array $tasks = [];
    public array $selectedOptions = []; // Para múltipla escolha
    public array $dissertativeAnswers = []; // Para dissertativas
    // Questões já corrigidas pelo professor: não podem ser editadas/reenviadas.
    #[Locked]
    public array $lockedTaskIds = [];

    #[On('student-event-open-task')]
    public function open($id)
    {
        $student = Auth::guard('student')->user();

        $activitask = ActivitTask::with([
            'module:id,title',
            'tasks' => function ($query) {
                $query->with(['options' => fn ($q) => $q->ordered()])->ordered();
            },
        ])
            ->whereHas('module', function ($query) use ($student) {
                $query->where('discipline_id', $student->discipline_id);
            })
            ->find($id);

        if (!$activitask) {
            $this->error('Atividade não encontrada');
            return;
        }

        if (! $activitask->isOpenForAnswers()) {
            $this->error($this->unavailableMessage($activitask));
            return;
        }

        // Registra o primeiro acesso do aluno à atividade (não sobrescreve).
        ActivityAccess::firstOrCreate(
            [
                'activit_task_id' => $activitask->id,
                'student_id' => $student->id,
            ],
            [
                'first_accessed_at' => now(),
            ]
        );

        // Respostas já enviadas pelo aluno (1ª tentativa) para pré-carregar o formulário.
        $existingAnswers = StudentAnwser::where('activit_task_id', $activitask->id)
            ->where('student_id', $student->id)
            ->where('attempt_number', 1)
            ->get()
            ->keyBy('task_id');

        $this->show = true;
        $this->activit_task_id = $activitask->id;
        $this->activityTitle = $this->displayTitle($activitask);
        $this->moduleTitle = $activitask->module?->title;
        $this->activityTotalValue = $activitask->is_avaliative && $activitask->total_value !== null
            ? ActivityValue::format($activitask->total_value)
            : null;
        $this->activityDeadline = $activitask->end?->format('d/m/Y H:i');
        $this->selectedOptions = $activitask->tasks
            ->where('type', 'multiple_choice')
            ->mapWithKeys(fn (Task $task) => [(string) $task->id => ''])
            ->all();
        $this->dissertativeAnswers = $activitask->tasks
            ->where('type', '!=', 'multiple_choice')
            ->mapWithKeys(fn (Task $task) => [(string) $task->id => ''])
            ->all();
        $this->lockedTaskIds = [];

        foreach ($existingAnswers as $taskId => $answer) {
            if ($answer->answer_option_id !== null) {
                $this->selectedOptions[$taskId] = $answer->answer_option_id;
            }

            if ($answer->answer_text !== null && trim((string) $answer->answer_text) !== '') {
                $this->dissertativeAnswers[$taskId] = $answer->answer_text;
            }

            // Já corrigida: trava a edição para não apagar nota/feedback.
            if ($answer->corrected) {
                $this->lockedTaskIds[] = (string) $taskId;
            }
        }

        $this->tasks = $activitask->tasks
            ->map(fn (Task $task) => [
                'id' => $task->id,
                'question' => $task->question,
                'type' => $task->type,
                'locked' => in_array((string) $task->id, $this->lockedTaskIds, true),
                'options' => $task->type === 'multiple_choice'
                    ? $task->options->map(fn (TaskOptions $option) => [
                        'id' => $option->id,
                        'text' => $option->text,
                    ])->values()->all()
                    : [],
            ])
            ->values()
            ->all();

        $this->dispatch('student-activity-progress-updated');
    }

    public function submitAnswers()
    {
        $activitask = $this->ownedActivity();

        if (! $activitask) {
            $this->error('Atividade não encontrada.');
            return;
        }

        if (! $activitask->isOpenForAnswers()) {
            $this->error($this->unavailableMessage($activitask));
            return;
        }

        $pendingTasks = $activitask->tasks
            ->reject(fn (Task $task) => in_array((string) $task->id, $this->lockedTaskIds, true));

        // Exige resposta em todas as questões ainda pendentes.
        $this->resetErrorBag();
        $hasEmpty = false;

        foreach ($pendingTasks as $task) {
            if ($task->type === 'multiple_choice') {
                if (empty($this->selectedOptions[$task->id])) {
                    $this->addError("selectedOptions.{$task->id}", 'Selecione uma alternativa para esta questão.');
                    $hasEmpty = true;
                }
            } elseif (trim((string) ($this->dissertativeAnswers[$task->id] ?? '')) === '') {
                $this->addError("dissertativeAnswers.{$task->id}", 'Escreva sua resposta para esta questão.');
                $hasEmpty = true;
            }
        }

        if ($hasEmpty) {
            $this->error('Responda todas as questões antes de enviar.');
            return;
        }

        foreach ($pendingTasks as $task) {
            if ($task->type === 'multiple_choice') {
                $this->saveMultipleChoiceAnswer($task, $this->studentId());
            } else {
                $this->saveDissertativeAnswer($task, $this->studentId());
            }
        }

        try {
            $student = Auth::guard('student')->user();

            if ($student) {
                ActivityNotificationService::activityAnswered($activitask, $student);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $this->success('Respostas enviadas com sucesso!');
        $this->cancelTask();
        $this->dispatch('student-activity-progress-updated');
        $this->dispatch('activity-notifications-updated');
    }

    /**
     * Recarrega a atividade garantindo que pertence à disciplina do aluno
     * (NÃO confiar no estado do cliente).
     */
    protected function ownedActivity(): ?ActivitTask
    {
        if (! $this->activit_task_id) {
            return null;
        }

        $student = Auth::guard('student')->user();

        return ActivitTask::with('tasks')
            ->whereHas('module', function ($query) use ($student) {
                $query->where('discipline_id', $student->discipline_id);
            })
            ->find($this->activit_task_id);
    }

    protected function displayTitle(ActivitTask $activitask): string
    {
        $title = trim((string) $activitask->title);

        if ($title === '' || str_starts_with($title, 'atividade ')) {
            return 'Atividade';
        }

        return $title;
    }

    protected function unavailableMessage(ActivitTask $activitask): string
    {
        if ($activitask->start && $activitask->start->isFuture()) {
            return 'Esta atividade estará disponível a partir de '.$activitask->start->format('d/m/Y H:i').'.';
        }

        if ($activitask->end && $activitask->end->isPast()) {
            return 'O prazo desta atividade terminou em '.$activitask->end->format('d/m/Y H:i').'.';
        }

        return 'Esta atividade não está disponível no momento.';
    }

    protected function studentId(): string
    {
        return Auth::guard('student')->user()->id;
    }

    protected function saveMultipleChoiceAnswer(Task $task, string $studentId)
    {
        $optionId = $this->selectedOptions[$task->id] ?? null;
        if (empty($optionId)) {
            return;
        }

        // Deriva o gabarito no servidor; nunca recebe `correct` do cliente.
        $option = TaskOptions::where('id', $optionId)
            ->where('task_id', $task->id)
            ->first();

        if (!$option) {
            return;
        }

        StudentAnwser::updateOrCreate(
            [
                'task_id' => $task->id,
                'student_id' => $studentId,
                'attempt_number' => 1,
            ],
            [
                'activit_task_id' => $this->activit_task_id,
                'answer_text' => null,
                'answer_option_id' => $option->id,
                'is_correct' => (bool) $option->correct,
                'corrected' => false,
            ]
        );
    }

    protected function saveDissertativeAnswer(Task $task, string $studentId)
    {
        $answerText = (string) ($this->dissertativeAnswers[$task->id] ?? '');

        if (trim($answerText) !== '') {
            StudentAnwser::updateOrCreate(
                [
                    'task_id' => $task->id,
                    'student_id' => $studentId,
                    'attempt_number' => 1,
                ],
                [
                    'activit_task_id' => $this->activit_task_id,
                    'answer_text' => $answerText,
                    'answer_option_id' => null,
                    'is_correct' => false, // Será definido pelo professor
                    'corrected' => false,
                ]
            );
        }
    }

    public function cancelTask()
    {
        $this->reset([
            'show',
            'activit_task_id',
            'activityTitle',
            'moduleTitle',
            'activityTotalValue',
            'activityDeadline',
            'tasks',
            'selectedOptions',
            'dissertativeAnswers',
            'lockedTaskIds',
        ]);
    }
};
?>
@php
    $answeredCount = collect($tasks)->filter(function ($task) use ($selectedOptions, $dissertativeAnswers) {
        if ($task['type'] === 'multiple_choice') {
            return ! empty($selectedOptions[$task['id']] ?? null);
        }

        return trim((string) ($dissertativeAnswers[$task['id']] ?? '')) !== '';
    })->count();
    $questionCount = count($tasks);
@endphp
<x-mary-drawer wire:model="show" class="w-11/12 max-w-5xl mx-auto p-0!" close-on-escape
    role="dialog" aria-modal="true" aria-labelledby="student-activity-heading">
    <div id="student-activity-drawer" class="flex h-dvh flex-col overflow-hidden bg-gray-50"
        x-data="{
            answered: {{ $answeredCount }},
            countAnswered() {
                return [...this.$el.querySelectorAll('[data-student-question]')].filter((question) => {
                    if (question.dataset.questionType === 'multiple_choice') {
                        return !!question.querySelector('input[type=radio]:checked');
                    }

                    const textarea = question.querySelector('textarea');

                    return !!textarea && textarea.value.trim() !== '';
                }).length;
            }
        }"
        @input="answered = countAnswered()"
        @change="answered = countAnswered()">
        <header class="shrink-0 border-b border-gray-200 bg-white px-5 py-4 sm:px-7">
            <div class="flex items-start justify-between gap-4">
                <div class="flex min-w-0 items-start gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
                        aria-hidden="true">
                        <x-mary-icon name="o-clipboard-document-list" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-blue-600">Atividade</p>
                        <h2 id="student-activity-heading"
                            class="mt-0.5 truncate text-xl font-semibold tracking-tight text-gray-900">
                            {{ $activityTitle ?: 'Atividade' }}
                        </h2>
                        <p class="mt-1 truncate text-sm text-gray-500">
                            {{ $moduleTitle ? 'Módulo: '.$moduleTitle : 'Responda às questões abaixo.' }}
                        </p>
                    </div>
                </div>

                <button type="button" wire:click="cancelTask"
                    class="btn btn-circle btn-ghost btn-sm shrink-0 text-gray-500 hover:text-gray-800"
                    aria-label="Fechar atividade">
                    <x-mary-icon name="o-x-mark" class="h-5 w-5" />
                </button>
            </div>

            @if ($activit_task_id)
                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-1 font-medium text-blue-700">
                        <x-mary-icon name="o-question-mark-circle" class="h-3.5 w-3.5" />
                        {{ count($tasks) }} {{ count($tasks) === 1 ? 'questão' : 'questões' }}
                    </span>
                    @if ($activityTotalValue !== null)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 font-medium text-emerald-700">
                            <x-mary-icon name="o-academic-cap" class="h-3.5 w-3.5" />
                            {{ $activityTotalValue }} pts
                        </span>
                    @endif
                    @if ($activityDeadline)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 font-medium text-gray-600">
                            <x-mary-icon name="o-calendar" class="h-3.5 w-3.5" />
                            Prazo: {{ $activityDeadline }}
                        </span>
                    @endif
                </div>
            @endif
        </header>

        <main class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-7">
            @if ($activit_task_id)
                <div class="mx-auto flex max-w-3xl flex-col gap-4">
                    @forelse ($tasks as $task)
                        @php
                            $isMultipleChoice = $task['type'] === 'multiple_choice';
                            $typeLabel = $isMultipleChoice ? 'Múltipla escolha' : 'Dissertativa';
                            $questionId = 'student-question-'.$task['id'];
                            $errorId = 'student-question-error-'.$task['id'];
                            $hasError = $errors->has('selectedOptions.'.$task['id']) || $errors->has('dissertativeAnswers.'.$task['id']);
                        @endphp
                        <section wire:key="task-{{ $task['id'] }}" data-tour="student-activity-question"
                            data-student-question data-question-type="{{ $task['type'] }}"
                            aria-labelledby="{{ $questionId }}"
                            @class([
                                'rounded-2xl border bg-white p-5 shadow-sm transition-colors',
                                'border-red-300' => $hasError,
                                'border-gray-200' => ! $hasError,
                            ])>
                            <div class="flex items-start gap-3.5">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white"
                                    aria-hidden="true">
                                    {{ $loop->iteration }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="mb-2 flex flex-wrap items-center gap-2">
                                        <span class="badge badge-sm border-0 bg-gray-100 text-gray-600">{{ $typeLabel }}</span>
                                        @if ($task['locked'])
                                            <span class="badge badge-sm border-0 bg-emerald-100 text-emerald-700">Já corrigida</span>
                                        @endif
                                    </div>
                                    <h3 id="{{ $questionId }}"
                                        class="whitespace-pre-line text-base font-semibold leading-relaxed text-gray-900">
                                        {{ $task['question'] }}
                                    </h3>
                                </div>
                            </div>

                            @if ($isMultipleChoice)
                                <fieldset class="mt-4" @disabled($task['locked'])>
                                    <legend class="sr-only">Escolha uma alternativa para a questão {{ $loop->iteration }}</legend>
                                    <div class="grid gap-2">
                                        @foreach ($task['options'] as $option)
                                            @php $optionId = 'student-option-'.$task['id'].'-'.$option['id']; @endphp
                                            <label for="{{ $optionId }}"
                                                @class([
                                                    'group flex items-start gap-3 rounded-xl border p-3.5 transition',
                                                    'cursor-pointer border-gray-200 bg-white hover:border-blue-300 hover:bg-blue-50/40' => ! $task['locked'],
                                                    'cursor-default border-gray-200 bg-gray-50' => $task['locked'],
                                                    'has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50/70' => ! $task['locked'],
                                                    'focus-within:ring-2 focus-within:ring-blue-500/20' => ! $task['locked'],
                                                ])>
                                                <input id="{{ $optionId }}" type="radio"
                                                    value="{{ $option['id'] }}"
                                                    wire:model="selectedOptions.{{ $task['id'] }}"
                                                    class="radio radio-primary radio-sm mt-0.5 shrink-0"
                                                    @disabled($task['locked']) />
                                                <span class="flex min-w-0 flex-1 items-start gap-2.5">
                                                    <span class="flex size-6 shrink-0 items-center justify-center rounded-md bg-gray-100 text-[11px] font-bold uppercase text-gray-600"
                                                        aria-hidden="true">
                                                        {{ chr(65 + $loop->index) }}
                                                    </span>
                                                    <span class="text-sm leading-relaxed text-gray-700">{{ $option['text'] }}</span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                                @error("selectedOptions.{$task['id']}")
                                    <p id="{{ $errorId }}" class="mt-2 flex items-center gap-1.5 text-xs font-medium text-red-600">
                                        <x-mary-icon name="o-exclamation-circle" class="h-4 w-4 shrink-0" />
                                        {{ $message }}
                                    </p>
                                @enderror
                            @else
                                <div class="mt-4">
                                    <label for="student-answer-{{ $task['id'] }}" class="sr-only">
                                        Resposta da questão {{ $loop->iteration }}
                                    </label>
                                    <textarea id="student-answer-{{ $task['id'] }}"
                                        wire:model="dissertativeAnswers.{{ $task['id'] }}"
                                        rows="4"
                                        placeholder="Escreva sua resposta com clareza..."
                                        class="textarea textarea-bordered w-full leading-relaxed focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                        @readonly($task['locked'])
                                        @if ($hasError) aria-invalid="true" aria-describedby="{{ $errorId }}" @endif
                                    ></textarea>
                                    @error("dissertativeAnswers.{$task['id']}")
                                        <p id="{{ $errorId }}" class="mt-2 flex items-center gap-1.5 text-xs font-medium text-red-600">
                                            <x-mary-icon name="o-exclamation-circle" class="h-4 w-4 shrink-0" />
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            @endif
                        </section>
                    @empty
                        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                            <x-mary-icon name="o-document-text" class="mx-auto h-10 w-10 text-gray-400" />
                            <h3 class="mt-3 text-sm font-semibold text-gray-800">Nenhuma questão nesta atividade</h3>
                            <p class="mt-1 text-sm text-gray-500">Feche esta janela e tente novamente mais tarde.</p>
                        </div>
                    @endforelse
                </div>
            @endif
        </main>

        @if ($activit_task_id)
            <footer class="shrink-0 border-t border-gray-200 bg-white px-5 py-4 sm:px-7" aria-live="polite">
                <div class="mx-auto flex max-w-3xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2.5">
                        <progress class="progress progress-primary h-1.5 w-24" value="{{ $answeredCount }}"
                            :value="answered" max="{{ max($questionCount, 1) }}"></progress>
                        <span class="text-xs font-medium text-gray-600">
                            <span x-text="answered">{{ $answeredCount }}</span> de {{ $questionCount }}
                            {{ $questionCount === 1 ? 'questão respondida' : 'questões respondidas' }}
                        </span>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="cancelTask" class="btn btn-ghost btn-sm">
                            Cancelar
                        </button>
                        <button type="button" id="student-activity-submit" wire:click="submitAnswers"
                            wire:loading.attr="disabled" wire:target="submitAnswers"
                            class="btn btn-primary btn-sm gap-2 px-4">
                            <span wire:loading.remove wire:target="submitAnswers" class="inline-flex items-center gap-2">
                                <x-mary-icon name="o-paper-airplane" class="h-4 w-4" />
                                Enviar respostas
                            </span>
                            <span wire:loading wire:target="submitAnswers" class="inline-flex items-center gap-2">
                                <span class="loading loading-spinner loading-xs"></span>
                                Enviando...
                            </span>
                        </button>
                    </div>
                </div>
            </footer>
        @endif
    </div>
</x-mary-drawer>
