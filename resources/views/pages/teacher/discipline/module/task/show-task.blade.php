<?php

use Livewire\Component;
use Livewire\Attributes\{On, Computed, Locked};
use App\Models\Teacher\{ActivitTask, Student};
use App\Support\ActivityValue;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    public bool $showTaskModule = false;
    public string $selectedTab = 'activity-tab';
    #[Locked]
    public ?ActivitTask $activitTask = null;
    #[Locked]
    public string $taskId = '';

    #[Computed]
    public function activityHeading(): string
    {
        $title = trim((string) $this->activitTask?->title);

        if ($title === '' || str_starts_with($title, 'atividade ')) {
            return 'Atividade';
        }

        return $title;
    }

    #[Computed]
    public function countAnswers()
    {
        return $this->activitTask?->studentAnwsers()->count() ?? 0;
    }

    /** Total de alunos matriculados na disciplina da atividade. */
    #[Computed]
    public function totalStudents()
    {
        $disciplineId = $this->activitTask?->discipline_id;

        if (! $disciplineId) {
            return 0;
        }

        return Student::where('discipline_id', $disciplineId)->count();
    }

    /** Alunos distintos que já responderam ao menos uma questão da atividade. */
    #[Computed]
    public function answeredStudents()
    {
        return $this->activitTask?->studentAnwsers()
            ->distinct('student_id')
            ->count('student_id') ?? 0;
    }

    /** Edit **/
    #[Locked]
    public ?int $editingQuestionIndex = null;
    #[Locked]
    public ?int $editingOptionIndex = null;
    public string $editingQuestionTitle = '';
    public string $editingOptionText = '';

    public function mount(?string $initialActivityId = null, ?string $initialTab = null): void
    {
        if ($initialActivityId) {
            $this->open($initialActivityId, $initialTab);
        }
    }

    #[On('task-show')]
    public function open($activit_id, $tab = null)
    {
        $teacher = Auth::guard('teacher')->user();

        $activitTask = ActivitTask::with([
            'module:id,title',
            'tasks' => function ($query) {
                $query->with(['studentAnwsers', 'options' => fn ($q) => $q->ordered()])->ordered();
            },
        ])
            ->whereKey($activit_id)
            ->ownedByTeacher($teacher)
            ->first();

        if (! $activitTask) {
            $this->reset([
                'activitTask', 'taskId', 'editingQuestionIndex',
                'editingOptionIndex', 'editingQuestionTitle', 'editingOptionText',
            ]);
            $this->showTaskModule = false;
            $this->error('Atividade não encontrada ou você não tem permissão para acessá-la.');
            return;
        }

        $this->activitTask = $activitTask;
        $this->taskId = $activitTask->id;
        $this->showTaskModule = true;

        if (in_array($tab, ['activity-tab', 'deliveries-tab'], true)) {
            $this->selectedTab = $tab;
        }
    }

    private function guardLoadedTask(): bool
    {
        if (! $this->activitTask) {
            $this->error('Atividade não carregada.');
            $this->showTaskModule = false;
            return false;
        }

        return true;
    }

    public function startEditingQuestion(int $index)
    {
        if (! $this->guardLoadedTask()) {
            return;
        }

        if (! isset($this->activitTask->tasks[$index])) {
            $this->error('Questão inválida.');
            return;
        }

        $this->editingQuestionIndex = $index;
        $this->editingQuestionTitle = $this->activitTask->tasks[$index]['question'];
    }

    public function saveQuestionEdit()
    {
        if (! $this->guardLoadedTask()) {
            return;
        }

        $this->validate(['editingQuestionTitle' => 'required|min:8']);
        $taskEdit = $this->activitTask->tasks[$this->editingQuestionIndex] ?? null;
        if (! $taskEdit) {
            $this->error('Questão inválida.');
            $this->cancelEditing();
            return;
        }

        $taskEdit->update([
            'question' => $this->editingQuestionTitle,
        ]);
        $this->success('Questão editada com sucesso');
        $this->cancelEditing();
    }

    public function startEditingOption(int $qIndex, int $optIndex)
    {
        if (! $this->guardLoadedTask()) {
            return;
        }

        $task = $this->activitTask->tasks[$qIndex] ?? null;
        if (! $task || ! isset($task->options[$optIndex])) {
            $this->error('Opção inválida.');
            return;
        }

        $this->editingQuestionIndex = $qIndex;
        $this->editingOptionIndex = $optIndex;
        $this->editingOptionText = $task->options[$optIndex]->text;
    }

    public function saveOptionEdit()
    {
        if (! $this->guardLoadedTask()) {
            return;
        }

        $this->validate(['editingOptionText' => 'required|min:1|max:255']);
        $taskOptionEdit = $this->activitTask->tasks[$this->editingQuestionIndex] ?? null;
        $option = $taskOptionEdit?->options[$this->editingOptionIndex] ?? null;
        if (! $option) {
            $this->error('Opção inválida.');
            $this->cancelEditing();
            return;
        }

        $option->update([
            'text' => $this->editingOptionText,
        ]);
        $this->cancelEditing();
        $this->success('Sucesso ao editar opção');
    }

    public function updateCorrectOption(int $qIndex, int $optIndex)
    {
        if (! $this->guardLoadedTask()) {
            return;
        }

        $task = $this->activitTask->tasks[$qIndex] ?? null;
        if (! $task || ! isset($task->options[$optIndex])) {
            $this->error('Opção inválida.');
            return;
        }

        foreach ($task->options as $index => $option) {
            $option->update(['correct' => $index === $optIndex]);
        }

        $this->activitTask->load('tasks.options');
        $this->success('Alternativa correta atualizada.');
    }

    public function cancelEditing()
    {
        $this->editingQuestionIndex = null;
        $this->editingOptionIndex = null;
        $this->editingQuestionTitle = '';
        $this->editingOptionText = '';
    }

    public function cancelMakeTask()
    {
        $this->showTaskModule = false;
        $this->dispatch('close-task-show');
    }

    public function updatedShowTaskModule($value): void
    {
        // O drawer pode ser fechado pelo MaryUI (overlay, ESC ou botão X) sem
        // passar por `cancelMakeTask`. Sempre que ele fechar, avisamos o
        // componente pai para voltar a exibir as opções da disciplina.
        if (! $value) {
            $this->dispatch('close-task-show');
        }
    }

    #[On('task-deleted')]
    public function taskDeleted(): void
    {
        $this->activitTask = null;
        $this->taskId = '';
        $this->cancelMakeTask();
    }

    #[On('task-updated-success')]
    public function taskUpdated(): void
    {
        if ($this->taskId) {
            $this->open($this->taskId);
        }
    }

    public function closeToskDelete()
    {

        $this->showTaskModule = false;
    }

    public function removeQuestion(int $index)
    {
        if (! $this->guardLoadedTask()) {
            return;
        }

        $taskToRemove = $this->activitTask->tasks->get($index);
        if (! $taskToRemove) {
            $this->error('Questão inválida.');
            return;
        }

        // As respostas do aluno têm FK em cascata para a questão. Remover uma
        // questão já respondida apagaria as submissões: bloqueia nesse caso.
        if ($taskToRemove->studentAnwsers()->exists()) {
            $this->error('Não é possível remover uma questão que já possui respostas de alunos.');
            return;
        }

        $taskToRemove->options()->delete();
        $taskToRemove->delete();

        $this->open($this->activitTask->id);
        $this->success('Questão removida com sucesso.');
    }
}; ?>
<x-mary-drawer wire:model="showTaskModule" class="w-11/12 max-w-6xl mx-auto p-0! z-50" close-on-escape
    without-backdrop-close x-on:close="$wire.dispatch('close-task-show')"
    role="dialog" aria-modal="true" aria-labelledby="teacher-activity-heading">
    <div id="teacher-activity-drawer" class="flex h-dvh flex-col overflow-hidden bg-gray-50">
        <header id="teacher-activity-header" class="shrink-0 border-b border-gray-200 bg-white px-5 py-4 sm:px-7">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex min-w-0 items-start gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
                        aria-hidden="true">
                        <x-mary-icon name="o-clipboard-document-list" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-blue-600">Painel da atividade</p>
                        <h2 id="teacher-activity-heading"
                            class="mt-0.5 truncate text-xl font-semibold tracking-tight text-gray-900">
                            {{ $this->activityHeading }}
                        </h2>
                        <p class="mt-1 truncate text-sm text-gray-500">
                            {{ $activitTask?->module?->title ?? 'Atividade da disciplina' }}
                        </p>
                    </div>
                </div>

                @if ($activitTask)
                    <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                        <button type="button"
                            x-on:click="$dispatch('open-edit-task', {id:'{{ $activitTask->id }}'})"
                            class="btn btn-ghost btn-sm gap-2 border border-gray-200 bg-white text-gray-700 hover:bg-gray-50"
                            aria-label="Editar atividade">
                            <x-mary-icon name="o-pencil-square" class="h-4 w-4" />
                            Editar
                        </button>
                        <button type="button"
                            x-on:click="$dispatch('open-modal-delete', {id:'{{ $activitTask->id }}'})"
                            class="btn btn-ghost btn-sm gap-2 border border-red-200 bg-white text-red-600 hover:bg-red-50"
                            aria-label="Excluir atividade">
                            <x-mary-icon name="o-trash" class="h-4 w-4" />
                            Excluir
                        </button>
                        <button type="button" wire:click="cancelMakeTask" data-tour="teacher-activity-close"
                            class="btn btn-ghost btn-sm gap-2 border border-gray-200 bg-white text-gray-700 hover:bg-gray-50"
                            title="Fechar atividade" aria-label="Fechar atividade">
                            <x-mary-icon name="o-x-mark" class="h-4 w-4" />
                            Fechar
                        </button>
                        <livewire:pages::teacher.discipline.module.task.delete-task
                            :activittask="$activitTask"
                            wire:key="activity-{{$activitTask->id}}" />
                    </div>
                @endif
            </div>

            @if ($activitTask)
                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                    @if ($activitTask->total_value !== null)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 font-medium text-emerald-700">
                            <x-mary-icon name="o-academic-cap" class="h-3.5 w-3.5" />
                            Avaliativa · {{ ActivityValue::format($activitTask->total_value) }} pts
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 font-medium text-gray-600">
                            <x-mary-icon name="o-document-text" class="h-3.5 w-3.5" />
                            Não avaliativa
                        </span>
                    @endif

                    <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 font-medium text-gray-600">
                        <x-mary-icon name="o-calendar" class="h-3.5 w-3.5" />
                        @if ($activitTask->start || $activitTask->end)
                            {{ $activitTask->start?->format('d/m/Y') ?? 'Aberto' }}
                            →
                            {{ $activitTask->end?->format('d/m/Y') ?? 'sem prazo' }}
                        @else
                            Sem prazo
                        @endif
                    </span>

                    <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 font-medium text-green-700">
                        <x-mary-icon name="o-inbox-arrow-down" class="h-3.5 w-3.5" />
                        {{ $this->answeredStudents }}/{{ $this->totalStudents }} responderam
                    </span>
                </div>
            @endif
        </header>

        <div class="min-h-0 flex-1 overflow-y-auto">
            <div class="mx-auto max-w-5xl px-5 py-5 sm:px-7">
                <div id="teacher-activity-tabs">
                    <x-mary-tabs wire:model="selectedTab"
                        label-class="font-semibold pb-2"
                        active-class="border-b-2 border-blue-500 text-blue-600"
                        label-div-class="flex gap-1 overflow-x-auto border-b border-gray-200"
                        tabs-class="relative w-full">
                        <x-mary-tab name="activity-tab" label="Atividade" icon="o-document-text">
                            <div class="space-y-3 pt-2">
                                @if ($activitTask && $activitTask->tasks->isNotEmpty())
                                    @foreach ($activitTask->tasks as $index => $task)
                                        <section wire:key="task-{{ $task->id }}" data-tour="teacher-task"
                                            @class([
                                                'rounded-2xl border border-gray-200 bg-white p-5 shadow-sm',
                                            ])>
                                            @if ($editingQuestionIndex === $index && $editingOptionIndex === null)
                                                <div class="flex items-start gap-3.5">
                                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white"
                                                        aria-hidden="true">
                                                        {{ $task->order }}
                                                    </span>
                                                    <div class="min-w-0 flex-1">
                                                        <label for="teacher-edit-question-{{ $task->id }}" class="sr-only">
                                                            Editar enunciado da questão {{ $task->order }}
                                                        </label>
                                                        <textarea id="teacher-edit-question-{{ $task->id }}"
                                                            wire:model="editingQuestionTitle"
                                                            wire:keydown.enter="saveQuestionEdit"
                                                            rows="3"
                                                            class="textarea textarea-bordered w-full leading-relaxed focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                                            autofocus></textarea>
                                                        <div class="mt-2 flex flex-wrap gap-2">
                                                            <x-mary-button label="Salvar" wire:click="saveQuestionEdit"
                                                                icon="o-check" class="btn-sm btn-primary" spinner />
                                                            <x-mary-button label="Cancelar" wire:click="cancelEditing"
                                                                icon="o-x-mark" class="btn-sm btn-ghost" spinner />
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                    <div class="flex min-w-0 items-start gap-3.5">
                                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white"
                                                            aria-hidden="true">
                                                            {{ $task->order }}
                                                        </span>
                                                        <div class="min-w-0">
                                                            <div class="mb-2 flex flex-wrap items-center gap-2">
                                                                <span class="badge badge-sm border-0 bg-gray-100 text-gray-600">
                                                                    {{ $task->type === 'multiple_choice' ? 'Múltipla escolha' : 'Dissertativa' }}
                                                                </span>
                                                                @if ($task->value !== null)
                                                                    <span class="badge badge-sm border-0 bg-emerald-50 text-emerald-700">
                                                                        {{ ActivityValue::format($task->value) }} pts
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            <h3 class="whitespace-pre-line text-base font-semibold leading-relaxed text-gray-900">
                                                                {{ $task->question }}
                                                            </h3>
                                                        </div>
                                                    </div>

                                                    <div data-tour="teacher-task-actions"
                                                        class="flex flex-wrap items-center gap-2 sm:justify-end">
                                                        <button type="button" wire:click="startEditingQuestion({{ $index }})"
                                                            data-tour="teacher-task-edit"
                                                            class="btn btn-ghost btn-sm gap-2 border border-gray-200 bg-white text-gray-700 hover:bg-gray-50"
                                                            aria-label="Editar questão {{ $task->order }}">
                                                            <x-mary-icon name="o-pencil-square" class="h-4 w-4" />
                                                            Editar
                                                        </button>
                                                        <button type="button"
                                                            x-on:click="$dispatch('event-teacher-show-student-answer',{task_id: '{{ $task->id }}'})"
                                                            data-tour="teacher-task-answers"
                                                            class="btn btn-ghost btn-sm gap-2 border border-gray-200 bg-white text-gray-700 hover:bg-gray-50"
                                                            aria-label="Ver respostas da questão {{ $task->order }}">
                                                            <x-mary-icon name="o-inbox-arrow-down" class="h-4 w-4" />
                                                            Respostas
                                                            <span class="badge badge-sm border-0 bg-gray-100 text-gray-600">
                                                                {{ $task->studentAnwsers->count() }}
                                                            </span>
                                                        </button>
                                                        <button type="button" wire:click="removeQuestion({{ $index }})"
                                                            data-tour="teacher-task-remove"
                                                            class="btn btn-ghost btn-sm gap-2 text-red-600 hover:bg-red-50"
                                                            aria-label="Remover questão {{ $task->order }}">
                                                            <x-mary-icon name="o-trash" class="h-4 w-4" />
                                                            Remover
                                                        </button>
                                                    </div>
                                                </div>
                                            @endif

                                            @if ($task->type === 'multiple_choice')
                                                <div class="mt-4 space-y-2 sm:pl-12">
                                                    @foreach ($task->options as $optIndex => $option)
                                                        @php
                                                            $optionId = 'teacher-option-'.$task->id.'-'.$option->id;
                                                            $correctId = 'teacher-correct-'.$task->id.'-'.$option->id;
                                                        @endphp
                                                        @if ($editingQuestionIndex === $index && $editingOptionIndex === $optIndex)
                                                            <div class="flex flex-col gap-2 rounded-xl border border-blue-200 bg-blue-50/50 p-3 sm:flex-row sm:items-center">
                                                                <span class="flex size-6 shrink-0 items-center justify-center rounded-md bg-white text-[11px] font-bold uppercase text-blue-700"
                                                                    aria-hidden="true">
                                                                    {{ chr(65 + $optIndex) }}
                                                                </span>
                                                                <label for="teacher-edit-option-{{ $task->id }}-{{ $option->id }}" class="sr-only">
                                                                    Editar alternativa {{ chr(65 + $optIndex) }}
                                                                </label>
                                                                <x-mary-input id="teacher-edit-option-{{ $task->id }}-{{ $option->id }}"
                                                                    wire:model="editingOptionText"
                                                                    wire:keydown.enter="saveOptionEdit"
                                                                    class="input input-bordered w-full bg-white focus:border-blue-500"
                                                                    autofocus />
                                                                <div class="flex shrink-0 gap-2">
                                                                    <x-mary-button label="Salvar" wire:click="saveOptionEdit"
                                                                        icon="o-check" class="btn-sm btn-primary" spinner />
                                                                    <x-mary-button label="Cancelar" wire:click="cancelEditing"
                                                                        icon="o-x-mark" class="btn-sm btn-ghost" spinner />
                                                                </div>
                                                            </div>
                                                        @else
                                                            <div @class([
                                                                'flex items-start gap-3 rounded-xl border p-3 transition-colors',
                                                                'border-emerald-200 bg-emerald-50/60' => $option->correct,
                                                                'border-gray-200 bg-white hover:border-blue-300 hover:bg-blue-50/30' => ! $option->correct,
                                                            ])>
                                                                <span @class([
                                                                    'flex size-6 shrink-0 items-center justify-center rounded-md text-[11px] font-bold uppercase',
                                                                    'bg-emerald-100 text-emerald-700' => $option->correct,
                                                                    'bg-gray-100 text-gray-600' => ! $option->correct,
                                                                ]) aria-hidden="true">
                                                                    {{ chr(65 + $optIndex) }}
                                                                </span>
                                                                <label for="{{ $correctId }}"
                                                                    class="mt-0.5 inline-flex cursor-pointer items-center"
                                                                    title="Marcar esta alternativa como correta">
                                                                    <span class="sr-only">Marcar alternativa {{ chr(65 + $optIndex) }} como correta</span>
                                                                    <input id="{{ $correctId }}" type="radio"
                                                                        name="correct_option_{{ $index }}"
                                                                        wire:change="updateCorrectOption({{ $index }}, {{ $optIndex }})"
                                                                        @checked($option->correct)
                                                                        class="radio radio-success radio-sm" />
                                                                </label>
                                                                <button type="button"
                                                                    wire:click="startEditingOption({{ $index }}, {{ $optIndex }})"
                                                                    class="min-w-0 flex-1 text-left text-sm leading-relaxed text-gray-700 hover:text-blue-700"
                                                                    aria-label="Editar alternativa {{ chr(65 + $optIndex) }}">
                                                                    {{ $option->text }}
                                                                </button>
                                                                @if ($option->correct)
                                                                    <span class="badge badge-sm shrink-0 border-0 bg-emerald-100 text-emerald-700">
                                                                        Correta
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="mt-3 flex items-center gap-2 text-xs text-gray-500 sm:pl-12">
                                                    <x-mary-icon name="o-pencil" class="h-4 w-4" />
                                                    Questão dissertativa — correção e feedback ficam na aba Entregas.
                                                </p>
                                            @endif
                                        </section>
                                    @endforeach
                                @else
                                    <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                                        <x-mary-icon name="o-document-text" class="mx-auto h-10 w-10 text-gray-400" />
                                        <h3 class="mt-3 text-sm font-semibold text-gray-800">Nenhuma questão cadastrada</h3>
                                        <p class="mt-1 text-sm text-gray-500">Edite a atividade para adicionar questões.</p>
                                    </div>
                                @endif
                            </div>
                        </x-mary-tab>

                        <x-mary-tab name="deliveries-tab" label="Entregas" icon="o-inbox-arrow-down">
                            <div class="space-y-4 p-1 pt-2">
                                <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-4">
                                    <div class="flex items-start gap-3">
                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-white text-blue-600"
                                            aria-hidden="true">
                                            <x-mary-icon name="o-inbox-arrow-down" class="h-5 w-5" />
                                        </span>
                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-900">Entregas e correção</h3>
                                            <p class="mt-1 text-sm leading-relaxed text-gray-600">
                                                Selecione um aluno para revisar as respostas, registrar notas e enviar feedback.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                @if ($activitTask)
                                    <livewire:pages::teacher.discipline.module.task.student-deliveries
                                        :activity-id="$activitTask->id"
                                        wire:key="student-deliveries-{{ $activitTask->id }}" />
                                @endif
                            </div>
                        </x-mary-tab>
                    </x-mary-tabs>
                </div>
            </div>
        </div>

        @if ($activitTask)
            <livewire:pages::teacher.discipline.module.task.student-anwsers
                wire:key="student-anwsers-{{ $activitTask->id }}" />
        @endif
    </div>
</x-mary-drawer>
