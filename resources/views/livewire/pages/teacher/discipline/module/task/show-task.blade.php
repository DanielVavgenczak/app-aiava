<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{On};
use App\Models\Teacher\{ActivitTask};
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    public bool $showTaskModule = false;
    public ?ActivitTask $activitTask = null;
    public string $taskId = '';

    /** Edit **/
    public ?int $editingQuestionIndex = null;
    public ?int $editingOptionIndex = null;
    public string $editingQuestionTitle = '';
    public string $editingOptionText = '';

    #[On('task-module')]
    public function open($activit_id)
    {
        $this->showTaskModule = true;
        $this->activitTask = ActivitTask::with([
            'tasks' => function ($query) {
                $query->with(['options'])->orderBy('created_at'); // Carrega as options de cada task
            },
        ])->find((string) $activit_id);
    }

    public function startEditingQuestion(int $index)
    {
        $this->editingQuestionIndex = $index;
        $this->editingQuestionTitle = $this->activitTask->tasks[$index]['question'];
    }

    public function saveQuestionEdit()
    {
        $this->validate(['editingQuestionTitle' => 'required|min:8']);
        $taskEdit = $this->activitTask->tasks[$this->editingQuestionIndex];
        $taskEdit->update([
            'question' => $this->editingQuestionTitle,
        ]);
        sleep(2);
        $this->success('Questão editada com sucesso');
        $this->cancelEditing();
    }

    public function startEditingOption(int $qIndex, int $optIndex)
    {
        $this->editingQuestionIndex = $qIndex;
        $this->editingOptionIndex = $optIndex;
        $this->editingOptionText = $this->activitTask->tasks[$qIndex]->options[$optIndex]->text;
    }

    public function saveOptionEdit()
    {
        $this->validate(['editingOptionText' => 'required|min:1|max:255']);
        $taskOptionEdit = $this->activitTask->tasks[$this->editingQuestionIndex];
        $option = $taskOptionEdit->options[$this->editingOptionIndex];
        $option->update([
            'text' => $this->editingOptionText,
        ]);
        sleep(2);
        $this->cancelEditing();
        $this->success('Sucesso ao editar opção');
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
    }
    public function closeToskDelete()
    {
        $this->showTaskModule = false;
    }
}; ?>
<x-mary-drawer wire:model="showTaskModule" class="w-full" with-close-button close-on-escape>
    <x-mary-button icon="o-x-mark" class="btn btn-ghost" wire:click="cancelMakeTask" />
    <header class="flex items-center justify-between p-5">
        <h2 class="text-lg font-semibold text-gray-800">Atividade</h2>
        <div>
            <x-mary-button label="Analisar entregas" icon="o-eye" class="btn-outline border-pink-600 text-pink-600" />
            @if ($activitTask)
                <x-mary-button label="Deletar atividade" type="button"
                    x-on:click="$dispatch('open-modal-delete', {id:'{{ $activitTask->id }}'})" icon="o-trash"
                    class="btn-outline border-pink-600 text-pink-600" />
                <livewire:pages.teacher.discipline.module.task.delete-task :activittask="$activitTask" />
            @endif
        </div>
    </header>
    <div class="p-5 mx-auto">
        @if ($activitTask && $activitTask->tasks()->count() > 0)
            @foreach ($activitTask->tasks as $index => $task)
                <div wire:key="task-{{ $index }}" class="p-4 bg-white ">
                    <!-- Edição do Título -->
                    @if ($editingQuestionIndex === $index && $editingOptionIndex === null)
                        <div class="flex items-center gap-2 mb-3">
                            <span class="font-bold text-gray-700">{{ $index + 1 }}.</span>
                            <textarea wire:model="editingQuestionTitle"
                                class="flex-1 min-h-[150px] font-normal  tracking-wide text-[15px] bg-gray-50 p-3 border-[1px] rounded-lg focus:ring-1 focus:border-pink-500 focus:outline focus:outline-pink-500"
                                wire:keydown.enter="saveQuestionEdit" autofocus></textarea>
                            <div class="flex gap-1">
                                <x-mary-button wire:click="saveQuestionEdit" icon="o-pencil-square"
                                    class="btn-outline border-blue-600 text-indigo-600" spinner />

                                <x-mary-button wire:click="cancelEditing" icon="o-x-mark"
                                    class="btn-outline border-red-600 text-red-600" spinner />

                            </div>
                        </div>
                    @else
                        <div class="flex items-start justify-between">
                            <div class="flex items-center ">
                                <div class="text-gray-800 whitespace-pre-line cursor-pointer "
                                    wire:click="startEditingQuestion({{ $index }})">
                                    <span>{{ $index + 1 }}</span> .{{ $task['question'] }}
                                </div>
                            </div>
                            <button wire:click="removeQuestion({{ $index }})" type="button"
                                class="ml-2 text-gray-400 hover:text-red-500">
                                <x-mary-icon name="o-trash" class="w-4 h-4 text-red-500" />
                            </button>
                        </div>
                    @endif

                    <!-- Conteúdo baseado no tipo -->
                    @if ($task['type'] === 'multiple_choice')
                        <!-- Edição de Opções -->
                        <div class="mt-3 ml-6 space-y-2">
                            @foreach ($task['options'] as $optIndex => $option)
                                @if ($editingQuestionIndex === $index && $editingOptionIndex === $optIndex)
                                    <div class="items-center gap-2 fslex">
                                        <span class="w-5 text-gray-500">{{ chr(65 + $optIndex) }}.</span>
                                        <x-text-input wire:model="editingOptionText"
                                            class="px-3 py-2 text-[15px] flex-1 focus:border-pink-500 focus:outline focus:outline-pink-500"
                                            wire:keydown.enter="saveOptionEdit" autofocus />
                                        <div class="flex gap-1">
                                            <x-mary-button wire:click="saveOptionEdit" icon="o-pencil"
                                                class="btn btn-xs btn-outline border-blue-600 text-blue-600 hover:text-blue-800"
                                                spinner />
                                            <x-mary-button wire:click="cancelEditing" icon="o-x-mark"
                                                class="btn btn-xs btn-outline border-red-600 text-red-600 hover:text-red-800"
                                                spinner />
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2">
                                        <span class="w-5 text-gray-500 lowercase">{{ chr(65 + $optIndex) }}.</span>
                                        <input type="radio" name="correct_option_{{ $index }}"
                                            wire:change="updateCorrectOption({{ $index }}, {{ $optIndex }})"
                                            @checked($option['correct']) class="text-indigo-600 border-gray-300">
                                        <span @class([
                                            'flex-1 cursor-pointer',
                                            'font-semibold text-indigo-700' => $option['is_correct'],
                                        ])
                                            wire:click="startEditingOption({{ $index }}, {{ $optIndex }})">
                                            {{ $option['text'] }}
                                        </span>
                                        @if ($option['correct'])
                                            <x-mary-icon name="o-check-circle" class="w-4 h-4 text-green-500" />
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
        @endif
        <x-slot:actions>
            <x-mary-button label="Cancel" wire:click="showTaskModule = false" />
            <x-mary-button label="Confirm" class="btn-primary" icon="o-check" />
        </x-slot:actions>
    </div>
</x-mary-drawer>
