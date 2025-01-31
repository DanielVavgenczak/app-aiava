<?php

namespace App\Livewire\Teacher\Content;

use Livewire\Component;

class UploadBlock extends Component
{
    public $module_id;
    public function render()
    {
        return view('livewire.teacher.content.upload-block');
    }
}
