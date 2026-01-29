<?php

namespace App\Livewire\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Logout
{
    /**
     * Log the current user out of the application.
     */
    public function __invoke(): void
    {
      if(Auth::guard('teacher')->check()) {
         $this->logoutTeacher();
      }
    }

    protected function logoutTeacher()
    {
        Auth::guard('teacher')->logout();
        Session::invalidate();
        Session::regenerateToken();

    }
}
