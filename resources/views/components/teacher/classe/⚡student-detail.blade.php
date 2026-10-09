<?php

use Livewire\Component;
use App\Models\Teacher\Student;
use Livewire\Attributes\On;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public bool $open = false;

    public ?Student $student = null;

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
        $this->open = true;
    }

    public function mount()
    {
        //
    }

    #[On('student-edit-cancel')]
    public function cancel()
    {
        $this->open = false;
        $this->student = null;
    }

    public function regeneratePassword($studentId)
    {
        $teacher = auth('teacher')->user();

        $student = Student::where('id', $studentId)
            ->whereHas('classe', fn ($q) => $q->where('teacher_id', $teacher?->id))
            ->firstOrFail();

        $newPassword = $this->generateRandomPassword();
        $student->password = bcrypt($newPassword);
        $student->temporary_password = $newPassword;
        $student->must_change_password = true;
        $student->password_changed_at = null;
        $student->save();

        $this->student = $student;

        $this->dispatch('password-regenerated', message: 'Nova senha gerada com sucesso!');
    }

    private function generateRandomPassword($length = 8)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $password;
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
                        <p class="text-base">{{ $student->telephone ?? 'Não informado' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">CPF</label>
                        <p class="text-base">{{ $student->cpf ?? 'Não informado' }}</p>
                    </div>
                </div>
            </div>

            {{-- Ações --}}
            <div class="flex justify-end gap-2 pt-4 border-t">
                @if($student->must_change_password)
                    <button
                        wire:click="regeneratePassword({{ $student->id }})"
                        class="btn btn-warning"
                    >
                        <x-mary-icon name="o-arrow-path" class="w-4 h-4" />
                        Gerar Nova Senha
                    </button>
                @endif

                <button
                    wire:click="open = false"
                    class="btn btn-ghost"
                >
                    Fechar
                </button>
            </div>
        </div>
    @endif

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Copiado!');
            });
        }
    </script>
</x-mary-modal>
