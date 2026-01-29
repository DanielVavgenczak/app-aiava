<?php

namespace App\Livewire\Layout;

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class HeaderTopApp extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect(route('teacher.login'), navigate: true);
    }
    public function render()
    {
        return view('livewire.layout.header-top-app');
    }
}
