<?php

namespace App\Livewire\Forms\Teacher;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Validate;
use Livewire\Form;
use App\Models\Teacher\Teacher;

class FormTeacherLogin extends Form
{
    #[Validate(['required', 'email'])]
    public string $email = '';
    #[Validate(['required', 'min:6'])]
    public string $password = '';

    public function authenticate()
{
    $this->validate();
    if (Auth::guard('teacher')->attempt([
        'email' => $this->email,
        'password' => $this->password
    ], true)) {
        $teacher = Teacher::where('email', $this->email)->first();
        session()->put('teacher_id', $teacher->id);
        session()->regenerate();
    }

    $this->addError('email', 'As credenciais fornecidas não correspondem aos nossos registros.');
}


}
