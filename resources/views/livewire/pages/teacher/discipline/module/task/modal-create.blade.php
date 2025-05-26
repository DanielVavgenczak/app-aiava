<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{On, Validate, Computed};
use App\Models\Teacher\{Task, ActivitTask};
use Illuminate\Support\Facades\{DB};

new class extends Component {
    public bool $open = false;
    public string $questionTypeSelected = 'radio';
    public $lessons;
    public $disciplineId;
    public bool $sendData = false;

    #[Validate('required')]
    public $selectedLessonId = null;
    #[Validate('required', message: 'O titulo do enunciado é obrigatório!')]
    #[Validate('min:8', message: 'O enunciado deve ter no minimo 10 caracteres')]
    public string $titleOfQuestion = '';

    #[Validate('required', message: 'O titulo da opção é obrigatório!')]
    #[Validate('min:5', message: 'O titulo da opção deve ter no minimo 5 caracteres')]
    public string $newOptionText = '';

    public string $teacherAnswer = '';
    public array $options = [];
    public ?int $correctOptionIndex = null;
    public bool $disabledButton = true;
    public array $questions = [];

    // Tipos de questões disponíveis
    public array $questionTypes = [
        [
            'type' => 'radio',
            'title' => 'Multipla escolha',
        ],
        [
            'type' => 'text',
            'title' => 'Resposta Dissertativa',
        ],
    ];

    public ?int $editingQuestionIndex = null;
    public ?int $editingOptionIndex = null;
    public string $editingQuestionTitle = '';
    public string $editingOptionText = '';

    public function mount($lessons, $disciplineId)
    {
        $this->lessons = $lessons;
        $this->disciplineId = $disciplineId;
    }

    public function startEditingQuestion(int $index)
    {
        $this->editingQuestionIndex = $index;
        $this->editingQuestionTitle = $this->questions[$index]['title'];
    }

    public function saveQuestionEdit()
    {
        $this->validate(['editingQuestionTitle' => 'required|min:8']);

        $this->questions[$this->editingQuestionIndex]['title'] = $this->editingQuestionTitle;
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
    public function validButton()
    {
        $validTitle = !empty($this->titleOfQuestion) && strlen($this->titleOfQuestion) >= 8;

        if ($this->questionTypeSelected === 'radio') {
            return $validTitle && count($this->options) >= 2;
        }

        return $validTitle; // Para questões dissertativas, só precisa do título
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
        $this->validateOnly('titleOfQuestion');
        $questionData = [
            'title' => $this->titleOfQuestion,
            'type' => $this->questionTypeSelected,
        ];
        if ($this->questionTypeSelected === 'radio') {
            $questionData['options'] = [...$this->options];
        } else {
            $questionData['options'] = [];
        }
        $this->questions[] = $questionData;
        $this->reset(['titleOfQuestion', 'options']);
    }

    public function removeQuestion(int $index)
    {
        if (isset($this->questions[$index])) {
            unset($this->questions[$index]);
            $this->questions = array_values($this->questions);
            $this->dispatch('remove-question', message: 'Questão removida com sucesso!');
        }
    }

    public function editQuestion($qIndex)
    {
        $this->questions[$qIndex]['editing'] = true;
    }
    public function addOptionRadio()
    {
        $this->validateOnly('newOptionText');
        if (!empty($this->newOptionText)) {
            $this->options[] = [
                'text' => $this->newOptionText,
                'is_correct' => $this->correctOptionIndex,
            ];
            $this->newOptionText = '';
        }
    }
    public function removeOption(int $index)
    {
        unset($this->options[$index]);
        $this->options = array_values($this->options);
        if ($this->correctOptionIndex === $index) {
            $this->correctOptionIndex = null;
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
        $this->open = true;
    }

    public function close()
    {
        $this->open = false;
        $this->dispatch('close-create-task');
    }

    public function closeFancybox()
    {
        $this->open = false;
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

        try {
            $this->sendData = true;
            DB::transaction(function () {
                $activit = ActivitTask::create([
                    'module_id' => $this->selectedLessonId,
                    'discipline_id' => $this->disciplineId,
                    'teacher_id' => Auth::user()->id,
                ]);
                foreach ($this->questions as $question) {
                    $task = $activit->tasks()->create([
                        'question' => $question['title'],
                        'type' => $question['type'] == 'radio' ? 'multiple_choice' : 'dissertative',
                    ]);
                    // Quando criar uma nova opção
                    $lastOrder = $task->options()->max('order');
                    if ($question['type'] === 'radio') {
                        foreach ($question['options'] as $option) {
                            $task->options()->create([
                                'text' => $option['text'],
                                'correct' => $option['is_correct'],
                                'order' => $lastOrder + 1,
                            ]);
                        }
                    }
                }
            });
            $this->dispatch('task-created-success', message: 'Atividade criada com sucesso!');
            sleep(3);
            $this->resetFormCreation();
            $this->close();
            $this->questions = [];
        } catch (\Exception $e) {
            $this->addError('save', 'Erro ao salvar tarefa: ' . $e->getMessage());
        }
    }

    public function cancelCreation()
    {
        $this->resetFormCreation();
        $this->close();
    }
}; ?>
<div x-data wire:show="open" @keydown.escape="open = false" x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0" class="fixed inset-0 z-40 overflow-auto bg-white" x-cloak>
    <div class="flex items-center justify-between w-full px-4 py-3 border-gray-200 border-b-[1px] - h-16">
        <h2 class="text-[15px] font-semibold text-gray-900 tracking-wide">Criar Atividade ou Avaliação</h2>
        <x-mary-button icon="o-x-mark" class="btn btn-ghost" wire:click="close" />
    </div>
    <div class="flex w-full px-4 overflow-auto " id="create-activity-task" data-tour="{{ $disciplineId }}">
        <div x-bind:class="!$store.expandedPreview ? 'w-2/6' : 'hidden'"
            class="flex flex-col p-5 transition-all duration-300 ease-in-out bg-white border-gray-300 border-r-[1px]">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2" id="create-title-question-{{ $disciplineId }}">
                    <label for="titleOfAnuanced" class="tracking-wide text-gray-800 font-medium text-[16px]">
                        Enunciado</label>
                    <textarea wire:model.live.debounce.0ms="titleOfQuestion"
                        class="w-full p-3 min-h-[150px] font-normal  tracking-wide text-[15px] border-gray-200 bg-gray-50  border-[1px]   focus:border-pink-500 focus:outline focus:outline-pink-500"
                        placeholder="Digite o enunciado (Enter para quebras de linha)" id="id_titleofquestion">
                    </textarea>
                    <div class="w-full py-2">
                        @error('titleOfQuestion')
                            <span class="text-red-500 error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div>
                    <h3 class="tracking-wide text-gray-800 text-[16px]">Tipo da resposta</h3>
                </div>
                <div class="flex py-4" id="question-select-types-{{ $disciplineId }}">
                    @foreach ($questionTypes as $type)
                        <button type="button" wire:click="selectedQuestionType('{{ $type['type'] }}')"
                            @class([
                                'tracking-wide cursor-pointer flex items-center gap-2 px-4 py-2 text-[15px] transition-all duration-300 ease-in-out border-b-[1px] border-gray-300 ',
                                'bg-gray-100 text-gray-800 border-gray-700 shadow-md' =>
                                    $questionTypeSelected === $type['type'],
                                'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' =>
                                    $questionTypeSelected !== $type['type'],
                            ]) id="question-select-{{ $type['type'] }}-{{ $disciplineId }}">
                            <span class="text-sm font-normal">{{ $type['title'] }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="mt-4">
                    @if ($questionTypeSelected === 'radio')
                        <div class="flex flex-col gap-3">
                            <div class="text-[15px] text-gray-800 font-normal tracking-wide">Digite uma
                                opção</div>
                            <div class="flex flex-col gap-2">
                                <div class="flex gap-2">
                                    <x-text-input wire:model.live.debounce.0ms="newOptionText"
                                        id="create-question-option-{{ $disciplineId }}" placeholder="Digite uma opção"
                                        wire:keydown.enter="addOptionRadio"
                                        class=" py-2 px-3 text-[15px] w-full focus:border-pink-500 focus:outline focus:outline-pink-500" />
                                    <x-primary-button type="button" @click.prevent="$wire.addOptionRadio"
                                        id="create-question-option-add-{{ $disciplineId }}"
                                        x-bind:disabled="$wire.newOptionText.length < 5"
                                        x-bind:class="$wire.newOptionText.length < 5 ?
                                            'bg-red-300 pointer-events-none hover:bg-red-300' : ''">
                                        <x-mary-icon name="o-plus" />
                                    </x-primary-button>
                                </div>
                                <div class="w-full py-2">
                                    @error('newOptionText')
                                        <span class="text-pink-500 error">{{ $errors->first('newOptionText') }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-between gap-3">
                            <div class="flex flex-col gap-2">
                                <div class="flex flex-col gap-2">
                                    <div class="flex flex-col gap-4">
                                        <!-- Lista de Opções -->
                                        <div class="mt-3 space-y-2" id="question-options-list-{{ $disciplineId }}">
                                            @foreach ($options as $index => $option)
                                                <div class="flex items-center gap-3 p-3 bg-white border rounded-lg">
                                                    <!-- Radio Button -->
                                                    <input type="radio" name="correct_option"
                                                        wire:model="correctOptionIndex"
                                                        wire:click.prevent="setCorrectOption({{ $index }})"
                                                        {{ $option['is_correct'] ? 'checked' : '' }}
                                                        class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                                    <x-text-input wire:model="options.{{ $index }}.text"
                                                        class="flex-1 border-none focus:ring-0" />

                                                    <!-- Botão Remover -->
                                                    <button type="button"
                                                        wire:click.prevent="removeOption({{ $index }})"
                                                        class="text-red-500 hover:text-red-700">
                                                        <x-mary-icon name="o-trash" class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-xs text-gray-700 text-left">
                            Questão dissertativa <br />
                            O aluno verá uma área de texto para resposta.
                        </div>
                    @endif
                </div>

                <div class="self-end mt-4">
                    @if ($questionTypeSelected === 'radio')
                        <button type="submit" wire:click.prevent="addQuestion"
                            id="create-question-button-radio-{{ $disciplineId }}"
                            class="px-4 py-2 text-white transition-colors rounded-lg"
                            x-bind:class="$wire.titleOfQuestion.length >= 8 && $wire.options.length >= 2 ?
                                'bg-indigo-500 hover:bg-indigo-600' :
                                'bg-indigo-300 cursor-not-allowed'"
                            x-bind:disabled="$wire.titleOfQuestion.length < 8 || $wire.options.length < 2">
                            Criar pergunta de multipla escolha
                        </button>
                    @else
                        <button type="submit" wire:click.prevent="addQuestion"
                            id="create-question-button-text-{{ $disciplineId }}"
                            class="px-4 py-2 text-white transition-colors rounded-lg"
                            x-bind:class="$wire.titleOfQuestion.length >= 8 ?
                                'bg-indigo-500 hover:bg-indigo-600' :
                                'bg-indigo-300 cursor-not-allowed'"
                            x-bind:disabled="$wire.titleOfQuestion.length < 8">Criar pergunta dissertativa
                        </button>
                    @endif
                </div>
            </div>
        </div>
        <div x-bind:class="!$store.expandedPreview ? 'w-2/3' : 'w-full'" class="p-5 bg-white">
            <div class="relative mt-10">
                <div class="flex items-center justify-between">
                    <x-mary-button label="Visualizar em tela cheia" icon="o-eye" class="font-medium"
                        x-on:click="$store.expandedPreview = !$store.expandedPreview" />
                    <span class="text-sm font-semibold tracking-wider text-gray-600 uppercase">{{ count($questions) }}
                        Questões</span>
                </div>
                @if (count($questions) > 0)
                    <div class="space-y-6">
                        @foreach ($questions as $qIndex => $question)
                            <div wire:key="question-{{ $qIndex }}" class="p-4 bg-white">
                                <!-- Edição do Título -->
                                @if ($editingQuestionIndex === $qIndex && $editingOptionIndex === null)
                                    <div class="flex items-center gap-2 mb-3">
                                        <span class="font-bold text-gray-700">{{ $qIndex + 1 }}.</span>
                                        <textarea wire:model="editingQuestionTitle"
                                            class="flex-1 min-h-[150px] font-normal  tracking-wide text-[15px] bg-gray-50 p-3 border-[1px] rounded-lg focus:ring-1 focus:ring-pink-300"
                                            wire:keydown.enter="saveQuestionEdit" autofocus></textarea>
                                        <div class="flex gap-1">
                                            <button wire:click="saveQuestionEdit"
                                                class="p-1 text-indigo-600 bg-indigo-200 hover:text-indigo-800">
                                                <x-mary-icon name="o-pencil-square" class="w-5 h-5" />
                                            </button>
                                            <button wire:click="cancelEditing"
                                                class="p-1 text-red-600 hover:text-red-800">
                                                <x-mary-icon name="o-x-mark" class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-start justify-between"
                                        id="question-created-{{ $disciplineId }}">
                                        <div class="flex items-center">
                                            <div class="text-gray-800 whitespace-pre-line cursor-pointer "
                                                wire:click="startEditingQuestion({{ $qIndex }})">
                                                <span>{{ $qIndex + 1 }}</span> .{{ $question['title'] }}
                                            </div>
                                        </div>
                                        <button wire:click="removeQuestion({{ $qIndex }})" type="button"
                                            class="ml-2 text-gray-400 hover:text-red-500">
                                            <x-mary-icon name="o-trash" class="w-4 h-4 text-red-500" />
                                        </button>
                                    </div>
                                @endif

                                <!-- Conteúdo baseado no tipo -->
                                @if ($question['type'] === 'radio')
                                    <!-- Edição de Opções -->
                                    <div class="mt-3 ml-6 space-y-2">
                                        @foreach ($question['options'] as $optIndex => $option)
                                            @if ($editingQuestionIndex === $qIndex && $editingOptionIndex === $optIndex)
                                                <div class="items-center gap-2 fslex">
                                                    <span class="w-5 text-gray-500">{{ chr(65 + $optIndex) }}.</span>
                                                    <x-text-input wire:model="editingOptionText" class="flex-1"
                                                        wire:keydown.enter="saveOptionEdit" autofocus />
                                                    <div class="flex gap-1">
                                                        <button wire:click="saveOptionEdit"
                                                            class="p-1 text-green-600 hover:text-green-800">
                                                            <x-mary-icon name="o-check" class="w-4 h-4" />
                                                        </button>
                                                        <button wire:click="cancelEditing"
                                                            class="p-1 text-red-600 hover:text-red-800">
                                                            <x-mary-icon name="o-x-mark" class="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="w-5 text-gray-500 lowercase">{{ chr(65 + $optIndex) }}.</span>
                                                    <input type="radio" name="correct_option_{{ $qIndex }}"
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
                                @else
                                    <!-- Área dissertativa (não editável no preview) -->
                                    <div class="mt-3">
                                        <textarea class="rounded-sm border-gray-200 w-full min-h-[80px] bg-white" placeholder="Resposta do aluno" disabled></textarea>

                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="w-full p-3 flex flex-col gap-3">
                        <div class="mb-4" id="create-question-associate-module-{{ $disciplineId }}">
                            <label class="block mb-1 text-sm font-medium text-gray-700">Vincular à aula:</label>
                            <x-mary-select wire:model="selectedLessonId" placeholder="Selecione uma aula"
                                :options="$lessons" option-label="title" option-value="id" class="w-full" />
                        </div>
                        <div class="self-end">
                            <x-mary-button label="Cancelar" type="button" wire:click="cancelCreation"
                                wire:loading.attr="disabled" icon="o-x-mark" class="bg-red-500 text-red-50" />
                            <x-mary-button label="Criar enunciado" type="submit" wire:click="save"
                                icon="o-clipboard" class="bg-blue-600 text-blue-50" wire:loading.attr="disabled" />
                            <div wire:loading wire:target="save" class="flex items-center gap-2">
                                <span class="text-sm text-pink-600 tracking-wide">Salvando as questionário</span>
                                <x-mary-loading class=" text-pink-600 loading-dots" />
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
