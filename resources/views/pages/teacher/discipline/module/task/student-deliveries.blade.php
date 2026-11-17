<?php

use Livewire\Component;
use Livewire\Attributes\{On, Locked};
use App\Models\Teacher\{ActivitTask, ActivityGrade, QuestionFeedback, Student, StudentAnwser};
use App\Support\{ActivityNotificationService, ActivityValue};
use Illuminate\Support\Facades\{Auth, DB};
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    #[Locked]
    public string $activitTaskId = '';

    /** Lista de alunos que entregaram a atividade. */
    #[Locked]
    public array $students = [];

    /** Aluno selecionado para correção. */
    #[Locked]
    public ?string $selectedStudentId = null;

    /** Respostas do aluno selecionado (todas as questões). */
    #[Locked]
    public array $answers = [];

    /** Valor total da atividade (null se não avaliativa). */
    #[Locked]
    public $activityTotalValue = null;

    /** Indica se a atividade possui notas. */
    #[Locked]
    public bool $isGraded = false;

    /** Nota final digitada da atividade. */
    public $finalGrade = null;
    public string $finalFeedback = '';

    public array $feedbacks = [];
    public array $grades = [];

    public string $filter = 'all'; // all | pending | corrected

    /**
     * Componente embutido na aba "Entregas" do show-task: carrega a lista
     * de alunos que entregaram a atividade ao ser montado.
     */
    public function mount($activityId = null): void
    {
        $teacher = Auth::guard('teacher')->user();

        $activitTask = ActivitTask::with('activityGrades')
            ->whereKey($activityId)
            ->ownedByTeacher($teacher)
            ->first();

        if (! $activitTask) {
            $this->students = [];

            return;
        }

        $this->activitTaskId = $activitTask->id;
        $this->isGraded = $activitTask->isGraded();
        $this->activityTotalValue = $activitTask->isGraded() ? $activitTask->total_value : null;
        $this->selectedStudentId = null;
        $this->answers = [];
        $this->students = $this->buildStudentList($activitTask);
    }

    /**
     * Monta a lista de alunos que entregaram a atividade, com turma e disciplina.
     */
    protected function buildStudentList(ActivitTask $activitTask): array
    {
        $answersByStudent = StudentAnwser::with('task')
            ->where('activit_task_id', $activitTask->id)
            ->get()
            ->groupBy('student_id');

        $finalGrades = $activitTask->activityGrades->keyBy('student_id');

        return Student::with(['classe', 'discipline'])
            ->where('discipline_id', $activitTask->discipline_id)
            ->orderBy('full_name')
            ->get()
            ->map(function (Student $student) use ($activitTask, $answersByStudent, $finalGrades) {
                $answers = $answersByStudent->get($student->id, collect());
                $delivered = $answers->isNotEmpty();
                $allCorrected = $delivered && $answers->every(fn ($a) => (bool) $a->corrected);
                $finalGrade = $finalGrades->get($student->id)?->final_grade;
                $reviewed = $delivered && ($allCorrected || $finalGrade !== null);

                return [
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'classe' => $student->classe?->name,
                    'discipline' => $student->discipline?->title,
                    'active' => $student->isActive(),
                    'delivered' => $delivered,
                    'reviewed' => $reviewed,
                    'status' => $reviewed ? 'corrected' : 'pending',
                    'answers_count' => $answers->count(),
                    'final_grade' => $activitTask->isGraded() && $finalGrade !== null ? ActivityValue::format($finalGrade) : null,
                    'delivered_at' => $answers->min('created_at')?->format('d/m/Y H:i'),
                ];
            })
            ->values()
            ->all();
    }

    #[On('event-teacher-select-student')]
    public function selectStudent($student_id): void
    {
        $teacher = Auth::guard('teacher')->user();

        $activitTask = ActivitTask::whereKey($this->activitTaskId)
            ->ownedByTeacher($teacher)
            ->first();

        if (! $activitTask) {
            return;
        }

        $student = Student::whereKey($student_id)
            ->where('discipline_id', $activitTask->discipline_id)
            ->first();

        if (! $student) {
            return;
        }

        $this->selectedStudentId = $student->id;

        $answers = StudentAnwser::with(['task', 'answerOption', 'feedbacks'])
            ->where('activit_task_id', $activitTask->id)
            ->where('student_id', $student->id)
            ->get()
            ->sortBy(fn ($a) => $a->task?->order ?? 0)
            ->values();

        $this->answers = $answers->map(fn (StudentAnwser $answer) => [
            'id' => $answer->id,
            'question' => $answer->task?->question,
            'type' => $answer->task?->type,
            'value' => $this->isGraded && $answer->task?->value !== null ? ActivityValue::format($answer->task->value) : null,
            'answer' => $answer->task?->type === 'multiple_choice'
                ? ($answer->answerOption?->text ?? 'Sem alternativa selecionada.')
                : ($answer->answer_text ?: 'Sem resposta.'),
            'is_correct' => $answer->task?->type === 'multiple_choice'
                ? (bool) $answer->is_correct
                : null,
            'corrected' => (bool) $answer->corrected,
            'grade' => $this->isGraded && $answer->grade !== null ? ActivityValue::format($answer->grade) : null,
            'feedback' => $answer->feedbacks->last()?->feedback,
        ])->all();

        $this->feedbacks = $answers->mapWithKeys(
            fn (StudentAnwser $answer) => [$answer->id => $answer->feedbacks->last()?->feedback ?? '']
        )->all();

        $this->grades = $answers->mapWithKeys(
            fn (StudentAnwser $answer) => [$answer->id => $this->isGraded && $answer->grade !== null ? ActivityValue::format($answer->grade) : '']
        )->all();

        $finalGrade = ActivityGrade::where('activit_task_id', $activitTask->id)
            ->where('student_id', $student->id)
            ->first();

        $this->finalGrade = $this->isGraded && $finalGrade?->final_grade !== null
            ? ActivityValue::format($finalGrade->final_grade)
            : '';
        $this->finalFeedback = $finalGrade?->feedback ?? '';
    }

    /** Soma das notas já lançadas por questão para o aluno selecionado. */
    public function sumQuestionGrades(): float
    {
        return round(array_sum(array_map(
            fn ($g) => ActivityValue::toFloat($g),
            $this->grades
        )), 2);
    }

    public function formatGrade($grade): string
    {
        return ActivityValue::format($grade);
    }

    public function sendFeedback(string $studentAnswerId): void
    {
        $teacher = Auth::guard('teacher')->user();

        $answer = StudentAnwser::query()
            ->with('task')
            ->whereKey($studentAnswerId)
            ->where('student_id', $this->selectedStudentId)
            ->whereHas('task', fn ($q) => $q->ownedByTeacher($teacher))
            ->first();

        if (! $answer) {
            $this->addError("feedbacks.$studentAnswerId", 'Resposta inválida ou sem permissão.');
            return;
        }

        $activitTask = ActivitTask::find($answer->activit_task_id);
        $isGraded = (bool) $activitTask?->isGraded();
        $maxGrade = $answer->task?->value !== null ? (float) $answer->task->value : 100.0;

        $rules = [
            "feedbacks.$studentAnswerId" => 'required|string|min:6|max:1000',
        ];

        if ($isGraded) {
            $rules["grades.$studentAnswerId"] = [
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

                    if (ActivityValue::toFloat($raw) > $maxGrade + 0.01) {
                        $fail('A nota da questão não pode ser maior que o valor da questão ('.ActivityValue::format($maxGrade).').');
                    }
                },
            ];
        }

        $this->validate($rules, [
            "feedbacks.$studentAnswerId.required" => 'O feedback é obrigatório.',
            "feedbacks.$studentAnswerId.min" => 'O feedback deve ter pelo menos :min caracteres.',
            "feedbacks.$studentAnswerId.max" => 'O feedback deve ter no máximo :max caracteres.',
        ]);

        $rawGrade = $isGraded
            ? trim((string) ($this->grades[$studentAnswerId] ?? ''))
            : '';
        $grade = $rawGrade === '' ? null : ActivityValue::toFloat($rawGrade);

        try {
            DB::transaction(function () use ($answer, $studentAnswerId, $teacher, $grade) {
                QuestionFeedback::create([
                    'student_anwser_id' => $answer->id,
                    'task_id' => $answer->task_id,
                    'student_id' => $answer->student_id,
                    'teacher_id' => $teacher->id,
                    'feedback' => $this->feedbacks[$studentAnswerId],
                    'registered_at' => now(),
                ]);

                $answer->update([
                    'grade' => $grade,
                    'corrected' => true,
                ]);
            });
        } catch (\Exception $e) {
            report($e);
            $this->addError("feedbacks.$studentAnswerId", 'Não foi possível salvar a correção. Tente novamente.');
            return;
        }

        try {
            if ($activitTask) {
                ActivityNotificationService::correctionCompleted($activitTask, $answer->student_id);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $this->success('Correção salva com sucesso!');
        $this->refreshDeliveries();
        $this->selectStudent($this->selectedStudentId);
        $this->feedbacks[$studentAnswerId] = '';
        $this->dispatch('activity-notifications-updated');
    }

    /** Salva a nota final da atividade do aluno selecionado. */
    public function saveFinalGrade(): void
    {
        $teacher = Auth::guard('teacher')->user();

        $activitTask = ActivitTask::whereKey($this->activitTaskId)
            ->ownedByTeacher($teacher)
            ->first();

        if (! $activitTask || ! $this->selectedStudentId) {
            $this->error('Atividade ou aluno inválido.');
            return;
        }

        if (! $activitTask->isGraded()) {
            $this->error('Esta atividade não possui notas.');
            return;
        }

        $max = $activitTask->total_value !== null ? (float) $activitTask->total_value : 100.0;
        $raw = trim((string) $this->finalGrade);

        if ($raw !== '') {
            if (! preg_match('/^\d{1,3}$/', $raw)) {
                $this->addError('finalGrade', 'A nota deve ser um número inteiro entre 0 e 100.');
                return;
            }

            if (ActivityValue::toFloat($raw) > $max + 0.01) {
                $this->addError('finalGrade', 'A nota final não pode ser maior que o valor da atividade ('.ActivityValue::format($max).').');
                return;
            }
        }

        ActivityGrade::updateOrCreate(
            [
                'activit_task_id' => $activitTask->id,
                'student_id' => $this->selectedStudentId,
            ],
            [
                'teacher_id' => $teacher->id,
                'final_grade' => $raw === '' ? null : ActivityValue::toFloat($raw),
                'feedback' => $this->finalFeedback ?: null,
                'registered_at' => now(),
            ]
        );

        try {
            ActivityNotificationService::correctionCompleted($activitTask, $this->selectedStudentId);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->success('Nota final salva com sucesso!');
        $this->refreshDeliveries();
        $this->dispatch('activity-notifications-updated');
    }

    protected function refreshDeliveries(): void
    {
        $teacher = Auth::guard('teacher')->user();
        $activitTask = ActivitTask::with('activityGrades')
            ->whereKey($this->activitTaskId)
            ->ownedByTeacher($teacher)
            ->first();

        if ($activitTask) {
            $this->students = $this->buildStudentList($activitTask);
        }
    }
}; ?>

<div class="w-11/12 mx-auto flex gap-4" style="min-width: 0;">
    {{-- Coluna esquerda: lista de alunos que entregaram --}}
    <div class="w-1/3 border-r border-gray-200 pr-4 overflow-y-auto max-h-[75vh]" id="teacher-deliveries-list">
        <div class="mb-3 flex flex-wrap gap-1" id="teacher-deliveries-filters">
            @foreach (['all' => 'Todos', 'pending' => 'Pendentes', 'corrected' => 'Corrigidas'] as $key => $label)
                <button wire:click="$set('filter', '{{ $key }}')"
                    data-delivery-filter="{{ $key }}"
                    class="btn btn-xs {{ $filter === $key ? 'btn-primary' : 'btn-ghost border' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

            @php
                $visible = collect($students)->filter(function ($s) use ($filter) {
                    return match ($filter) {
                        'pending' => ! $s['delivered'] && $s['active'],
                        'corrected' => $s['delivered'] && $s['reviewed'],
                        default => $s['delivered'],
                    };
                });
            @endphp

            @forelse ($visible as $student)
                <div wire:key="student-{{ $student['id'] }}"
                    id="delivery-student-{{ $student['id'] }}"
                    data-delivery-student="{{ $student['id'] }}"
                    class="w-full p-3 mb-2 rounded border {{ $selectedStudentId === $student['id'] ? 'border-blue-400 bg-blue-50' : 'border-gray-200 hover:bg-gray-50' }}">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $student['name'] }}</p>
                                <span data-delivery-student-classe="{{ $student['classe'] ?? '' }}"
                                    class="inline-flex items-center gap-1 rounded-full border border-blue-100 bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-700">
                                    <x-mary-icon name="o-user-group" class="h-3.5 w-3.5" />
                                    Turma: {{ $student['classe'] ?? 'não informada' }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 truncate">
                                Disciplina: {{ $student['discipline'] ?? 'não informada' }}
                            </p>
                            <div class="mt-1 flex items-center gap-2 text-xs text-gray-500">
                                @if ($student['delivered_at'])
                                    <span>{{ $student['delivered_at'] }}</span>
                                @endif
                                @if ($isGraded && $student['final_grade'] !== null)
                                    <span class="text-gray-700 font-semibold">Nota: {{ $student['final_grade'] }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-2 shrink-0">
                            @if ($student['status'] === 'corrected')
                                <x-mary-badge value="Corrigida" class="badge-success badge-sm" />
                            @else
                                <x-mary-badge value="Pendente" class="badge-warning badge-sm" />
                            @endif

                            <x-mary-button label="Abrir" icon="o-folder-open" class="btn-xs btn-primary"
                                id="delivery-student-open-{{ $student['id'] }}"
                                wire:click="selectStudent('{{ $student['id'] }}')" spinner />
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 py-4">Nenhuma entrega neste filtro.</p>
            @endforelse
        </div>

        {{-- Coluna direita: prova respondida do aluno --}}
        <div class="w-2/3 overflow-y-auto max-h-[75vh]" id="teacher-deliveries-detail"
            data-selected-student-id="{{ $selectedStudentId ?? '' }}">
            @if (! $selectedStudentId)
                <div class="h-full flex items-center justify-center text-gray-400">
                    <div class="text-center">
                        <x-mary-icon name="o-cursor-arrow-rays" class="w-10 h-10 mx-auto mb-2" />
                        <p>Selecione um aluno para ver as respostas e corrigir.</p>
                    </div>
                </div>
            @elseif (count($answers) === 0)
                <p class="text-gray-500 py-6">Este aluno ainda não respondeu a atividade.</p>
            @else
                <div class="flex flex-col gap-5">
                    @foreach ($answers as $answer)
                        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                            wire:key="delivery-answer-{{ $answer['id'] }}">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex min-w-0 items-start gap-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-50 text-sm font-bold text-blue-700">
                                        {{ $loop->iteration }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                            Questão {{ $loop->iteration }}
                                        </p>
                                        <h3 data-delivery-question
                                            class="mt-1 break-words text-base font-medium leading-relaxed text-gray-900 whitespace-pre-line">
                                            {{ $answer['question'] }}
                                        </h3>
                                    </div>
                                </div>

                                <div class="flex shrink-0 flex-wrap items-center gap-2 sm:justify-end">
                                    @if ($answer['value'] !== null)
                                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                                            Vale: {{ $answer['value'] }}
                                        </span>
                                    @endif
                                    @if ($answer['corrected'])
                                        <x-mary-badge value="Corrigida" class="badge-success badge-sm" />
                                    @else
                                        <x-mary-badge value="Pendente" class="badge-warning badge-sm" />
                                    @endif
                                </div>
                            </div>

                            @if ($answer['type'] === 'multiple_choice')
                                <div class="mt-4 overflow-hidden rounded-lg border {{ $answer['is_correct'] ? 'border-green-200' : 'border-red-200' }}">
                                    <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 {{ $answer['is_correct'] ? 'bg-green-100' : 'bg-red-100' }}">
                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-600">
                                            Resposta do aluno
                                        </p>
                                        @if ($answer['is_correct'])
                                            <x-mary-badge value="Correta" class="badge-success badge-sm" />
                                        @else
                                            <x-mary-badge value="Incorreta" class="badge-error badge-sm" />
                                        @endif
                                    </div>
                                    <div class="px-4 py-3.5 {{ $answer['is_correct'] ? 'bg-green-50/60' : 'bg-red-50/60' }}">
                                        <p data-delivery-answer
                                            class="break-words text-base font-medium leading-relaxed text-gray-900 whitespace-pre-line">
                                            {{ $answer['answer'] }}
                                        </p>
                                    </div>
                                </div>
                            @else
                                <div class="mt-4 overflow-hidden rounded-lg border border-gray-200">
                                    <div class="bg-gray-100 px-4 py-2.5">
                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                            Resposta do aluno
                                        </p>
                                    </div>
                                    <div class="bg-gray-50 px-4 py-3.5">
                                        <p data-delivery-answer
                                            class="break-words text-base leading-relaxed text-gray-800 whitespace-pre-line">
                                            {{ $answer['answer'] }}
                                        </p>
                                    </div>
                                </div>
                            @endif

                            <div class="mt-4 border-t border-gray-100 pt-4">
                                <div class="flex flex-col gap-2">
                                    <label for="delivery-feedback-{{ $answer['id'] }}"
                                        class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                        Feedback da correção
                                    </label>
                                    <x-mary-textarea id="delivery-feedback-{{ $answer['id'] }}" rows="3"
                                        class="!outline-none" placeholder="Write feedback"
                                        wire:model="feedbacks.{{ $answer['id'] }}" />
                                    @error("feedbacks.{$answer['id']}")
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror

                                    <div class="flex justify-end gap-2 items-center">
                                        @if ($isGraded)
                                            <div class="flex flex-col" x-data="{ ...window.moneyMask }">
                                                <x-mary-input class="!outline-none" placeholder="Nota da questão (ex: 8)"
                                                    inputmode="numeric"
                                                    wire:model="grades.{{ $answer['id'] }}"
                                                    x-on:input.capture="mask($event)" />
                                                @error("grades.{$answer['id']}")
                                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        @endif
                                        <button class="btn btn-sm bg-gray-200 mt-2 self-end"
                                            x-on:click.prevent="$wire.sendFeedback('{{ $answer['id'] }}')">
                                            Salvar correção
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @if ($isGraded)
                        {{-- Nota final da atividade --}}
                        <div class="border-t border-gray-200 pt-4" id="teacher-deliveries-final-grade">
                            <h3 class="text-md font-semibold text-gray-800 mb-2">Nota final da atividade</h3>
                            <div class="flex items-end gap-3">
                                <div class="flex flex-col">
                                    <span class="text-xs text-gray-500 mb-1">
                                        Soma das questões:
                                        <strong>{{ $this->formatGrade($this->sumQuestionGrades()) }}</strong>
                                        @if ($activityTotalValue !== null)
                                            / {{ $this->formatGrade($activityTotalValue) }}
                                        @endif
                                    </span>
                                    <x-mary-input label="Nota final" placeholder="Ex: 85" inputmode="numeric"
                                        wire:model="finalGrade" class="!outline-none" />
                                    @error('finalGrade')
                                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                                <x-mary-button label="Salvar nota final" class="btn-primary btn-sm mb-1"
                                    wire:click="saveFinalGrade" spinner="saveFinalGrade" />
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
