<?php

use Livewire\Component;
use App\Models\Teacher\{Classe, Student};
use Livewire\Attributes\{On, Validate};
use Illuminate\Support\Facades\Hash;

new class extends Component {
    public bool $open = false;

    #[Validate('required', message: 'Nome é obrigatório!')]
    #[Validate('min:5', message: 'O nome deve ter no minimo 5 caracteres')]
    public string $studentName = '';
    #[Validate('required', message: 'O e-mail é obrigatório!')]
    #[Validate('email', message: 'O e-mail deve ser valido!')]
    public string $studentEmail = '';
    public string $studentPhone = '';
    public array $students = [];

    public Classe $classe;

    #[On('open-form-register-student')]
    public function openForm()
    {
        $this->open = true;
    }

    public function addStudent()
    {
        $validated = $this->validate([
            'studentName' => 'required|string|min:3',
            'studentEmail' => 'required|email',
            'studentPhone' => 'nullable|string'
        ]);
          $students = [
            'name' => $validated['studentName'],
            'email' => $validated['studentEmail'],
            'phone' => $validated['studentPhone'] ?? '',
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
        sleep(1);
    }

    public function storeStudent()
    {
        try {
            DB::transaction(function () {
                foreach ($this->students as $student) {
                    $phone = !empty(trim($student['phone'] ?? '')) ? $student['phone'] : null;
                    Student::create([
                        'full_name' => $student['name'],
                        'email' => $student['email'],
                        'phone' => $phone,
                        'classe_id' => $this->classe->id,
                        'teacher_id' => Auth::guard('teacher')->user()->id,
                        'discipline_id' => $this->classe->discipline_id,
                        'password' => Hash::make('123456'),
                    ]);
                }
            });
            $this->reset('studentName', 'studentEmail', 'studentPhone', 'students');
            $this->open = false;
            $this->dispatch('event-student', type: 'created');
        } catch (\Exception $e) {
            $this->open = false;
            if (str_contains($e->getMessage(), 'students_phone_non_null_unique')) {
                  $this->error(
                      'Telefone já cadastrado',
                      'Este número de telefone já está em uso por outro aluno.',
                      position: 'toast-top toast-end',
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
        $this->reset('studentName', 'studentEmail', 'studentPhone', 'students');
        $this->open = false;
    }
}; ?>

<x-mary-modal wire:model="open" title="Aluno" subtitle="Registrar um novo Aluno" box-class="w-11/12  max-w-6xl">
    <form wire:submit.prevent="storeStudent" wire:loading.class="flex flex-col justify-center items-center">
        <div class="mb-3 grid grid-cols-12 gap-2 items-end" wire:target="storeStudent" wire:loading,remove
            id="student-form">
            <div class="col-span-4" id="student-input-full_name">
                <div class="flex flex-col gap-1">
                  <label class="text-sm font-bold text-gray-600" for="input-student-full_name">Nome do aluno</label>
                  <input
                  class="input !outline-none focus:border-gray-300"
                  placeholder="Nome do aluno"
                  id="input-student-full_name"
                  wire:model="studentName"/>
                </div>
                <div>
                  @error("studentName")
                  <span class="text-xs text-red-500">{{ $message }}</span>
                  @enderror
                </div>
            </div>
            <div class="col-span-4" id="student-input-email">
              <div class="flex flex-col gap-1">
                <label class="text-sm font-bold text-gray-600" for="input-student-input-email">E-mail do aluno</label>
                <input
                class="input !outline-none focus:border-gray-300"
                placeholder="E-mail do aluno"
                id="input-student-input-email"
                wire:model="studentEmail"/>
              </div>
              @error("studentEmail")
              <div id="student-email-error">
                  <span class="text-xs text-red-500">{{ $message }}</span>
              </div>
              @enderror
            </div>
            {{-- <div class="col-span-3" id="student-input-phone">
                <div class="flex flex-col gap-1">
                  <label class="text-sm font-bold text-gray-600" for="input-student-input-phone">Telefone do aluno (*opcional)</label>
                  <input
                  class="input !outline-none focus:border-gray-300"
                  placeholder="(41) 99999-9999"
                  x-mask="(99) 99999-9999"
                  id="input-student-input-phone"
                  wire:model="studentPhone"/>
                </div>

            </div> --}}
            <div class="col-span-1" id="student-input-add">
                <button class="btn  bg-pink-700 text-gray-50" type="button" id="student-input-add-to-list">
                  <x-mary-icon name="o-plus" class="w-5 h-5" wire:click="addStudent"/>
                </button:>
            </div>
        </div>
        @if (count($students) > 0)
            <div id="student-input-list">
                <ul>
                    @foreach ($students as $index => $student)
                        <li class="flex items-center justify-between hover:bg-gray-200">
                            <div class="flex justify-between p-4 w-1/2">
                                <span class="text-sm text-gray-700 tracking-wide">{{ $student['name'] }}</span>
                                <span class="text-sm text-gray-700">{{ $student['email'] }}</span>
                                <span class="text-sm text-gray-700">{{ $student['phone'] }}</span>
                            </div>
                            <x-mary-button icon="o-trash" class="btn btn-sm btn-ghost text-red-400"
                                wire:click="removeStudent({{ $index }})" spinner />
                        </li>
                    @endforeach
                </ul>
            </div>
            <div id="student-input-actions">
                <div class="self-end mt-4" wire:loading.remove>
                    <button type="button" class="btn btn-sm btn-ghost tracking-wider capitalize"
                        x-on:click="$wire.open = false" id="student-input-cancel">cancelar</button>
                    <button type="button" class="btn btn-sm bg-pink-700 capitalize text-gray-50 tracking-wider"
                        wire:click.prevent="storeStudent" id="student-input-register">registrar</button>
                </div>
                <div wire:loading wire:target="storeStudent" class="w-full flex justify-center">
                    <div class="flex flex-col justify-center items-center gap-4">
                        <x-mary-loading class="loading-spinner loading-xl text-pink-700" />
                        <span class="text-xs capitalize font-semibold tracking-wider text-pink-700">cadastrando
                            estudantes</span>
                    </div>
                </div>
            </div>
            </div>
        @endif
    </form>
</x-mary-modal>
