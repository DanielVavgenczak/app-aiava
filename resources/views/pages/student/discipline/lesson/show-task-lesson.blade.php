<?php
use Livewire\Component;
use Livewire\Attributes\{On};
use App\Models\Teacher\{ActivitTask, Task, TaskOptions, StudentAnwser};
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public bool $show = false;
    public ActivitTask $activitask;
    public array $tasks = [];
    public array $selectedOptions = []; // Para múltipla escolha
    public array $dissertativeAnswers = []; // Para dissertativas
    public $student = null;

    #[On('student-event-open-task')]
    public function open($id)
    {
        $this->show = true;
        $this->activitask = ActivitTask::with([
            'tasks' => function ($query) {
                $query->with(['options'])->orderBy('created_at');
            },
        ])->find($id);

        $this->student = Auth::guard('student')->user();
    }

    public function submitAnswers()
    {
        foreach ($this->activitask->tasks as $task) {
            if ($task->type === 'multiple_choice') {
                $this->saveMultipleChoiceAnswer($task);
            } else {
                $this->saveDissertativeAnswer($task);
            }
        }

        $this->success('Respostas enviadas com sucesso!');
        $this->cancelTask();
    }

    protected function saveMultipleChoiceAnswer(Task $task)
    {
        if (!empty($this->selectedOptions[$task->id])) {
            $optionId = $this->selectedOptions[$task->id];
            $isCorrect = TaskOptions::where('id', $optionId)->where('correct', true)->exists();

            StudentAnwser::create([
                'task_id' => $task->id,
                'student_id' => $this->student->id,
                'activit_task_id' => $this->activitask->id,
                'task_option_id' => $optionId,
                'is_correct' => $isCorrect,
            ]);
        }
    }

    protected function saveDissertativeAnswer(Task $task)
    {
        //dd($this->dissertativeAnswers[$task->id]);
        if (!empty($this->dissertativeAnswers[$task->id])) {
            StudentAnwser::create([
                'task_id' => $task->id,
                'student_id' => $this->student->id,
                'activit_task_id' => $this->activitask->id,
                'answer_text' => $this->dissertativeAnswers[$task->id],
                'is_correct' => false, // Será definido pelo professor
            ]);
        }
    }

    public function cancelTask()
    {
        $this->reset(['show', 'activitask', 'selectedOptions', 'dissertativeAnswers']);
    }
};
?>
<x-mary-drawer id="discipline-show-activity-drawer" wire:model="show" class="w-11/12 mx-auto" with-close-button close-on-escape>
    <!-- Cabeçalho -->
    <button class="btn-close absolute right-4 top-4 cursor-pointer" wire:click="cancelTask">
        <x-mary-icon name="o-x-mark" class="w-6 h-6 text-gray-600 hover:text-gray-800" />
    </button>
    <header class="flex items-center justify-between p-5 w-full">
        <h2 class="text-lg font-semibold text-gray-800">Atividade</h2>
        {{-- <div class="flex flex-col items-center bg-gray-100 p-2 rounded">
            <h3 class="uppercase text-[13px] text-gray-600 tracking-wide">tentativas</h3>
            <span class="text-[12px] font-bold text-gray-800">
                {{ $activitask->attempts_count ?? 0 }} <!-- Exemplo de contagem de tentativas -->
            </span>
        </div> --}}
    </header>
    <div class="w-11/12 mx-auto">
        @if ($activitask)
            <div class="flex flex-col gap-6">
                @foreach ($activitask->tasks as $task)
                    <div class="pb-4">
                        <div class="mb-2 font-medium">
                            {{ $loop->iteration }}. {{ $task->question }}
                        </div>

                        @if ($task->type === 'multiple_choice')
                            <div class="space-y-2">
                                @foreach ($task->options as $option)
                                    <div class="flex items-center my-2">
                                        <span class="uppercase text-gray-700 mr-2">
                                            {{ chr(65 + $loop->index) }}.
                                        </span>
                                        <x-mary-radio wire:model="selectedOptions.{{ $task->id }}" :options="[$option]"
                                            option-value="id" option-label="text" />
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="mt-3">
                                <x-mary-textarea wire:model="dissertativeAnswers.{{ $task->id }}"
                                    class="!outline-none" placeholder="Escreva sua resposta" rows="3" />
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="actions mt-10 flex justify-end gap-3 absolute bottom-4">
                <button x-on:click="$wire.cancelTask" class="btn btn-ghost btn-sm">cancelar</button>
                <button x-on:click="$wire.submitAnswers" class="bg-blue-400 text-blue-500 btn btn-sm">Enviar</button>
            </div>
        @endif
    </div>
</x-mary-drawer>
