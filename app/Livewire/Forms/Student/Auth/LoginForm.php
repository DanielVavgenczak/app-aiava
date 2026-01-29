<?php

namespace App\Livewire\Forms\Student\Auth;

use App\Models\Teacher\Student;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required', message: 'O e-mail é obrigatorio!')]
    public string $email = '';

    #[Validate('required', message: 'O senha é obrigatorio!')]
    public string $password = '';

    public function authenticationStudent()
    {
        //TODO: adicionar remember: true, porem validar com tempo
        $this->validate();
        if (Auth::guard('student')->attempt(credentials: [
            'email' => $this->email,
            'password' => $this->password
        ])) {
            $student = Student::where('email', $this->email)->first();
            session()->regenerate();
        }
        //session('error', 'credenciais não correspondentes');
        $this->addError('error', 'As credenciais fornecidas correspondem aos nossos registros.');
    }
}
