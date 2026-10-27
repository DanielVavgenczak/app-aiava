<?php

use Livewire\Component;
use Livewire\Attributes\{On, Locked};
use App\Models\Teacher\ActivitTask;
use App\Support\{ActivityNotificationService, ActivityValue};
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public bool $openEdit = false;

    #[Locked]
    public ?string $activitTaskId = null;

    public string $title = '';
    public bool $isAvaliative = false;
    public $valueOfActivity = null;
    public $startDate = null;
    public $endDate = null;

    /** Atividade possui respostas de alunos? Bloqueia mudanças estruturais. */
    #[Locked]
    public bool $hasAnswers = false;

    #[On('open-edit-task')]
    public function open($id): void
    {
        $teacher = Auth::guard('teacher')->user();

        $activitTask = ActivitTask::query()
            ->withCount('studentAnwsers')
            ->whereKey($id)
            ->ownedByTeacher($teacher)
            ->first();

        if (! $activitTask) {
            $this->error('Atividade não encontrada ou sem permissão.');
            return;
        }

        $this->activitTaskId = $activitTask->id;
        $this->title = (string) $activitTask->title;
        $this->isAvaliative = (bool) $activitTask->is_avaliative;
        $this->valueOfActivity = $activitTask->total_value;
        $this->startDate = $activitTask->start?->format('Y-m-d');
        $this->endDate = $activitTask->end?->format('Y-m-d');
        $this->hasAnswers = $activitTask->student_anwsers_count > 0;
        $this->resetErrorBag();
        $this->openEdit = true;
    }

    public function updatedIsAvaliative(): void
    {
        if (! $this->isAvaliative) {
            $this->valueOfActivity = null;
        }

        $this->resetErrorBag('isAvaliative');
    }

    public function updatedValueOfActivity(): void
    {
        $raw = is_string($this->valueOfActivity) ? trim($this->valueOfActivity) : $this->valueOfActivity;

        if ($raw !== null && $raw !== '' && ActivityValue::toFloat($raw) > 100) {
            $this->addError('valueOfActivity', 'A nota máxima é 100.');
        }
    }

    public function save(): void
    {
        if (! $this->activitTaskId) {
            return;
        }

        $teacher = Auth::guard('teacher')->user();

        $activitTask = ActivitTask::query()
            ->withCount('studentAnwsers')
            ->whereKey($this->activitTaskId)
            ->ownedByTeacher($teacher)
            ->first();

        if (! $activitTask) {
            $this->error('Atividade não encontrada ou sem permissão.');
            return;
        }

        if ($this->startDate && $this->endDate && $this->endDate < $this->startDate) {
            $this->addError('endDate', 'A data final deve ser igual ou posterior à data de início.');
            return;
        }

        // Com respostas já registradas, não permite alterar a estrutura avaliativa
        // (isso invalidaria as notas lançadas). Apenas título e datas são editáveis.
        $structuralLocked = $activitTask->student_anwsers_count > 0;

        if ($structuralLocked) {
            $activitTask->update([
                'title' => $this->title,
                'start' => $this->startDate ? $this->startDate . ' 00:00:00' : null,
                'end' => $this->endDate ? $this->endDate . ' 23:59:59' : null,
            ]);

            $this->publishNotifications($activitTask);

            $this->openEdit = false;
            $this->dispatch('task-updated-success', message: 'Atividade atualizada com sucesso!');
            return;
        }

        $this->validate([
            'title' => 'required|string|min:3|max:255',
        ], [
            'title.required' => 'O título da atividade é obrigatório.',
            'title.min' => 'O título da atividade deve ter pelo menos 3 caracteres.',
        ]);

        if ($this->isAvaliative) {
            $raw = is_string($this->valueOfActivity) ? trim($this->valueOfActivity) : $this->valueOfActivity;

            if ($raw === null || $raw === '') {
                $this->addError('valueOfActivity', 'Informe o valor total da atividade avaliativa.');
                return;
            }

            if (! preg_match('/^\d{1,3}$/', (string) $raw)) {
                $this->addError('valueOfActivity', 'A nota deve ser um número inteiro entre 0 e 100.');
                return;
            }

            if (ActivityValue::toFloat($raw) > 100) {
                $this->addError('valueOfActivity', 'A nota máxima é 100.');
                return;
            }
        }

        $activitTask->update([
            'title' => $this->title,
            'is_avaliative' => $this->isAvaliative,
            'total_value' => $this->isAvaliative ? ActivityValue::toFloat($this->valueOfActivity) : null,
            'start' => $this->startDate ? $this->startDate . ' 00:00:00' : null,
            'end' => $this->endDate ? $this->endDate . ' 23:59:59' : null,
        ]);

        $this->publishNotifications($activitTask);

        $this->openEdit = false;
        $this->dispatch('task-updated-success', message: 'Atividade atualizada com sucesso!');
    }

    protected function publishNotifications(ActivitTask $activitTask): void
    {
        try {
            ActivityNotificationService::publish($activitTask);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function cancel(): void
    {
        $this->reset(['openEdit', 'activitTaskId', 'title', 'isAvaliative', 'valueOfActivity', 'startDate', 'endDate', 'hasAnswers']);
        $this->resetErrorBag();
    }
}; ?>

<x-mary-modal wire:model="openEdit" title="Editar atividade" class="backdrop-blur" box-class="w-2/3" id="edit-task-modal">
    <div class="flex flex-col gap-4">
        @if ($hasAnswers)
            <div class="alert alert-warning text-sm">
                <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5" />
                <span>
                    Esta atividade já possui respostas de alunos. Por isso, apenas o
                    <strong>título</strong> e as <strong>datas</strong> podem ser alterados.
                </span>
            </div>
        @endif

        <x-mary-input label="Título" wire:model="title" class="!outline-none" id="edit-task-title" />

        @unless ($hasAnswers)
            <div class="flex items-center gap-4">
                <x-mary-toggle label="Atividade avaliativa" wire:model.live="isAvaliative" id="edit-task-avaliativa" />
            </div>

            @if ($isAvaliative)
                <x-mary-input label="Valor total da atividade" placeholder="Ex: 100" inputmode="numeric"
                    wire:model="valueOfActivity" class="!outline-none" id="edit-task-value" />
                @error('valueOfActivity')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            @endif
        @endunless

        <div class="grid grid-cols-2 gap-4" id="edit-task-dates">
            <x-mary-input label="Início" type="date" wire:model="startDate" class="!outline-none" />
            <x-mary-input label="Fim" type="date" wire:model="endDate" class="!outline-none" />
        </div>
        @error('endDate')
            <span class="text-red-500 text-xs">{{ $message }}</span>
        @enderror
    </div>

    <x-slot:actions>
        <x-mary-button label="Cancelar" wire:click="cancel" />
        <x-mary-button label="Salvar" class="btn-primary" wire:click="save" spinner="save" />
    </x-slot:actions>
</x-mary-modal>
