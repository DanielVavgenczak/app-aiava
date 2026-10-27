<?php

use Livewire\Component;
use Livewire\Attributes\{On, Locked};
use App\Models\Teacher\ActivitTask;
use App\Support\ActivityNotificationService;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public bool $openDelete = false;
    #[Locked]
    public ActivitTask $activittask;

    public function mount(ActivitTask $activittask)
    {
        $teacher = Auth::guard('teacher')->user();

        $owned = ActivitTask::query()
            ->whereKey($activittask->id)
            ->ownedByTeacher($teacher)
            ->first();

        if (! $owned) {
            abort(404);
        }

        $this->activittask = $owned;
    }

    #[On('open-modal-delete')]
    public function open($id)
    {
        $this->openDelete = true;
    }

    public function delete()
    {
        try {
            ActivityNotificationService::forgetActivity($this->activittask);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->activittask->delete();
        $this->dispatch('task-deleted');
        $this->openDelete = false;
    }
}; ?>

<x-mary-modal wire:model="openDelete" class="backdrop-blur" title="Deletar atividade">
    Tem certeza que deseja deletar esta atividade?
    <span class="font-semibold">{{ $activittask->title }}</span>
    <x-slot:actions>
        <x-mary-button label="Cancelar" x-on:click="$openDelete = false" />
        <x-mary-button label="Sim, deletar" class="btn-error" wire:click="delete" spinner />
    </x-slot:actions>
</x-mary-modal>
