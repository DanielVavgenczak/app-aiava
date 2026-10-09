<?php

use Livewire\Component;
use Livewire\Attributes\{On, Locked};
use App\Models\Teacher\ActivitTask;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public bool $openDelete = false;
    #[Locked]
    public ActivitTask $activittask;

    public function mount(ActivitTask $activittask)
    {
        $teacher = Auth::guard('teacher')->user();

        $owned = ActivitTask::query()
            ->where('id', $activittask->id)
            ->where('teacher_id', $teacher?->id)
            ->whereHas('discipline', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher?->id);
            })
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
        $this->activittask->delete();
        $this->dispatch('task-deleted');
        $this->openDelete = false;
    }
}; ?>

<x-mary-modal wire:model="openDelete" class="backdrop-blur">
    Tem certeza que deseja deletar essa atividade ?
    {{ $activittask->id }}
    <x-slot:actions>
        <x-mary-button label="Cancel" x-on:click="$openDelete = false" />
        <x-mary-button label="Sim" class="btn-error" wire:click="delete" spinner />
    </x-slot:actions>
</x-mary-modal>
