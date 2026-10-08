<?php

use Livewire\Component;
use App\Models\Teacher\{Classe, Student};
use Livewire\Attributes\{On, Validate};
use Illuminate\Support\Facades\Hash;
use Mary\Traits\Toast;
use App\Traits\GeneratePasswordStudent;
use Illuminate\Support\Facades\Log;
new class extends Component {
    use Toast, GeneratePasswordStudent;
    public bool $open = false;

    #[Validate('required', message: 'Nome é obrigatório!')]
    #[Validate('min:5', message: 'O nome deve ter no minimo 5 caracteres')]
    public string $studentName = '';
    #[Validate('required', message: 'O e-mail é obrigatório!')]
    #[Validate('email', message: 'O e-mail deve ser valido!')]
    public string $studentEmail = '';

    #[Validate('required', message: 'O telefone é obrigatório!')]
    public string $studentPhone = '';
    public array $students = [];
    public string $classeId;
    public string $disciplineId;

    public Classe $classe;

    #[On('teacher::new-student')]
    public function open($classe_id, $discipline_id)
    {
        $this->open = true;
        $this->classeId = $classe_id;
        $this->disciplineId = $discipline_id;
    }
    public function addStudent()
    {
        $validated = $this->validate([
            'studentName' => 'required|string|min:3',
            'studentEmail' => 'required|email',
            'studentPhone' => 'required'
        ]);
         // ✅ VERIFICA SE EMAIL JÁ EXISTE NA LISTA ATUAL
        $emailExistsInList = collect($this->students)->contains(function ($student) use ($validated) {
            return $student['email'] === $validated['studentEmail'];
        });

        if ($emailExistsInList) {
            $this->addError('studentEmail', 'Este email já foi adicionado na lista atual.');
            return;
        }

        // ✅ VERIFICA SE EMAIL JÁ EXISTE NO BANCO (para o mesmo teacher)
        $emailExistsInDb = Student::where('teacher_id', Auth::guard('teacher')->user()->id)
            ->where('email', $validated['studentEmail'])
            ->exists();

        if ($emailExistsInDb) {
            $this->addError('studentEmail', 'Este email já está cadastrado para outro aluno seu.');
            return;
        }

        $students = [
            'name' => $validated['studentName'],
            'email' => $validated['studentEmail'],
            'phone' => $validated['studentPhone'],
        ];

        $this->students[] = $students;
        $this->reset(['studentName', 'studentEmail', 'studentPhone']);
    }

    /**
     * Remove student in list
     * **/
    public function removeStudent(int $index)
    {
        unset($this->students[$index]);
        $this->students = array_values($this->students);
    }

    public function storeStudent()
    {
        try {
            DB::transaction(function () {
                foreach ($this->students as $student) {
                    $temporaryPassword = $this->generateRandomPassword(); // Senha temporária padrão
                    Student::create([
                        'full_name' => $student['name'],
                        'email' => $student['email'],
                        'phone' => $student['phone'],
                        'classe_id' => $this->classeId,
                        'teacher_id' => Auth::guard('teacher')->user()->id,
                        'discipline_id' => $this->disciplineId,
                        'password' => Hash::make($temporaryPassword),
                        'temporary_password' => $temporaryPassword,
                        'must_change_password' => true,
                    ]);
                }
            });
            $this->reset('studentName', 'studentEmail','studentPhone','students');
            $this->open = false;
            $this->dispatch('student-event', type: 'created', message: 'Aluno cadastrado com sucesso!');
        } catch (\Exception $e) {
            $this->open = false;
            if (str_contains($e->getMessage(), 'students_phone_non_null_unique')) {
                  $this->error(
                      'Telefone já cadastrado',
                      'Este número de telefone já está em uso por outro aluno.',
                      position: 'toast-top toast-end ',
                      timeout: 8000
                  );
              } else if (str_contains($e->getMessage(), 'students_teacher_id_email_unique')) {

                  $this->error(
                      'Email já cadastrado',
                      'Este email já está em uso para um dos seus alunos.',
                      position: 'toast-top toast-end',
                      timeout: 8000
                  );
              } else {
                  $this->error(
                      'Erro ao cadastrar aluno',
                      'Ocorreu um erro inesperado. Tente novamente.',
                      position: 'toast-top toast-end',
                      timeout: 8000
                  );
              }
        }
    }
    #[On('student-register-cancel')]
    public function cancel()
    {
        $this->reset('studentName', 'studentEmail', 'students');
        $this->open = false;
    }
}; ?>

