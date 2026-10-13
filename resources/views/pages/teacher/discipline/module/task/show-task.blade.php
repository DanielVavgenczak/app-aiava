<?php

use Livewire\Component;
use Livewire\Attributes\{On, Computed, Locked};
use App\Models\Teacher\{ActivitTask};
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    public bool $showTaskModule = false;
    #[Locked]
    public ?ActivitTask $activitTask = null;
    #[Locked]
    public string $taskId = '';

    #[Computed]
    public function countAnswers()
    {
        return $this->activitTask?->studentAnwsers()->count() ?? 0;
    }

    /** Edit **/
    #[Locked]
    public ?int $editingQuestionIndex = null;
    #[Locked]
    public ?int $editingOptionIndex = null;
    public string $editingQuestionTitle = '';
    public string $editingOptionText = '';

    #[On('task-show')]
    public function open($activit_id)
    {
        $teacher = Auth::guard('teacher')->user();

        $activitTask = ActivitTask::with([
            'tasks' => function ($query) {
                $query->with(['studentAnwsers', 'options'])->orderBy('created_at');
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

    #[On('task-deleted')]
    public function taskDeleted(): void
    {
        $this->activitTask = null;
        $this->taskId = '';
        $this->cancelMakeTask();
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

        $taskToRemove = $this->activitTask->tasks[$index] ?? null;
        if (! $taskToRemove) {
            $this->error('Questão inválida.');
            return;
        }

        // Remoção mantida desabilitada conforme comportamento original.
    }
}; ?>
<x-mary-drawer wire:model="showTaskModule" class="w-10/12 mx-auto z-50">
    <x-mary-button icon="o-x-mark" class="btn btn-ghost right-0" x-on:click="$wire.cancelMakeTask" />
    <header class="flex items-center justify-between p-3">
        <h2 class="text-[15px] font-medium text-gray-700">Atividade</h2>
        <div class="flex items-center gap-4">
          @if(isset($activitTask->total_value))
          <div>
            <span>Total: {{$activitTask->total_value}}</span>
          </div>
          @else
            <div>
              <span>Não avaliativa</span>
            </div>
          @endif
           @if ($activitTask)
                <button
                   type="button"
                   x-on:click="$dispatch('open-modal-delete', {id:'{{ $activitTask->id }}'})"
                   class="btn btn-sm bg-pink-700 text-white">
                  <x-mary-icon name="o-trash" class="w-6 h-6 mr-1" /> Deletar atividade
                </button>
                <livewire:pages::teacher.discipline.module.task.delete-task
                  :activittask="$activitTask"
                  wire:key="activity-{{$activitTask->id}}" />
            @endif
        </div>
    </header>
    <div class="w-11/12 p-3 mx-auto ">
        <div>
            @if ($activitTask && $activitTask->tasks()->count() > 0)
                @foreach ($activitTask->tasks as $index => $task)
                    <div wire:key="task-{{ $index }}" class=" bg-white ">
                        <!-- Edição do Título -->
                        @if ($editingQuestionIndex === $index && $editingOptionIndex === null)
                            <div class="flex items-center gap-2 mb-3">
                                <span class="font-bold text-gray-700"> {{ $task['order'] }}.</span>
                                <textarea wire:model="editingQuestionTitle"
                                    class="textarea !outline-none focus:border-gray-400 "
                                    wire:keydown.enter="saveQuestionEdit" autofocus></textarea>
                                <div class="flex gap-1">
                                    <x-mary-button wire:click="saveQuestionEdit" icon="o-pencil-square"
                                        class="btn btn-sm bg-gray-200  text-gray-600" spinner />

                                    <x-mary-button wire:click="cancelEditing" icon="o-x-mark"
                                        class="btn btn-sm bg-gray-200  text-gray-600" spinner />

                                </div>
                            </div>
                        @else
                            <div class="flex items-center justify-between">
                                <div class="flex items-center ">
                                    <div class="text-gray-800 whitespace-pre-line cursor-pointer">
                                        <span> {{ $task['order'] }}</span> .{{ $task['question'] }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                  @if(isset($task['value']))
                                    <div class="text-sm text-gray-600">
                                      <span>Nota:{{$task['value']}}</span>
                                    </div>
                                    @endif
                                      <x-mary-button label="Editar"
                                          wire:click="startEditingQuestion({{ $index }})" icon="o-pencil-square"
                                          class="btn-sm btn" spinner />
                                      @if ($task->type !== 'multiple_choice')
                                        <span class="badge bg-red-300">{{ $task->studentAnwsers->count() }}</span>
                                          {{-- <x-mary-button class="btn-sm" label="Entregas"
                                              badge="{{ $task->studentAnwsers->count() }}"
                                              badge-classes="bg-gray-500 text-white"
                                              x-on:click="$dispatch('event-teacher-show-student-answer',{task_id: '{{ $task->id }}'})" /> --}}
                                      @endif

                                      <button wire:click="removeQuestion({{ $index }})" type="button"
                                          class="btn btn-sm">
                                          <x-mary-icon name="o-trash" class="w-4 h-4 text-gray-500" />
                                          Remover
                                      </button>

                                </div>
                            </div>
                        @endif

                        <!-- Conteúdo baseado no tipo     -->
                        @if ($task['type'] === 'multiple_choice')
                            <!-- Edição de Opções -->
                            <div class="mt-3 ml-6 space-y-2">
                                @foreach ($task['options'] as $optIndex => $option)
                                    @if ($editingQuestionIndex === $index && $editingOptionIndex === $optIndex)
                                        <div class="items-center gap-2 flex">
                                            <span class="w-5 text-gray-500">{{ chr(65 + $optIndex) }}.</span>
                                            <x-mary-input wire:model="editingOptionText"
                                                class="px-3 py-2 text-[15px] flex-1 focus:border-red-500 focus:outline focus:outline-red-500"
                                                wire:keydown.enter="saveOptionEdit" autofocus />
                                            <div class="flex gap-1">
                                                {{-- <div>
                                                 <span>Nota: {{}}</span>
                                                </div> --}}
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
                                            <span class="flex items-center w-10 text-gray-500 uppercase">
                                                @if ($option['correct'])
                                                    <x-mary-icon name="o-check-circle"
                                                        class="relative  text-green-700" />
                                                @endif
                                                {{ chr(65 + $optIndex) }}.
                                            </span>
                                            <input type="radio" name="correct_option_{{ $index }}"
                                                wire:change="updateCorrectOption({{ $index }}, {{ $optIndex }})"
                                                @checked($option['correct']) class="text-indigo-600 border-gray-300">
                                            <span @class([
                                                'flex-1 cursor-pointer',
                                                'font-semibold text-indigo-700' => $option['correct'],
                                            ])
                                                wire:click="startEditingOption({{ $index }}, {{ $optIndex }})">
                                                {{ $option['text'] }}
                                            </span>

                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <!-- Área dissertativa (não editável no preview) -->
                            <div class="mt-3">

                            </div>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
        {{-- <livewire:pages.teacher.discipline.module.task.student-anwsers /> --}}
    </div>
</x-mary-drawer>
