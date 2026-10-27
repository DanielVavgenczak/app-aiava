<?php

use Livewire\Component;
use App\Models\Teacher\{ActivitTask, Student};
use App\Support\ActivityProgress;
use Livewire\Attributes\On;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public bool $open = false;

    public ?Student $student = null;

    /** Senha temporária em texto plano, exibida ao professor. */
    public ?string $temporaryPassword = null;

    /** Atividades disponibilizadas ao aluno com datas e situação. */
    public array $activities = [];

    #[On('teacher::view-student')]
    public function open($id)
    {
        $teacher = auth('teacher')->user();

        $student = Student::where('id', $id)
            ->whereHas('classe', fn ($q) => $q->where('teacher_id', $teacher?->id))
            ->first();

        if (! $student) {
            abort(404);
        }

        $this->student = $student;
        $this->temporaryPassword = $student->temporary_password;
        $this->activities = $this->buildActivities($student);
        $this->open = true;
    }

    /**
     * Atividades da disciplina do aluno, com primeiro acesso, envio e situação.
     */
    protected function buildActivities(Student $student): array
    {
        $activities = ActivitTask::query()
            ->where('discipline_id', $student->discipline_id)
            ->orderByDesc('created_at')
            ->get();

        $progress = ActivityProgress::forStudent($student, $activities);

        return $activities->map(function (ActivitTask $activity) use ($progress) {
            $item = $progress[$activity->id] ?? null;

            $status = $item['status'] ?? ActivityProgress::NOT_ACCESSED;
            $late = $item['late'] ?? false;

            return [
                'id' => $activity->id,
                'title' => 'Atividade de '.($activity->created_at?->format('d/m/Y') ?? '—'),
                'available_at' => ($item['available_at'] ?? $activity->start)?->format('d/m/Y H:i')
                    ?? $activity->created_at?->format('d/m/Y H:i'),
                'deadline' => $item['deadline']?->format('d/m/Y H:i'),
                'first_accessed_at' => $item['first_accessed_at']?->format('d/m/Y H:i'),
                'answered_at' => $item['answered_at']?->format('d/m/Y H:i'),
                'status' => $status,
                'status_label' => ActivityProgress::label($status, $late),
                'badge' => ActivityProgress::badgeClass($status, $late),
            ];
        })->all();
    }

    #[On('student-edit-cancel')]
    public function cancel()
    {
        $this->open = false;
        $this->student = null;
        $this->temporaryPassword = null;
        $this->activities = [];
    }

    public function regeneratePassword($studentId)
    {
        $teacher = auth('teacher')->user();

        $student = Student::where('id', $studentId)
            ->whereHas('classe', fn ($q) => $q->where('teacher_id', $teacher?->id))
            ->first();

        if (! $student) {
            abort(404);
        }

        $newPassword = $student->generateTemporaryPassword();

        $this->student = $student;
        $this->temporaryPassword = $newPassword;
        $this->open = true;

        $this->success('Nova senha gerada com sucesso!');
    }
}; ?>
<x-mary-modal
    wire:model="open"
    title="Visão geral do aluno"
    subtitle="Informações detalhadas do aluno"
    box-class="max-w-6xl"
>
    @if ($student)
        <div class="space-y-6">
            {{-- Informações básicas --}}
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold mb-3">Informações Básicas</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Nome Completo</label>
                        <p class="text-base">{{ $student->full_name }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">E-mail</label>
                        <p class="text-base">{{ $student->email }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Telefone</label>
                        <p class="text-base">{{ $student->phone ?? 'Não informado' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">CPF</label>
                        <p class="text-base">{{ $student->cpf ?? 'Não informado' }}</p>
                    </div>
                </div>
            </div>

            {{-- Acesso --}}
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold mb-3">Acesso do aluno</h3>
                <label class="text-sm font-medium text-gray-500">Senha temporária</label>
                @if ($temporaryPassword)
                    <div x-data="{ copied: false }" class="flex items-center gap-2 mt-1">
                        <code
                            x-ref="password"
                            class="px-3 py-1 rounded bg-white border border-gray-200 font-mono text-base"
                        >{{ $temporaryPassword }}</code>
                        <button
                            type="button"
                            class="btn btn-sm btn-ghost"
                            title="Copiar senha"
                            x-on:click="navigator.clipboard.writeText($refs.password.textContent); copied = true; setTimeout(() => copied = false, 1500)"
                        >
                            <span x-show="!copied"><x-mary-icon name="o-clipboard-document" class="w-4 h-4" /></span>
                            <span x-show="copied" x-cloak class="text-xs text-green-600">Copiado!</span>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">O aluno deve alterar esta senha no primeiro acesso.</p>
                @else
                    <p class="text-base text-gray-500 mt-1">O aluno já alterou a senha para uma de uso pessoal.</p>
                @endif
            </div>

            {{-- Atividades disponibilizadas ao aluno --}}
            <div class="bg-gray-50 p-4 rounded-lg" id="student-activities-section">
                <h3 class="text-lg font-semibold mb-3">Atividades</h3>
                @if (count($activities) === 0)
                    <p class="text-sm text-gray-500">
                        Nenhuma atividade disponibilizada para este aluno.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-sm w-full">
                            <thead>
                                <tr>
                                    <th>Atividade</th>
                                    <th>Disponibilizada em</th>
                                    <th>Prazo</th>
                                    <th>Primeiro acesso</th>
                                    <th>Envio/conclusão</th>
                                    <th>Situação</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($activities as $activity)
                                    <tr wire:key="student-activity-{{ $activity['id'] }}">
                                        <td class="whitespace-nowrap">{{ $activity['title'] }}</td>
                                        <td class="whitespace-nowrap">{{ $activity['available_at'] ?? '—' }}</td>
                                        <td class="whitespace-nowrap">{{ $activity['deadline'] ?? 'Sem prazo' }}</td>
                                        <td class="whitespace-nowrap">{{ $activity['first_accessed_at'] ?? '—' }}</td>
                                        <td class="whitespace-nowrap">{{ $activity['answered_at'] ?? '—' }}</td>
                                        <td>
                                            <x-mary-badge value="{{ $activity['status_label'] }}"
                                                class="badge-sm {{ $activity['badge'] }}" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Ações --}}
            <div class="flex justify-end gap-2 pt-4 border-t">
                <button
                    wire:click="regeneratePassword('{{ $student->id }}')"
                    wire:loading.attr="disabled"
                    wire:target="regeneratePassword"
                    class="btn btn-warning"
                >
                    <x-mary-icon name="o-arrow-path" class="w-4 h-4" />
                    Gerar Nova Senha
                </button>

                <button
                    wire:click="open = false"
                    class="btn btn-ghost"
                >
                    Fechar
                </button>
            </div>
        </div>
    @endif
</x-mary-modal>