<x-mary-modal wire:model="open" title="Aluno" subtitle="Registrar aluno" box-class="w-11/12  max-w-6xl">
    <form wire:submit.prevent="storeStudent" class="flex gap-4 w-full">
    {{-- Card Esquerda: Inputs --}}
    <div class="w-1/2 bg-white rounded-lg shadow p-4" id="student-inputs" >
        <div class="flex flex-col gap-4">
            <div id="student-input-full_name">
                <label class="text-sm font-bold text-gray-600" for="input-student-full_name">Nome completo</label>
                <input class="input !outline-none focus:border-gray-300 w-full mt-1"
                    type="text"
                    placeholder="Nome do aluno" id="input-student-full_name"
                    wire:model="studentName"/>
                @error("studentName")
                    <span class="text-xs text-red-500">{{ $message }}</span>
                @enderror
            </div>
            <div id="student-input-phone">
                <label class="text-sm font-bold text-gray-600" for="input-student-input-phone">Telefone</label>
                <input class="input !outline-none focus:border-gray-300 w-full mt-1"
                    type="text"
                    placeholder="XX XXXXX-XXXX" id="input-student-input-phone"
                    x-data
                    x-on:input="
                        let v = $event.target.value.replace(/\D/g, '').slice(0, 11);
                        if (v.length > 6) {
                            v = v.slice(0,2) + ' ' + v.slice(2,7) + '-' + v.slice(7);
                        } else if (v.length > 2) {
                            v = v.slice(0,2) + ' ' + v.slice(2);
                        }
                        $event.target.value = v;
                        $wire.set('studentPhone', v);
                    "
                    wire:model="studentPhone"/>
                @error("studentPhone")
                    <span class="text-xs text-red-500">{{ $message }}</span>
                @enderror
            </div>
            <div id="student-input-email">
                <label class="text-sm font-bold text-gray-600" for="input-student-input-email">E-mail</label>
                <input class="input !outline-none focus:border-gray-300 w-full mt-1"
                    type="email"
                    placeholder="E-mail do aluno" id="input-student-input-email"
                    wire:model="studentEmail"/>
                @error("studentEmail")
                    <span class="text-xs text-red-500" id="input-student-input-email-error">{{ $message }}</span>
                @enderror
            </div>

            <div id="add-student-to-list-register">
                <button
                id="action-add-student"
                class="btn bg-blue-400 text-gray-50 hover:bg-blue-500 w-full"
                type="button"
                x-on:click="$wire.addStudent">
                    <x-mary-icon name="o-plus" class="w-5 h-5"/>
                    <span>Adicionar aluno</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Card Direita: Lista de Alunos --}}
    <div class="w-full bg-white shadow p-4 ">
        @if (count($students) > 0)
            <ul class="divide-y divide-gray-200" id="student-register-list">
                @foreach ($students as $index => $student)
                    <li class="flex items-center justify-between py-3 hover:bg-gray-50 px-2 rounded">
                        <div class="flex gap-7 flex-1">
                            <span class="text-sm text-gray-700">{{ $student['name'] }}</span>
                            <span class="text-sm text-gray-700">{{ $student['email'] }}</span>
                            <span class="text-sm text-gray-700">{{ $student['phone'] }}</span>
                        </div>
                        <x-mary-button icon="o-trash" class="btn btn-sm bg-red-100 rounded-sm text-red-500"
                            wire:click="removeStudent({{ $index }})" spinner />
                    </li>
                @endforeach
            </ul>

            <div class="flex justify-end gap-2 mt-4 pt-4" id="student-input-actions">
                <button type="button"
                id="student-input-cance"
                class="btn btn-sm btn-ghost tracking-wider capitalize"
                    x-on:click="$wire.cancel()">cancelar</button>
                <button
                type="submit"
                id="student-input-register"
                class="btn btn-sm bg-blue-400 capitalize text-gray-50 tracking-wider"
                wire:click.prevent="storeStudent"
                wire:loading.class="opacity-50"
                wire:target="storeStudent">registrar</button>
            </div>

            <div wire:loading wire:target="storeStudent" class="flex justify-center mt-4">
                <div class="flex flex-col items-center gap-2">
                    <x-mary-loading class="loading-spinner loading-xl text-blue-400" />
                    <span class="text-xs font-semibold text-blue-400">cadastrando estudantes</span>
                </div>
            </div>
        @else
            <p class="text-gray-400 text-sm text-center py-8">Nenhum aluno adicionado ainda.</p>
        @endif
    </div>

</form>
</x-mary-modal>
