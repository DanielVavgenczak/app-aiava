<?php

namespace App\Http\Livewire\Pages\Teacher\Classes\Modal;

use Livewire\Component;

class EditStudent extends Component
{
    public $student;

    public function render()
    {
        return view('livewire.pages.teacher.classes.modal.edit-student');
    }
}
