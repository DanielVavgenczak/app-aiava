<?php

namespace App\Livewire\Teacher\Profile;

use Illuminate\Container\Attributes\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class Index extends Component
{     
     public function render()
    {
        return view('livewire.teacher.profile.index');
    }
}
