<?php

namespace App\Livewire\Forms\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class Login extends Form
{

    #[Validate('required', message: 'O e-mail é obrigatório!')]
    #[Validate('email', message: 'O e-mail deve ser válido!')]
    public string $email = '';

    #[Validate('required', message: 'A senha é obrigatória!')]
    public string $password = '';
    public bool $remember = false;

    /**
     * Authenticate user across multiple guards.
     *
     * Attempts authentication first as teacher, then as student.
     * Implements rate limiting to prevent brute-force attacks.
     *
     * @return string The authenticated guard type ('teacher'|'student') or 'unauthorized'
     *
     * @throws \Illuminate\Validation\ValidationException When validation fails
     */
    public function authenticateuniquepagelogin(): string
    {
        $this->validate();
        $this->ensureIsNotRateLimited();

        // Tenta autenticar como professor primeiro
        if (Auth::guard('teacher')->attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember
        )) {
            RateLimiter::clear($this->throttleKey());
            return 'teacher';
        }

        // Tenta autenticar como aluno
        if (Auth::guard('student')->attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember
        )) {
            RateLimiter::clear($this->throttleKey());
            return 'student';
        }
        // Se nenhum deu certo, incrementa tentativas e lança erro
        RateLimiter::hit($this->throttleKey());
        return 'unauthorized';
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email) . '|' . request()->ip());
    }
}
