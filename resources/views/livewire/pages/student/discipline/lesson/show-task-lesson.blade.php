<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{On};
use App\Models\Teacher\{ActivitTask};
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    public bool $show = false;
    public ?ActivitTask $activitTask;

    #[On('student-open-task')]
    public function open($task_id)
    {
        $this->activitTask = ActivitTask::with([
            'tasks' => function ($query) {
                $query->with(['options'])->orderBy('created_at');
            },
        ])->find($task_id);
        $this->show = true;
    }

    public function cancelTask()
    {
        $this->show = false;
    }
}; ?>
<x-mary-drawer wire:model="show" class="w-full" with-close-button close-on-escape>
    <x-mary-button icon="o-x-mark" class="btn btn-ghost" x-on:click="$wire.show = false" />
    <header class="flex items-center justify-between p-5">
        <div class="flex flex-col">
            <h2 class="text-lg font-semibold tracking-wide text-gray-700 capitalize">Atividade</h2>
            <p>Nome da disciplina</p>
        </div>
    </header>
    <div class="w-11/12 mx-auto">
        @if ($activitTask && $activitTask->tasks()->count() > 0)
            <div class="flex flex-col gap-6">
                @foreach ($activitTask->tasks as $index => $task)
                    <div class="">
                        <div class="mb-2">
                            {{ $index + 1 }}. {{ $task->question }}
                        </div>
                        @if ($task->type !== 'dissertative')
                            @foreach ($task->options as $index => $options)
                                <div class="flex items-center my-5">
                                    <span class="uppercase text-gray-700 mr-1">
                                        {{ chr(65 + $index) }}.</span>
                                    <x-mary-radio :options="[$options]" option-label="text" option-value="id"
                                        id="option-{{ $index }}" class="!outline-none w-[20px] h-[20px]" />
                                </div>
                            @endforeach
                        @else
                            <div class="">
                                <x-mary-textarea class="w-full !outline-none" placeholder="Descreva sua respostas" />
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="actions mt-10">
                <button class="btn btn-md btn-ghost tracking-wide" x-on:click="$wire.show = false">
                    Cancelar
                </button>
                <button class="btn btn-md bg-pink-700 text-gray-50">
                    Enviar
                </button>
            </div>
        @endif
    </div>
</x-mary-drawer>
