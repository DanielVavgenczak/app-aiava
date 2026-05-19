<?php

namespace App\Livewire\Forms\Auth;

use App\Models\Teacher\Teacher;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Validate;
use Livewire\Form;
use Illuminate\Support\Facades\Log;

class Register extends Form
{
    //    #[Validate('required', message: 'O nome da classe é obrigatório!')]
    #[Validate('required', message: 'O Nome é obrigatório!')]
    #[Validate('min:3', message: 'O Nome deve ter no mínimo 3 caracteres!')]
    #[Validate('string', message: 'O Nome deve ser em formato de texto!')]
    public string $first_name = '';

    #[Validate('required', message: 'O sobrenome é obrigatório!')]
    #[Validate('min:3', message: 'O Sobrenome deve ter no mínimo 3 caracteres!')]
    #[Validate('string', message: 'O Sobrenome deve ser em formato de texto!')]
    public string $last_name = '';

    #[Validate('required', message: 'O e-mail é obrigatório!')]
    #[Validate('email', message: 'O e-mail deve ser valido!')]
    public string $email = '';

    #[Validate('required', message: 'A senha é obrigatório')]
    #[Validate('min:6', message: 'A senha deve ter no mínimo 8 caracteres!')]
    //#[Validate('same:password_confirmation', message: 'A senha e a confirmação de senha devem ser iguais!')]
    public string $password = '';
    #[Validate('required', message: 'A confirmação de senha é obrigatória!')]
    #[Validate('same:password', message: 'A senha e a confirmação de senha devem ser iguais!')]
    public string $password_confirmation = '';

    public function store()
    {
        $this->validate();
        try {
            Teacher::create([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
            ]);
            $this->reset();
            session()->flash('status-registed', 'Cadastro efetuado com sucesso!');

            return true;
        } catch (UniqueConstraintViolationException $error) {
            if (str_contains($error->getMessage(), 'teachers_email_unique')) {
                $this->addError('email', 'O e-mail ja foi cadastrado!');
                return false;
            }
        } catch (\Exception $error) {
            Log::error("Erro ao cadastrar professor: " . $error->getMessage(), [
                'email' => $this->email,
                'trace' => $error->getTraceAsString()
            ]);
            $this->addError('email', 'Erro inesperado. Tente novamente mais tarde.');
            return false;
        }
    }
}
