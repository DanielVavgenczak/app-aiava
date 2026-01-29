<?php

namespace App\Http\Livewire\Pages\Teacher\Discipline\Module\Task;

use Livewire\Component;

class DeleteTask extends Component
{
    public $activittask;

    public function render()
    {
        return view('livewire.pages.teacher.discipline.module.task.delete-task');
    }
}
