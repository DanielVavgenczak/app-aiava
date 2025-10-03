<?php

namespace App\Livewire\Teacher\Profile;

use Illuminate\Container\Attributes\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Psy\Readline\Hoa\Console;

class Index extends Component
{     
     public function render()
    {
        return view('livewire.teacher.profile.index');
    }
}
