<?php

use Livewire\Component;
use Livewire\Attributes\{On, Validate, Computed, Locked};
use App\Models\Module;
use App\Models\Teacher\{Task, ActivitTask, Discipline};
use App\Support\ActivityValue;
use Illuminate\Support\Facades\{Auth, DB};
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    public bool $open_create_task = false;
    public $search_lesson = '';
    public $noteAcitivity = '';
    public string $questionTypeSelected = 'radio';
    /** Load to atribute activitie*/
    public $lessons;

    public $totalValueExceded = false;
    #[Locked]
    public $disciplineId;
    public bool $sendData = false;
    public bool $isAvaliative = false;
    public $valueOfActivity = null;
    #[Validate('required')]
    public $selectedLessonId = null;
    public string $titleOfQuestion = '';

    //Order list
    private int $nextQuestionOrder = 1;
    public array $questionOrderMap = []; // maping id

    #[Validate('required', message: 'O titulo da opção é obrigatório!')]
    #[Validate('min:5', message: 'O titulo da opção deve ter no minimo 5 caracteres')]
    public string $newOptionText = '';

    public string $teacherAnswer = '';
    public array $options = [];
    public ?int $correctOptionIndex = null;
    public bool $disabledButton = true;
    public array $questions = [];
    public $valueOfQuestion = null;
    public array $questionNoteValues = [];
    public $totalNoteQuestions = 0;

    // dates
    public $startDate = null;
    public $endDate = null;
    // Tipos de questões disponíveis
    public array $questionTypes = [
        [
            'type' => 'radio',
            'title' => 'Multipla escolha',
        ],
        [
            'type' => 'text',
            'title' => 'Dissertativa',
        ],
    ];

    public ?int $editingQuestionIndex = null;
    public ?int $editingQuestionValueIndex = null;
    public ?int $editingOptionIndex = null;
    public string $editingQuestionTitle = '';
    public $editingQuestionValue = null;
    public string $editingOptionText = '';

    public function mount($lessons, $disciplineId)
    {
        $this->lessons = $lessons;
        $this->disciplineId = $disciplineId;
        $this->options = [];
        $this->questions = [];
        $this->questionNoteValues = [];

        // order
        $this->questionOrderMap = [];
    }

    /**
     *  Convert value received to float
     *  **/
    public function convertToFloat($value)
    {
        return ActivityValue::toFloat($value);
    }


    public function startEditingQuestion(int $index)
    {
        $this->editingQuestionIndex = $index;
        $this->editingQuestionValueIndex = $index;
        $this->editingQuestionTitle = $this->questions[$index]['title'];
        $this->editingQuestionValue = $this->questions[$index]['value_of_question'];
    }

    public function saveQuestionEdit()
    {
        $this->validate(rules: [
            'editingQuestionTitle' => 'required|min:8',
            'editingQuestionValue' => $this->validIsAvaliative() ? 'required' : ''
        ]);

        $oldValue = $this->questions[$this->editingQuestionIndex]['value_of_question'];
        $oldNumericValue = $this->convertToFloat($oldValue);

        //update note question
        $this->questions[$this->editingQuestionIndex]['title'] = $this->editingQuestionTitle;
        $this->questions[$this->editingQuestionIndex]['value_of_question'] = $this->editingQuestionValue;

        $newValue = $this->convertToFloat($this->editingQuestionValue);
        // update to new value
        $this->updateTotalValue($newValue, $this->editingQuestionIndex);
        $this->validateTotalValue();
        $this->cancelEditing();
    }

    public function startEditingOption(int $qIndex, int $optIndex)
    {
        $this->editingQuestionIndex = $qIndex;
        $this->editingOptionIndex = $optIndex;
        $this->editingOptionText = $this->questions[$qIndex]['options'][$optIndex]['text'];
    }

    public function saveOptionEdit()
    {
        $this->validate(['editingOptionText' => 'required|min:1|max:255']);
        $this->questions[$this->editingQuestionIndex]['options'][$this->editingOptionIndex]['text'] = $this->editingOptionText;
        $this->cancelEditing();
    }

    public function cancelEditing()
    {
        $this->editingQuestionIndex = null;
        $this->editingOptionIndex = null;
        $this->editingQuestionTitle = '';
        $this->editingOptionText = '';
    }

    #[Computed]
    public function validIsAvaliative()
    {
        return $this->isAvaliative;
    }

    #[Computed]
    public function execedeValue(): bool
    {
        return $this->totalUsedValue() >= $this->activityValue();
    }

    public function selectedQuestionType(string $type)
    {
        if (is_array($type)) {
            $type = $type['type'] ?? 'text'; // Pega a string 'type' do array
        }
        $this->questionTypeSelected = $type;
    }

    public function addQuestion()
    {
        $this->validate(
            [
                'titleOfQuestion' => 'required|min:8',
                'valueOfQuestion' => $this->validIsAvaliative() ? 'required' : '',
            ],
            [
                'titleOfQuestion.required' => 'O questionário é obrigatório!',
                'titleOfQuestion.min' => 'O questionário deve ter no minimo 8 caracteres',
                'valueOfQuestion.required' => 'A nota da questão é obrigatória',
            ],
        );

        $questionExists = false;
        foreach ($this->questions as $question) {
            if ($question['title'] === $this->titleOfQuestion) {
                $questionExists = true;
                break;
            }
        }

        if($questionExists){
            $this->addError('titleOfQuestion', 'Questão ja cadastrada');
            return;
        }
        if ($this->convertToFloat($this->valueOfQuestion) > $this->convertToFloat($this->valueOfActivity)) {
            $this->addError('valueOfQuestion', 'O valor da questão não deve ser maior que o da atividade');
            return;
        }

        // Position of question
        $questionID = $this->nextQuestionOrder++;
        $order = count($this->questions);

        $options = $this->questionTypeSelected === 'radio' ? $this->prepareOptions() : null;

        // Se a questão de múltipla escolha não tem opções suficientes,
        // prepareOptions() já registrou o erro: não adiciona a questão.
        if ($this->questionTypeSelected === 'radio' && $options === null) {
            return;
        }

        //TODO: create column order in table
        $questionData = [
            'order' => count($this->questions) + 1,
            'title' => $this->titleOfQuestion,
            'type' => $this->questionTypeSelected,
            'value_of_question' => $this->valueOfQuestion ?? 0,
            'options' => $options
        ];

        $this->questions[] = $questionData;
        $this->questionOrderMap[$questionID] = $order;

        if ($this->valueOfQuestion) {
          $this->updateTotalValue($this->valueOfQuestion);
          $this->validateTotalValue();
        }
        $this->reset(['titleOfQuestion', 'options', 'newOptionText']);
        $this->valueOfQuestion = null;

    }

    /** Order questions and options with new label order */
    private function prepareOptions()
    {
      if (count($this->options) < 2) {
        $this->addError('options', 'Para questões de múltipla escolha, é necessário pelo menos 2 opções.');
        return;
      }
      $options = [];
      foreach ($this->options as $index => $option) {
        $options[] = [
          'text' => $option['text'],
          'is_correct' => $option['is_correct'] ?? false,
          'order' => $index
        ];
      }
      return $options;
    }

    public function updateTotalValue($value, $index = null)
    {
        $floatValue = $this->convertToFloat($value);
        if($index === null){
            // add the new value to new questions
            $this->questionNoteValues[] = $floatValue;
        }else {
            if(isset($this->questionNoteValues[$index])){
              // update the value of an existing question
              $this->questionNoteValues[$index] = $floatValue;
            }
        }

        $this->questionNoteValues = array_values($this->questionNoteValues);
    }

    public function formatarNota($valor): string
    {
        return ActivityValue::format($valor);
    }

   #[Computed]
  public function totalUsedValue(): float
  {
      $values = array_map(
          fn ($value) => $this->convertToFloat($value),
          $this->questionNoteValues,
      );

      return round(array_sum($values), 2);
  }

  /** Valor total configurado para a atividade. */
  private function activityValue(): float
  {
      return $this->convertToFloat($this->valueOfActivity);
  }

  /**
   * Valida a soma das notas fora do ciclo de renderização.
   * Computed properties não devem mutar estado nem adicionar erros.
   */
  public function validateTotalValue(): void
  {
      $total = $this->totalUsedValue();
      $activity = $this->activityValue();

      $this->totalValueExceded = false;

      if ($activity <= 0) {
          return;
      }

      if ($total > $activity + 0.01) {
          $this->addError('totalUsedValueExedido', 'O valor total das questões não pode ser maior que o da atividade');
          return;
      }

      if (abs($total - $activity) < 0.01) {
          $this->totalValueExceded = true;
          $this->addError('totalUsedValue', 'A soma das notas das questões atingiu o valor máximo da atividade');
      }
  }

  public function updatedValueOfActivity(): void
  {
      $this->validateTotalValue();
  }

    public function calculateTotalQuestionValue()
    {
        //$count = array_sum($this->questionNoteValues);
        return array_sum(array_column($this->questions, 'value_of_question'));
    }

    public function removeQuestion(int $index)
    {

       if (isset($this->questions[$index])) {
            $value = $this->questions[$index]['value_of_question'] ?? 0;
            //$numericValue = $this->convertToFloat($value);

            unset($this->questions[$index]);

            $this->questions = array_values($this->questions);

            foreach ($this->questions as $newIndex => &$question) {
                $question['order'] = $newIndex + 1; // 1, 2, 3...
            }

            if (isset($this->questionNoteValues[$index])) {
                unset($this->questionNoteValues[$index]);
                $this->questionNoteValues = array_values($this->questionNoteValues);
            }

            if ($this->editingQuestionIndex === $index) {
                $this->cancelEditing();
            }

            $this->validateTotalValue();

            $this->dispatch(
              'remove-question',
               message: 'Questão removida com sucesso!');
        }
    }

    public function editQuestion($qIndex)
    {
        $this->questions[$qIndex]['editing'] = true;
    }
    public function addOptionRadio()
    {
        $this->validateOnly('newOptionText');
        $optionText = trim($this->newOptionText);
        if(empty($optionText)){
            $this->addError('newOptionText', 'Opção vazia');
            return;
        }

        $optionExists = false;
        foreach ($this->options as $option) {
            if (trim($option['text']) === $optionText) {
                $optionExists = true;
                break;
            }
        }

        if($optionExists){
            $this->addError('newOptionText', 'Opção ja cadastrada');
            return;
        }

        $this->options[] = [
          'text' => $this->newOptionText,
          'is_correct' => $this->correctOptionIndex,
        ];
        $this->newOptionText = '';
        $this->resetErrorBag('newOptionText');
    }

    /** Remove option in this list the options
     * @param int $index is position of array
     * **/
    public function removeOption(int $index)
    {
      if(isset($this->options[$index])){
        // create copy
        $newOptions = $this->options;
        unset($newOptions[$index]);

        // re indexed in array
        $this->options = array_values($this->options);

        if ($this->correctOptionIndex === $index) {
            $this->correctOptionIndex = null;
        }elseif($this->correctOptionIndex > $index){
            $this->correctOptionIndex--;
        }
      }
    }

    public function setCorrectOption(int $index)
    {
        foreach ($this->options as $i => &$option) {
            $option['is_correct'] = $i === $index;
        }
        $this->correctOptionIndex = $index;
    }

    public function updateCorrectOption($questionIndex, int $optionIndex)
    {
        // Atualiza todas as opções da questão específica
        foreach ($this->questions[$questionIndex]['options'] as $i => &$option) {
            $option['is_correct'] = $i === $optionIndex;
        }

        // Dispara um evento para atualizar a UI
        $this->dispatch('answer-updated');
    }

    #[On('open-create-task')]
    public function openTask(): void
    {
        $this->open_create_task = true;
    }

    public function close()
    {
      $this->open_create_task = false;
      $this->dispatch('close-create-task');
    }

    public function closeFancybox()
    {
        $this->open_create_task = false;
    }

    protected function resetFormCreation()
    {
        $this->titleOfQuestion = '';
        $this->options = [];
        $this->questionTypeSelected = 'radio';
        $this->selectedLessonId = null;
    }

    public function save()
    {
        $this->validate([
            'selectedLessonId' => 'required|exists:modules,id',
            'disciplineId' => 'required|exists:disciplines,id',
            'questions' => 'required|array|min:1',
        ]);

        // O módulo (aula) precisa pertencer à disciplina informada e a
        // disciplina precisa pertencer ao professor autenticado. Evita que um
        // payload forjado crie atividades em recursos de outro professor.
        $teacher = Auth::guard('teacher')->user();

        $module = Module::query()
            ->whereKey($this->selectedLessonId)
            ->where('discipline_id', $this->disciplineId)
            ->whereHas('discipline', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher?->id);
            })
            ->first();

        if (! $module) {
            abort(404);
        }

        $questionCorrectError = [];

        foreach($this->questions as $index => $question){
          if($question['type'] === 'radio'){
            $isCorrect = false;
            foreach($question['options'] as $option) {
              if($option['is_correct'] ?? false) {
                $isCorrect = true;
                break;
              }
            }
            if(!$isCorrect){
              $questionCorrectError[] = $index + 1;
            }
          }
        }
            // Se encontrou questões sem opção correta
        if (!empty($questionCorrectError)) {
              $question = implode(', ', $questionCorrectError);
              $this->addError(
                  'is_correct_alternative',
                  "As seguintes questões de múltipla escolha precisam ter uma alternativa correta selecionada: {$question}"
              );
              return;
        }

        if ($this->isAvaliative && $this->totalUsedValue() > $this->activityValue() + 0.01) {
            $this->addError('totalUsedValueExedido', 'O valor total das questões não pode ser maior que o da atividade');
            return;
        }

          try {
           // dd($this->questions, $this->valueOfActivity, $this->isAvaliative);
            $this->sendData = true;
            DB::transaction(function () {
              $activit = ActivitTask::create([
                  'module_id' => $this->selectedLessonId,
                  'discipline_id' => $this->disciplineId,
                  'teacher_id' => Auth::guard('teacher')->user()->id,
                  'start' => $this->startDate,
                  'end' => $this->endDate,
                  'is_avaliative' => $this->isAvaliative ? true : false,
                  'total_value' => $this->isAvaliative ? ActivityValue::toFloat($this->valueOfActivity) : null
              ]);
                foreach ($this->questions as $question) {
                    $task = $activit->tasks()->create([
                        'question' => $question['title'],
                        'type' =>  $question['type'] == 'radio' ? 'multiple_choice' : 'dissertative',
                        'order' => $question['order'],
                        'value' => $this->isAvaliative ? ActivityValue::toFloat($question['value_of_question']) : null,
                    ]);
                    // Quando criar uma nova opção
                    if ($question['type'] === 'radio') {
                        $lastOrder = (int) ($task->options()->max('order') ?? 0);
                        foreach ($question['options'] as $option) {
                            $task->options()->create([
                                'text' => $option['text'],
                                'correct' => $option['is_correct'],
                                'order' => ++$lastOrder,
                            ]);
                        }
                    }
                }
            });
            $this->dispatch('task-created-success', message: 'Atividade criada com sucesso!');
            $this->resetFormCreation();
            $this->close();
            $this->questions = [];
        } catch (\Exception $e) {
            report($e);
            $this->addError('save', 'Não foi possível salvar a atividade. Tente novamente.');
        }
    }


    public function cancelCreation()
    {
        $this->resetFormCreation();
        $this->close();
    }
}; ?>
<div
    x-data
    wire:show="open_create_task"
    @keydown.escape="open_create_task = false"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50  bg-white"
    x-trao.noscroll="$wire.open_create_task"
    x-cloak>
    <div class="sticky px-5 top-0 flex items-center justify-between w-full  border-gray-300 border-b-[1px] h-16 "
        id="create-task-header-{{ $disciplineId }}">
        <h2 class="text-[15px] font-semibold text-gray-700">Criar atividade / avaliação</h2>
        <x-mary-button icon="o-x-mark" class="btn btn-sm btn-ghost" wire:click="close" />
    </div>
    <!-- create questions to actitivity -->
     <div class="flex w-full"
        id="create-activity-task"
        data-tour="{{ $disciplineId }}">
        <div
            class="sticky left-0 top-[40px] flex flex-col p-5 transition-all duration-300 ease-in-out border-gray-300 border-r-[1px]  max-h-[calc(100dvh-(var(--spacing)*14.25))] scrollbar  scrollbar-thin scrollbar-thumb-gray-100 scrollbar-thumb-rounded-full scrollbar-track-gray-50 h-screen w-1/3 overflow-y-scroll">

            <!-- endscrooll -->
            <div class="mb-6">
                <!-- Com ícone interno -->
                <label class="flex items-center gap-2 cursor-pointer"
                    id="create-task-avaliative-{{ $disciplineId }}">
                    <input
                        type="checkbox"
                        class="hidden peer"
                        wire:model.live.debounce.0ms="isAvaliative">
                    <div
                        class="w-6 h-6 border-2 border-gray-300 rounded
                               peer-checked:bg-pink-700 peer-checked:border-pink-700
                                peer-checked:flex peer-checked:items-center peer-checked:justify-center
                                transition-all duration-200">
                        <x-mary-icon
                            name="o-check"
                            class="w-4 h-4 text-white hidden peer-checked:block" />
                    </div>
                    <div class="flex flex-col ml-2">
                        <span class="text-gray-800 text-[14px] font-bold">Atividade avaliativa</span>
                    </div>
                </label>
            </div>

            @if($this->validIsAvaliative)
            <div class="flex gap-2 mb-6 transition ease-in-out duration-700"
                id="create-task-value-activity-{{ $disciplineId }}"
                      x-data="{
                        note: $wire.entangle('valueOfActivity').live,
                        mensagem: '',
                        showMessage: false,
                        mask(event){
                             let value = event.target.value.replace(/\D/g,''); // apenas números
                            // se vazio → volta para 0,00
                            if (!value) {
                                this.note = '0,00';
                                this.mensagem = '';
                                return;
                            }
                            // garante no mínimo 3 dígitos para ter casas decimais
                            while(value.length < 3){
                                value = '0' + value;
                            }
                            // pega inteiro e decimal
                            let inteiro = value.slice(0, -2);
                            let decimal = value.slice(-2);
                            // converte inteiro pra número
                            let numeroInteiro = parseInt(inteiro);

                            // regra 1: limite máximo
                            if (numeroInteiro > 10) {
                                // dividide de novo esse cara
                                let redivide = inteiro.slice(0,1);
                                this.note = `${redivide},${decimal[0]}`;
                                this.showWarningMessage('Valor maximo permitido: 10,0');
                                return;
                            }

                            // regra 2: quando for 10, só pode ser 10,0
                            if (numeroInteiro === 10) {
                                this.note = '10,0';
                                if(decimal !== '00'){
                                    //this.mensagem = 'Valor maximo permitido: 10,0';
                                    this.showWarningMessage('Valor maximo permitido: 10,0');
                                }else {
                                    this.mensagem = '';
                                }
                                return;
                            }

                            // rule 3 #
                            this.note = `${numeroInteiro},${decimal}`;
                            // monta valor final normal
                            this.mensagem = '';
                        },

                        showWarningMessage(message){
                          this.mensagem = message;
                          this.showMessage = true;
                          setTimeout(() => {
                            this.showMessage = false;
                            this.mensagem = '';
                          },4000)
                        },
                        handleBackspaceInput(){
                          let value = this.note.replace(/\D/g,''); // pega só números

                          // se já estiver vazio → volta para 0,00
                          if (!value || value === '000') {
                              this.note = '0,00';
                              return;
                          }

                          // remove último dígito (como backspace)
                          value = value.slice(0, -1);

                          // garante pelo menos 3 dígitos
                          while(value.length < 3){
                              value = '0' + value;
                          }

                          // reaplica a máscara normal
                          let inteiro = value.slice(0, -2);
                          let decimal = value.slice(-2);

                          let numeroInteiro = parseInt(inteiro);

                          if (numeroInteiro > 10) {
                              let redivide = inteiro.slice(0,1);
                              this.note = `${redivide},${decimal[0]}`;
                              return;
                          }

                          if (numeroInteiro === 10) {
                              this.note = '10,0';
                              return;
                          }

                          this.note = `${numeroInteiro},${decimal}`;
                      }
                      }">
                  <div class="flex flex-col gap-1 w-full" >
                    <label
                        for="note_question_input"
                        class="text-gray-800 font-bold text-[12px]">
                        Nota
                    </label>
                    <div class="flex gap-1 items-center">
                         <input
                          x-model="note"
                          x-on:input="mask($event)"
                          placeholder="0,00"
                          class="input input-md !outline-none text-right font-mono focus:border-gray-300"
                        />
                        <button
                            type="button"
                            class="btn btn-md bg-gray-100 text-gray-500"
                            @click.prevent="handleBackspaceInput">
                            <x-mary-icon
                                name="o-backspace"
                                class="w-5 h-5 text-gray-600" />
                        </button>
                    </div>
                    <p x-show="showMessage" class="text-red-500 text-sm mt-1 transition-opacity duration-300" x-text="mensagem"></p>

                    @error('valueOfActivity')
                        <span class="text-xs tracking-wider text-pink-500 error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>
            </div>
            @endif

            <div class="flex flex-col gap-2">
                <div class="flex flex-col gap-1" id="create-title-question-{{ $disciplineId }}">
                    <label for="id_titleofquestion" class="text-gray-800 text-[12px] font-bold">
                        Enunciado</label>
                    <textarea
                        wire:model.live.debounce.0ms="titleOfQuestion"
                        class="w-full min-h-[100px] font-normal text-sm  textarea !outline-none focus:border-gray-400"
                        placeholder="Digite o enunciado (Enter para quebras de linha)"
                        id="id_titleofquestion">
                    </textarea>
                    <div class="w-full py-2">
                        @error('titleOfQuestion')
                            <span class="text-sm tracking-wider text-red-500 error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="mb-4">
                    <h3 class="text-[12px] text-gray-700 text-sm font-medium">
                     Selecione o tipo de questão
                    </h3>
                </div>
                <div class="flex py-4"
                  id="question-select-types-{{ $disciplineId }}">
                    @foreach ($questionTypes as $type)
                        <button
                            type="button"
                            wire:click="selectedQuestionType('{{ $type['type'] }}')"
                            @class([
                                'cursor-pointer flex items-center gap-2 px-4 py-2 transition-all duration-300 ease-in-out border-b-[1px] border-gray-300 ',
                                'text-gray-800 border-gray-700 shadow-md' =>
                                    $questionTypeSelected === $type['type'],
                                'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' =>
                                    $questionTypeSelected !== $type['type'],
                            ]) id="question-select-{{ $type['type'] }}-{{ $disciplineId }}">
                            <span class="text-[13px] font-semibold">{{ $type['title'] }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="mt-2">
                    @if ($questionTypeSelected === 'radio')
                        <div class="flex flex-col gap-3">
                            <div class="flex justify-between items-center">
                                <span class="text-[12px] text-gray-800 font-bold">Digite uma opção</span>
                                <span class="text-xs text-gray-600 tracking-wider font-normal  uppercase">minimo 3
                                    alternativas</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <div class="flex gap-1">
                                    <input
                                        wire:model.live.debounce.0ms="newOptionText"
                                        id="create-question-option-{{ $disciplineId }}"
                                        placeholder="Texto da opção"
                                        wire:keydown.enter="addOptionRadio"
                                        class="input input-md text-sm w-full !outline-none focus:border-gray-400" />
                                    <button
                                        type="button"
                                        wire:click="addOptionRadio"
                                        id="create-question-option-add-{{ $disciplineId }}"
                                        class="btn btn-md bg-blue-400 hover:bg-blue-500 text-gray-50">
                                        <x-mary-icon name="o-plus" />
                                    </button>
                                </div>
                                <div class="w-full py-2">
                                    @error('newOptionText')
                                        <!--TODO: Componentizar estilos de erros e tipos -->
                                        <span
                                            class="text-pink-500 error text-xs font-semibold tracking-wider">
                                            {{ $errors->first('newOptionText') }}
                                        </span>
                                    @enderror
                                    @error('options')
                                        <span
                                            class="text-pink-500 error text-xs font-semibold tracking-wider">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-between gap-2">
                            <div class="flex flex-col gap-2">
                                <div class="flex flex-col gap-2">
                                    <div class="flex flex-col gap-4">
                                        <!-- Lista de Opções -->
                                        <div class="mt-2 space-y-2"
                                            id="question-options-list-{{ $disciplineId }}">
                                            @forelse ( $options as $index => $option)
                                                <div class="flex items-center gap-1 p-3">
                                                    <input
                                                        wire:model.defer="options.{{ $index }}.text"
                                                        class="input input-sm flex-1 !outline-none focus:border-gray-300" />

                                                    <!-- Botão Remover -->
                                                    <button
                                                        type="button"
                                                        wire:click.prevent="removeOption({{ $index }})"
                                                        class="btn btn-sm btn-ghost text-pink-500 hover:text-pink-700">
                                                        <x-mary-icon name="o-trash" class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            @empty
                                            <div
                                                class="w-full text-center p-3">
                                                <!-- TODO: Componentizar estilos de aviso -->
                                                <p class="text-sm text-gray-600 font-semibold text-center">
                                                    Nenhuma opção criada
                                                </p>
                                            </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- TODO: criar componente para mensagens assim -->
                        <div class="text-[12px] font-medium tracking-wide text-gray-700">
                            {{-- O aluno verá uma área de texto para resposta. --}}
                        </div>
                    @endif
                    @if ($this->validIsAvaliative)
                    <div class="mt-4"
                      x-data="{
                        note: $wire.entangle('valueOfQuestion').live,
                        mensagem: '',
                        showMessage: false,
                        mask(event){
                             let value = event.target.value.replace(/\D/g,''); // apenas números
                            // se vazio → volta para 0,00
                            if (!value) {
                                this.note = '0,00';
                                this.mensagem = '';
                                return;
                            }
                            // garante no mínimo 3 dígitos para ter casas decimais
                            while(value.length < 3){
                                value = '0' + value;
                            }

                            // pega inteiro e decimal
                            let inteiro = value.slice(0, -2);
                            let decimal = value.slice(-2);
                            // converte inteiro pra número
                            let numeroInteiro = parseInt(inteiro);

                            // regra 1: limite máximo
                            if (numeroInteiro > 10) {
                                // dividide de novo esse cara
                                let redivide = inteiro.slice(0,1);
                                this.note = `${redivide},${decimal[0]}`;
                                this.showWarningMessage('Valor maximo permitido: 10,0');
                                return;
                            }

                            // regra 2: quando for 10, só pode ser 10,0
                            if (numeroInteiro === 10) {
                                this.note = '10,0';
                                if(decimal !== '00'){
                                    //this.mensagem = 'Valor maximo permitido: 10,0';
                                    this.showWarningMessage('Valor maximo permitido: 10,0');
                                }else {
                                    this.mensagem = '';
                                }
                                return;
                            }

                            // rule 3 #
                            this.note = `${numeroInteiro},${decimal}`;
                            // monta valor final normal
                            this.mensagem = '';

                        },

                        showWarningMessage(message){
                          this.mensagem = message;
                          this.showMessage = true;
                          setTimeout(() => {
                            this.showMessage = false;
                            this.mensagem = '';
                          },4000)
                        },
                        handleBackspaceInput(){
                          let value = this.note.replace(/\D/g,''); // pega só números

                          // se já estiver vazio → volta para 0,00
                          if (!value || value === '000') {
                              this.note = '0,00';
                              return;
                          }

                          // remove último dígito (como backspace)
                          value = value.slice(0, -1);

                          // garante pelo menos 3 dígitos
                          while(value.length < 3){
                              value = '0' + value;
                          }

                          // reaplica a máscara normal
                          let inteiro = value.slice(0, -2);
                          let decimal = value.slice(-2);

                          let numeroInteiro = parseInt(inteiro);

                          if (numeroInteiro > 10) {
                              let redivide = inteiro.slice(0,1);
                              this.note = `${redivide},${decimal[0]}`;
                              return;
                          }

                          if (numeroInteiro === 10) {
                              this.note = '10,0';
                              return;
                          }

                          this.note = `${numeroInteiro},${decimal}`;
                      }

                    }" id="create-task-question-value-{{ $disciplineId }}">
                        <div class="flex flex-col gap-1">
                            <label
                                for="note_to_question_input"
                                class="text-[12px] text-gray-800 font-bold">
                                Nota da questão
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="text"
                                 x-model="note"
                                 x-on:input="mask($event)"
                                 placeholder="0,00"
                                 class="input input-md !outline-none text-right font-mono focus:border-gray-300"/>

                                <button
                                    class="btn btn-md bg-gray-100 text-gray-500"
                                    @click.prevent="handleBackspaceInput">
                                    <x-mary-icon
                                        name="o-backspace"
                                        class="w-5 h-5 text-gray-600" />
                                </button>
                            </div>
                            <p x-show="showMessage" class="text-red-500 text-sm mt-1" x-text="mensagem"></p>

                            @error('valueOfQuestion')
                            <span class="text-red-500 error text-xs font-semibold tracking-wider">
                                {{ $errors->first('valueOfQuestion') }}
                            </span>
                            @enderror
                        </div>
                    </div>
                    @endif
                </div>
                <div class="mt-4 self-end">
                    <button
                        class="btn btn-md btn-ghost text-gray-600"
                        id="create-task-cancel-question-{{ $disciplineId }}">
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        x-on:click.prevent="$wire.addQuestion"
                        id="create-question-button-radio-{{ $disciplineId }}"
                                                        class="btn btn-md bg-blue-400 hover:bg-blue-500 text-gray-50">
                                                        Criar questão
                    </button>
                </div>
            </div>
        </div>

        <!-- Preview das Questões -->
        <div class="bg-white w-2/3" id="create-task-preview-panel-{{ $disciplineId }}">
            <!-- and input note of activity -->
            <div class="relative h-full">
                    @if ($valueOfActivity > 0)
                    <div class="flex items-center justify-center flex-col gap-2 p-3">
                        <div
                            class="w-[100px] h-[50px] flex justify-center p-5
                            {{ $this->execedeValue ? 'border-[1px] border-green-500 rounded-sm text-green-800' : 'text-gray-800' }}">
                            <span class="font-medium text-lg">{{ $this->formatarNota($this->totalUsedValue) }}</span>
                            <span class="mx-2 font-medium text-lg">/</span>
                            <span class="font-medium text-lg">{{ $this->formatarNota($valueOfActivity) ?? 0 }}</span>
                        </div>
                        {{-- totalUsedValueExedido --}}
                         @error('totalUsedValueExedido')
                        <div class="p-3">
                             <span class="text-sm tracking-wide text-red-600 font-medium">
                                {{ $message }}
                            </span>
                        </div>
                        @enderror

                        @error('totalUsedValue')
                        <div class="p-3">
                             <span class="text-sm tracking-wide text-green-600 font-medium">
                                {{ $message }}
                            </span>
                        </div>
                        @enderror
                        @if(!$this->execedeValue)
                            <span class="text-center font-medium text-gray-600 text-sm">
                                Nota da atividade
                            </span>
                        @endif
                    </div>
                    @endif
                    <div class="flex items-center justify-between p-5">
                        <div class="flex items-center gap-2">
                        @if(count($questions) > 0)
                            <div id="create-task-question-count-{{ $disciplineId }}">
                                <span class="text-sm capitalize font-medium tracking-wider">questões</span>
                                <div class="badge badge-md badge-ghost">
                                    {{ count($questions) }}
                                </div>
                            </div>
                        @endif
                    </div>
                    </div>
                    @if (count($questions) > 0)
                    <div class="p-5 overflow-y-scroll  max-h-[calc(80vh-150px)] scrollbar  scrollbar-thin scrollbar-thumb-gray-100 scrollbar-thumb-rounded-full scrollbar-track-gray-50">
                            @foreach ($questions as $qIndex => $question)
                                <div wire:key="question-{{ $qIndex }}" class="p-4 bg-white" x-data="{
                                                  note: $wire.entangle('editingQuestionValue'),
                                                  mensagem: '',
                                                  showMessage: false,
                                                  mask(event){
                                                      let value = event.target.value.replace(/\D/g,''); // apenas números
                                                      // se vazio → volta para 0,00
                                                      if (!value) {
                                                          this.note = '0,00';
                                                          this.mensagem = '';
                                                          return;
                                                      }
                                                      // garante no mínimo 3 dígitos para ter casas decimais
                                                      while(value.length < 3){
                                                          value = '0' + value;
                                                      }

                                                      // pega inteiro e decimal
                                                      let inteiro = value.slice(0, -2);
                                                      let decimal = value.slice(-2);
                                                      // converte inteiro pra número
                                                      let numeroInteiro = parseInt(inteiro);

                                                      // regra 1: limite máximo
                                                      if (numeroInteiro > 10) {
                                                          // dividide de novo esse cara
                                                          let redivide = inteiro.slice(0,1);
                                                          this.note = `${redivide},${decimal[0]}`;
                                                          this.showWarningMessage('Valor maximo permitido: 10,0');
                                                          return;
                                                      }

                                                      // regra 2: quando for 10, só pode ser 10,0
                                                      if (numeroInteiro === 10) {
                                                          this.note = '10,0';
                                                          if(decimal !== '00'){
                                                              //this.mensagem = 'Valor maximo permitido: 10,0';
                                                              this.showWarningMessage('Valor maximo permitido: 10,0');
                                                          }else {
                                                              this.mensagem = '';
                                                          }
                                                          return;
                                                      }

                                                      // rule 3 #
                                                      this.note = `${numeroInteiro},${decimal}`;
                                                      // monta valor final normal
                                                      this.mensagem = '';

                                                  },

                                                  showWarningMessage(message){
                                                    this.mensagem = message;
                                                    this.showMessage = true;
                                                    setTimeout(() => {
                                                      this.showMessage = false;
                                                      this.mensagem = '';
                                                    },4000)
                                                  },
                                                  handleBackspaceInput(){
                                                    let value = this.note.replace(/\D/g,''); // pega só números

                                                    // se já estiver vazio → volta para 0,00
                                                    if (!value || value === '000') {
                                                        this.note = '0,00';
                                                        return;
                                                    }

                                                    // remove último dígito (como backspace)
                                                    value = value.slice(0, -1);

                                                    // garante pelo menos 3 dígitos
                                                    while(value.length < 3){
                                                        value = '0' + value;
                                                    }

                                                    // reaplica a máscara normal
                                                    let inteiro = value.slice(0, -2);
                                                    let decimal = value.slice(-2);

                                                    let numeroInteiro = parseInt(inteiro);

                                                    if (numeroInteiro > 10) {
                                                        let redivide = inteiro.slice(0,1);
                                                        this.note = `${redivide},${decimal[0]}`;
                                                        return;
                                                    }

                                                    if (numeroInteiro === 10) {
                                                        this.note = '10,0';
                                                        return;
                                                    }

                                                    this.note = `${numeroInteiro},${decimal}`;
                                                }

                                              }">
                                    <!-- Edição do Título -->
                                    @if ($editingQuestionIndex === $qIndex  && $editingQuestionValueIndex === $qIndex && $editingOptionIndex === null)
                                        <div class="flex items-center gap-2 mb-3" >
                                            <span class="font-bold text-gray-700">{{ $question['order']}}.</span>
                                            <textarea
                                                wire:model="editingQuestionTitle"
                                                class="w-full min-h-[100px] font-normal text-sm  textarea !outline-none focus:border-gray-300"
                                                wire:keydown.enter="saveQuestionEdit"
                                                autofocus></textarea>
                                            @if($this->validIsAvaliative)
                                            <input
                                              x-model="note"
                                              x-on:input="mask($event)"
                                              class="input !outline-none w-20 text-sm text-right"
                                            />
                                            @endif
                                            <div class="flex gap-1">
                                                <button wire:click="saveQuestionEdit"
                                                    class="btn btn-sm bg-gray-100 text-gray-600">
                                                    <x-mary-icon name="o-pencil-square" class="w-5 h-5" />
                                                </button>
                                                <button wire:click="cancelEditing"
                                                    class="btn btn-sm bg-gray-100 text-gray-600">
                                                    <x-mary-icon name="o-x-mark" class="w-5 h-5" />
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex items-end justify-between"
                                            id="question-created-{{ $disciplineId }}">
                                            <div class="flex items-center">
                                                <div class="text-gray-800 whitespace-pre-line">
                                                    <span>{{ $qIndex + 1 }}</span> .{{ $question['title'] }}
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                @if($this->validIsAvaliative)
                                                <div class="text-sm text-gray-800 font-semibold">
                                                    <span class="capitalize ml-1">Nota:</span>{{ $question['value_of_question'] }}
                                                </div>
                                                @endif
                                                <button wire:click="startEditingQuestion({{ $qIndex }})"
                                                    type="button" class="btn btn-sm  bg-gray-100"
                                                    data-tour="edit-question">
                                                    <x-mary-icon name="o-pencil-square" class="w-4 h-4 text-gray-600" />
                                                    <span class="capitalize text-gray-600">editar</span>
                                                </button>
                                                <button wire:click="removeQuestion({{ $qIndex }})"
                                                    type="button" class="btn btn-sm  bg-gray-100"
                                                    data-tour="remove-question">
                                                    <x-mary-icon name="o-trash" class="w-4 h-4 text-gray-600" />
                                                    <span class="capitalize text-gray-600">remover</span>
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Conteúdo baseado no tipo -->
                                    @if ($question['type'] === 'radio')
                                        <!-- Edição de Opções -->
                                        <div class="mt-3 ml-6 space-y-2">
                                            @foreach ($question['options'] as $optIndex => $option)
                                                @if ($editingQuestionIndex === $qIndex && $editingOptionIndex === $optIndex)
                                                    <div class="items-center gap-2 fslex">
                                                        <span
                                                            class="w-5 text-gray-500 uppercase">{{ chr(65 + $optIndex) }}.</span>
                                                        <input wire:model="editingOptionText" class="input input-sm !outline-none flex-1"
                                                            wire:keydown.enter="saveOptionEdit" autofocus />
                                                        <div class="flex gap-1">
                                                            <button wire:click="saveOptionEdit"
                                                                class="btn btn-sm bg-gray-200 text-gray-800">
                                                                <x-mary-icon name="o-pencil-square" class="w-4 h-4" />
                                                            </button>
                                                            <button wire:click="cancelEditing"
                                                                class="btn btn-sm bg-gray-200 text-red-600 hover:text-red-800">
                                                                <x-mary-icon name="o-trash" class="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="flex items-center gap-2">
                                                        <span
                                                            class="w-5 text-gray-500 uppercase">{{ chr(65 + $optIndex) }}.</span>
                                                        <input type="radio"
                                                            name="correct_option_{{ $qIndex }}"
                                                            wire:change="updateCorrectOption({{ $qIndex }}, {{ $optIndex }})"
                                                            @checked($option['is_correct'])
                                                            class="text-indigo-600 border-gray-300">
                                                        <span @class([
                                                            'flex-1 cursor-pointer',
                                                            'font-semibold text-indigo-700' => $option['is_correct'],
                                                        ])
                                                            wire:click="startEditingOption({{ $qIndex }}, {{ $optIndex }})">
                                                            {{ $option['text'] }}
                                                        </span>
                                                        @if ($option['is_correct'])
                                                            <x-mary-icon name="o-check-circle"
                                                                class="w-4 h-4 text-green-500" />
                                                        @endif
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                            <!-- Mostrar erro de opções corretas -->
                            @error('is_correct_alternative')
                                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                                    <div class="flex items-center">
                                        <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5 mr-2" />
                                        <span class="font-medium">Atenção</span>
                                    </div>
                                    <p class="mt-2 text-sm">{{ $message }}</p>
                                    <p class="text-sm mt-1">Por favor, selecione a alternativa correta para cada questão de múltipla escolha antes de salvar.</p>
                                </div>
                            @enderror
                             <!-- rest infos -->
                            <div class="flex flex-col gap-2 mt-6 p-5 bg-gray-100 rounded-sm">
                                <div class="mb-2" id="create-question-associate-module-{{ $disciplineId }}">
                                    <label class="block text-[12px] font-bold text-gray-700">Vincular à aula:</label>
                                    <select class="select !outline-none w-full focus:border-gray-400" wire:model="selectedLessonId">
                                       <option value="">Selecione à aula</option>
                                        @foreach ($lessons as $lesson)
                                            <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
                                        @endforeach
                                    </select>
                                    @error('selectedLessonId')
                                      <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="mb-4 flex gap-4" id="create-question-dates-{{ $disciplineId }}">
                                      <div>
                                           <label for="data_start_input" class="text-[12px] font-bold text-gray-800">Data de inicio</label>
                                           <input type="date" id="data_start_input" wire:model="startDate" class="input !outline-none focus:border-gray-400">
                                         </div>
                                         <div>
                                           <label for="data_and_input" class="text-[12px] font-bold text-gray-800 tracking-wider">Data final</label>
                                           <input type="date" id="data_and_input" wire:model="endDate" class="input !outline-none focus:border-gray-400">
                                         </div>
                                   </div>

                                <div class="self-start p-5">
                                    <button
                                        type="button"
                                        wire:click="cancelCreation"
                                        id="create-task-cancel-btn-{{ $disciplineId }}"
                                        class="btn btn-md bg-ghost text-gray-600">
                                        Cancelar
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="save"
                                        wire:loading.attr="disabled"
                                        id="create-task-save-btn-{{ $disciplineId }}"
                                        class="btn btn-md bg-blue-400 hover:bg-blue-500 capitalize text-gray-50">
                                        criar questionário
                                    </button>
                                    <div
                                        wire:loading wire:target="save"
                                        class="flex items-center gap-2">
                                        <span class="text-sm text-pink-7">
                                            Salvando o questionário
                                        </span>
                                        <x-mary-loading class=" text-pink-700 loading-dots" />
                                    </div>
                                    @error('save')
                                        <span class="text-sm tracking-wider text-red-500 error mt-2 block">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        <!-- end rest infos -->
                        </div>
                </div>
                @else
                    <div class="flex justify-center items-center h-full">
                        <p class="text-sm font-medium tracking-wider text-gray-600">Nenhuma questão foi criada!</p>
                    </div>
                @endif
        </div>
    </div>
</div>
