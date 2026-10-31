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
        $this->activityTotalValue = $activitTask->total_value;
        $this->selectedStudentId = null;
        $this->answers = [];
        $this->students = $this->buildStudentList($activitTask);
    }

    /**
     * Monta a lista de alunos que entregaram a atividade, com turma e disciplina.
     */
    protected function buildStudentList(ActivitTask $activitTask): array
    {
        $studentIds = Student::where('discipline_id', $activitTask->discipline_id)->pluck('id');

        $answersByStudent = StudentAnwser::with('task')
            ->where('activit_task_id', $activitTask->id)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->groupBy('student_id');

        // Apenas alunos que efetivamente entregaram a atividade.
        $deliveredIds = $answersByStudent->keys();

        $finalGrades = $activitTask->activityGrades->keyBy('student_id');

        return Student::with(['classe', 'discipline'])
            ->whereIn('id', $deliveredIds)
            ->orderBy('full_name')
            ->get()
            ->map(function (Student $student) use ($answersByStudent, $finalGrades) {
                $answers = $answersByStudent->get($student->id, collect());
                $allCorrected = $answers->every(fn ($a) => (bool) $a->corrected);
                $finalGrade = $finalGrades->get($student->id)?->final_grade;

                $status = $allCorrected ? 'corrected' : 'pending';

                return [
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'classe' => $student->classe?->name,
                    'discipline' => $student->discipline?->title,
                    'delivered' => true,
                    'status' => $status,
                    'answers_count' => $answers->count(),
                    'final_grade' => $finalGrade !== null ? ActivityValue::format($finalGrade) : null,
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
            'value' => $answer->task?->value !== null ? ActivityValue::format($answer->task->value) : null,
            'answer' => $answer->task?->type === 'multiple_choice'
                ? ($answer->answerOption?->text ?? 'Sem alternativa selecionada.')
                : ($answer->answer_text ?: 'Sem resposta.'),
            'is_correct' => $answer->task?->type === 'multiple_choice'
                ? (bool) $answer->is_correct
                : null,
            'corrected' => (bool) $answer->corrected,
            'grade' => $answer->grade !== null ? ActivityValue::format($answer->grade) : null,
            'feedback' => $answer->feedbacks->last()?->feedback,
        ])->all();

        $this->feedbacks = $answers->mapWithKeys(
            fn (StudentAnwser $answer) => [$answer->id => $answer->feedbacks->last()?->feedback ?? '']
        )->all();

        $this->grades = $answers->mapWithKeys(
            fn (StudentAnwser $answer) => [$answer->id => $answer->grade !== null ? ActivityValue::format($answer->grade) : '']
        )->all();

        $finalGrade = ActivityGrade::where('activit_task_id', $activitTask->id)
            ->where('student_id', $student->id)
            ->first();

        $this->finalGrade = $finalGrade?->final_grade !== null
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

        $maxGrade = $answer->task?->value !== null ? (float) $answer->task->value : 100.0;

        $this->validate([
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

                    if (ActivityValue::toFloat($raw) > $maxGrade + 0.01) {
                        $fail('A nota da questão não pode ser maior que o valor da questão ('.ActivityValue::format($maxGrade).').');
                    }
                },
            ],
        ], [
            "feedbacks.$studentAnswerId.required" => 'O feedback é obrigatório.',
            "feedbacks.$studentAnswerId.min" => 'O feedback deve ter pelo menos :min caracteres.',
            "feedbacks.$studentAnswerId.max" => 'O feedback deve ter no máximo :max caracteres.',
        ]);

        $rawGrade = trim((string) ($this->grades[$studentAnswerId] ?? ''));
        $grade = $rawGrade === '' ? null : ActivityValue::toFloat($rawGrade);

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

        try {
            $activitTask = ActivitTask::find($answer->activit_task_id);

            if ($activitTask) {
                ActivityNotificationService::correctionCompleted($activitTask, $answer->student_id);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $this->success('Correção salva com sucesso!');
        $this->refreshDeliveries();
        $this->selectStudent($this->selectedStudentId);
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
                    return $filter === 'all' || $s['status'] === $filter;
                });
            @endphp

            @forelse ($visible as $student)
                <div wire:key="student-{{ $student['id'] }}"
                    id="delivery-student-{{ $student['id'] }}"
                    data-delivery-student="{{ $student['id'] }}"
                    class="w-full p-3 mb-2 rounded border {{ $selectedStudentId === $student['id'] ? 'border-blue-400 bg-blue-50' : 'border-gray-200 hover:bg-gray-50' }}">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $student['name'] }}</p>
                            <p class="text-xs text-gray-500 truncate">
                                Turma: {{ $student['classe'] ?? 'não informada' }}
                            </p>
                            <p class="text-xs text-gray-500 truncate">
                                Disciplina: {{ $student['discipline'] ?? 'não informada' }}
                            </p>
                            <div class="mt-1 flex items-center gap-2 text-xs text-gray-500">
                                @if ($student['delivered_at'])
                                    <span>{{ $student['delivered_at'] }}</span>
                                @endif
                                @if ($student['final_grade'] !== null)
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
                <div class="flex flex-col gap-4">
                    @foreach ($answers as $answer)
                        <div class="border border-gray-200 rounded-lg p-4" wire:key="delivery-answer-{{ $answer['id'] }}">
                            <div class="flex items-center justify-between mb-2">
                                <div class="font-medium text-gray-800">
                                    {{ $loop->iteration }}. {{ $answer['question'] }}
                                </div>
                                <div class="flex items-center gap-2">
                                    @if ($answer['value'] !== null)
                                        <span class="text-xs text-gray-500">Vale: {{ $answer['value'] }}</span>
                                    @endif
                                    @if ($answer['corrected'])
                                        <x-mary-badge value="Corrigida" class="badge-success badge-sm" />
                                    @else
                                        <x-mary-badge value="Pendente" class="badge-warning badge-sm" />
                                    @endif
                                </div>
                            </div>

                            @if ($answer['type'] === 'multiple_choice')
                                <div class="{{ $answer['is_correct'] ? 'bg-green-100' : 'bg-red-100' }} p-3 rounded mb-3">
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <p class="text-xs uppercase tracking-wide text-gray-600">Resposta do aluno</p>
                                        @if ($answer['is_correct'])
                                            <x-mary-badge value="Correta" class="badge-success badge-sm" />
                                        @else
                                            <x-mary-badge value="Incorreta" class="badge-error badge-sm" />
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-800 font-medium whitespace-pre-line">{{ $answer['answer'] }}</p>
                                </div>
                            @else
                                <div class="bg-gray-100 p-3 rounded mb-3">
                                    <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Resposta do aluno</p>
                                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ $answer['answer'] }}</p>
                                </div>
                            @endif

                            <div class="flex flex-col gap-1">
                                <x-mary-textarea class="!outline-none" placeholder="Insira o feedback"
                                    wire:model="feedbacks.{{ $answer['id'] }}" />
                                @error("feedbacks.{$answer['id']}")
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror

                                <div class="flex justify-end gap-2 items-center">
                                    <div class="flex flex-col" x-data="{ ...window.moneyMask }">
                                        <x-mary-input class="!outline-none" placeholder="Nota da questão (ex: 8)"
                                            inputmode="numeric"
                                            wire:model="grades.{{ $answer['id'] }}"
                                            x-on:input.capture="mask($event)" />
                                        @error("grades.{$answer['id']}")
                                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <button class="btn btn-sm bg-gray-200 mt-2 self-end"
                                        x-on:click.prevent="$wire.sendFeedback('{{ $answer['id'] }}')">
                                        Salvar correção
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach

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
                </div>
            @endif
        </div>
    </div>
