<?php

namespace App\Livewire\Layout;

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class HeaderTopApp extends Component
{
    public $authenticated;
    public $profileLink;

    public function mount()
    {
        $this->authenticatedUser();
    }
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect(route('login'), navigate: true);
    }

    public function render()
    {
        return view('livewire.layout.header-top-app');
    }


    private function authenticatedUser()
    {
        $isTeacher = Auth::guard('teacher')->check() ? Auth::guard('teacher')->user() : null;
        $isStudent = Auth::guard('student')->check() ? Auth::guard('student')->user() : null;


        $this->authenticated = $isTeacher ?: $isStudent;
        $this->profileLink = $isTeacher ? 'teacher.profile' : 'student.profile';
    }
}
