<?php
use Livewire\Component;
use Livewire\Attributes\{On};
use App\Models\Teacher\{ActivitTask, ActivityGrade, StudentAnwser, QuestionFeedback};
use App\Support\ActivityProgress;

new class extends Component {
    public bool $show = false;
    public ?string $activit_task_id = null;
    public array $results = [];
    public ?string $finalGrade = null;
    public ?string $finalFeedback = null;

    // Situação da atividade para o aluno (calculada com base nos registros).
    public ?string $statusLabel = null;
    public ?string $statusBadge = null;
    public ?string $availableAt = null;
    public ?string $deadline = null;
    public ?string $firstAccessedAt = null;
    public ?string $answeredAt = null;

    #[On('student-event-open-task-result')]
    public function open($id): void
    {
        $student = Auth::guard('student')->user();

        $activitask = ActivitTask::whereHas('module', function ($query) use ($student) {
            $query->where('discipline_id', $student->discipline_id);
        })->find($id);

        if (! $activitask) {
            return;
        }

        $progress = ActivityProgress::forStudent($student, collect([$activitask]))[$activitask->id] ?? null;

        $this->statusLabel = null;
        $this->statusBadge = null;
        $this->availableAt = null;
        $this->deadline = null;
        $this->firstAccessedAt = null;
        $this->answeredAt = null;

        if ($progress) {
            $this->statusLabel = ActivityProgress::label($progress['status'], $progress['late']);
            $this->statusBadge = ActivityProgress::badgeClass($progress['status'], $progress['late']);
            $this->availableAt = $progress['available_at']?->format('d/m/Y H:i');
            $this->deadline = $progress['deadline']?->format('d/m/Y H:i');
            $this->firstAccessedAt = $progress['first_accessed_at']?->format('d/m/Y H:i');
            $this->answeredAt = $progress['answered_at']?->format('d/m/Y H:i');
        }

        $answers = StudentAnwser::with(['task'])
            ->where('activit_task_id', $activitask->id)
            ->where('student_id', $student->id)
            ->orderBy('created_at')
            ->get();

        $feedbacks = QuestionFeedback::whereIn('student_anwser_id', $answers->pluck('id'))
            ->get()
            ->keyBy('student_anwser_id');

        $this->activit_task_id = $activitask->id;
        $this->results = $answers->map(function (StudentAnwser $answer) use ($feedbacks) {
            return [
                'id' => $answer->id,
                'question' => $answer->task?->question,
                'grade' => $answer->corrected ? $answer->grade : null,
                'corrected' => (bool) $answer->corrected,
                'feedback' => $feedbacks->get($answer->id)?->feedback,
            ];
        })->values()->all();

        $final = ActivityGrade::where('activit_task_id', $activitask->id)
            ->where('student_id', $student->id)
            ->first();

        $this->finalGrade = $final?->final_grade !== null
            ? \App\Support\ActivityValue::format($final->final_grade)
            : null;
        $this->finalFeedback = $final?->feedback;

        $this->show = true;
    }

    public function close(): void
    {
        $this->reset([
            'show',
            'activit_task_id',
            'results',
            'finalGrade',
            'finalFeedback',
            'statusLabel',
            'statusBadge',
            'availableAt',
            'deadline',
            'firstAccessedAt',
            'answeredAt',
        ]);
    }
};
?>
<x-mary-drawer wire:model="show" class="w-11/12 mx-auto" with-close-button close-on-escape>
    <button
        class="btn-close absolute right-4 top-4 cursor-pointer"
        data-tour="student-result-close"
        wire:click="close"
        aria-label="Fechar resultado da atividade"
    >
        <x-mary-icon name="o-x-mark" class="w-6 h-6 text-gray-600 hover:text-gray-800" />
    </button>

    <header class="flex items-center justify-between p-5 w-full">
        <h2 class="text-lg font-semibold text-gray-800">Resultado da atividade</h2>
    </header>

    <div id="student-result-drawer" data-activity-id="{{ $activit_task_id }}" class="w-11/12 mx-auto">
        <div id="student-result-status" class="mb-4 p-4 rounded-lg bg-gray-50 border border-gray-200">
            <div class="flex items-center justify-between gap-2 mb-2">
                <span class="text-sm font-semibold text-gray-700">Situação da atividade</span>
                @if ($statusLabel)
                    <x-mary-badge value="{{ $statusLabel }}" class="badge-sm {{ $statusBadge }}" />
                @endif
            </div>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-gray-600">
                <dt class="font-medium">Disponibilizada em</dt>
                <dd>{{ $availableAt ?? 'Sem data de início' }}</dd>
                <dt class="font-medium">Prazo</dt>
                <dd>{{ $deadline ?? 'Sem prazo' }}</dd>
                <dt class="font-medium">Primeiro acesso</dt>
                <dd>{{ $firstAccessedAt ?? 'Não acessada' }}</dd>
                <dt class="font-medium">Envio/conclusão</dt>
                <dd>{{ $answeredAt ?? 'Sem resposta' }}</dd>
            </dl>
        </div>

        @if (count($results) === 0)
            <p class="text-gray-500 py-6">Você ainda não respondeu esta atividade.</p>
        @else
            @if ($finalGrade !== null)
                <div id="student-result-final-grade"
                    class="mb-4 p-4 rounded-lg bg-blue-50 border border-blue-200 flex items-center justify-between">
                    <span class="text-sm font-semibold text-blue-800">Nota final da atividade</span>
                    <x-mary-badge value="{{ $finalGrade }}" class="badge-primary badge-lg" />
                </div>
            @endif
            <div class="flex flex-col gap-4">
                @foreach ($results as $result)
                    <div class="border border-gray-200 rounded-lg p-4" wire:key="result-{{ $result['id'] }}"
                        @if ($loop->first) id="student-result-answer" @endif>
                        <div class="font-medium text-gray-800 mb-2">
                            {{ $loop->iteration }}. {{ $result['question'] }}
                        </div>

                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs uppercase tracking-wide text-gray-500">Nota:</span>
                            @if ($result['corrected'])
                                <x-mary-badge value="{{ $result['grade'] }}" class="badge-primary" />
                            @else
                                <x-mary-badge value="Aguardando correção" class="badge-warning" />
                            @endif
                        </div>

                        <div class="text-sm text-gray-600">
                            <span class="font-semibold">Feedback:</span>
                            {{ $result['feedback'] ?: 'Sem feedback do professor.' }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-mary-drawer>
