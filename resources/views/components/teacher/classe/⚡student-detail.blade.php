<?php

use Livewire\Component;
use App\Models\Teacher\{Classe, Student};
use Livewire\Attributes\{On, Validate};
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    public bool $open = false;
    public Student $student;

    #[On('teacher::view-student')]
    public function open($id)
    {
        if ($this->student->id === $id || !$this->student->id === '') {
            $this->open = true;
        }
    }

    public function mount(Student $student)
    {
        $this->fill($student);
    }

    // public function updateStudent()
    // {
    //     try {
    //         $update = [
    //             'full_name' => $this->studentName,
    //             'email' => $this->studentEmail,
    //         ];
    //         $this->student->update($update);
    //         $this->dispatch('student-event', type: 'update', message: 'Aluno atualizado com sucesso!');
    //         $this->open = false;
    //     } catch (\Exception $e) {
    //         $this->open = false;
    //         if (!empty($e->getMessage())) {
    //             $this->error('Erro interno no sistema', 'Error interno no sistema para essa funcionalidade por favor tente novamente mais tarde', position: 'toast-top toast-end', timeout: 8000);
    //         }
    //     }
    // }

    #[On('student-edit-cancel')]
    public function cancel()
    {
        $this->open = false;
    }

    public function regeneratePassword($studentId)
    {
        $student = Student::findOrFail($studentId);

        // Gera nova senha
        $newPassword = $this->generateRandomPassword();
        $student->password = bcrypt($newPassword);
        $student->temporary_password = $newPassword;
        $student->must_change_password = true;
        $student->password_changed_at = null;
        $student->save();

        // Atualiza o estudante na modal
        $this->student = $student;

        $this->dispatch('password-regenerated',
            message: 'Nova senha gerada com sucesso!'
        );
    }

}; ?>
<x-mary-modal
    wire:model="open"
    title="Visão geral do aluno"
    subtitle="Informações detalhadas do aluno"
    box-class="max-w-6xl"
>
    <div class="space-y-6">
        {{-- Informações Básicas --}}
        <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="font-semibold text-lg mb-3">Informações Pessoais</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Nome Completo</p>
                    <p class="font-medium">{{ $student->full_name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Email</p>
                    <p class="font-medium">{{ $student->email }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Telefone</p>
                    <p class="font-medium">{{ $student->phone ?? 'Não informado' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <span class="badge {{ $student->status === 'active' ? 'badge-success' : 'badge-warning' }}">
                        {{ $student->status === 'active' ? 'Ativo' : 'Inativo' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Informações Acadêmicas --}}
        <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="font-semibold text-lg mb-3">Informações Acadêmicas</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Turma</p>
                    <p class="font-medium">{{ $student->classe->name ?? 'Não definida' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Disciplina</p>
                    <p class="font-medium">{{ $student->discipline->title ?? 'Não definida' }}</p>
                </div>
            </div>
        </div>

        {{-- Credenciais de Acesso --}}
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
            <h3 class="font-semibold text-lg mb-3 text-blue-700">Credenciais de Acesso</h3>

            @if($student->must_change_password)
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-3">
                    <p class="text-sm text-yellow-700">
                        ⚠️ Aluno ainda não alterou a senha temporária
                    </p>
                </div>

                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-600">Email de Acesso</p>
                        <div class="flex items-center gap-2">
                            <span class="font-mono">{{ $student->email }}</span>
                            <button
                                onclick="copyToClipboard('{{ $student->email }}')"
                                class="btn btn-xs btn-ghost"
                                title="Copiar email"
                            >
                                <x-mary-icon name="o-clipboard" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600">Senha Temporária</p>
                        <div class="flex items-center gap-2">
                            <span class="font-mono font-bold text-lg">{{ $student->temporary_password }}</span>
                            <button
                                onclick="copyToClipboard('{{ $student->temporary_password }}')"
                                class="btn btn-xs btn-ghost"
                                title="Copiar senha"
                            >
                                <x-mary-icon name="o-clipboard" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            @else
                <div class="flex items-center gap-2 text-green-700">
                    <x-mary-icon name="o-check-circle" class="w-5 h-5" />
                    <p>Senha já alterada pelo aluno em {{ $student->password_changed_at->format('d/m/Y H:i') }}</p>
                </div>
            @endif
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

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Feedback visual opcional
                alert('Copiado!');
            });
        }
    </script>
</x-mary-modal>
