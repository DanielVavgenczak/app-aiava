<?php

namespace App\Http\Livewire\Pages\Teacher\Classes\Modal;

use Livewire\Component;

class DeleteStudent extends Component
{
    public $student;

    public function render()
    {
        return view('livewire.pages.teacher.classes.modal.delete-student');
    }
}
