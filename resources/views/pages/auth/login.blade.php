<?php

use Livewire\Component;
use Livewire\Attributes\{Layout};
use App\Livewire\Forms\Auth\Login;
use Mary\Traits\Toast;

new #[Layout('layouts.guest')] class extends Component {
    use Toast;

    public Login $form;
    public function mount(){
        if(session('status-registed')) {
          $this->toast('success', session('status-registed'));
        }
    }
    public function login()
    {
        return match($this->form->authenticateuniquepagelogin()) {
        'teacher' => (function() {
              return $this->redirect(route('teacher.discipline'), navigate: true);
        })(),
        'student' => (function() {
                  if (session('must_change_password')) {
                      $this->warning(
                          'Você está usando a senha padrão. Por segurança, altere sua senha no perfil.',
                          timeout: 8000
                      );
                  }
                  return $this->redirect(route('student.discipline'), navigate: true);
        })(),
            default => $this->tapToast(),
        };

    }

    private function tapToast()
    {
        $this->error('Acesso negado: E-mail ou senha inválidos.',  timeout: 5000);
    }

}; ?>

<div>
        <div class="w-full text-center">
            <img src="/images/aiava-logo.png" class="w-32 mx-auto" />
            <h5 class="text-xl font-bold mt-3 text-gray-600">Login</h5>
        </div>
        <form wire:submit="login" class="mt-8 space-y-6">
            <div class="rounded-md  -space-y-px">
                <div>
                    <x-mary-input
                        label="E-mail"
                        wire:model="form.email"
                        class="!outline-none"
                        type="email"
                        placeholder="seu@email.com"
                    />
                </div>

                <div class="mt-4">
                    <x-mary-input
                        label="Senha"
                        wire:model="form.password"
                        class="!outline-none"
                        type="password"
                        placeholder="******"
                    />
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        wire:model="form.remember"
                        class="h-4 w-4 text-blue-600"
                    />
                    <label class="ml-2 text-sm text-gray-900">
                        Lembrar-me
                    </label>
                </div>
                <!-- TODO: criar rota de esqueci minha senha -->
                <a href="#"
                   class="text-sm font-medium text-blue-700 hover:text-blue-500">
                    Esqueceu a senha?
                </a>
            </div>

            <div>
                <button type="submit"
                        class="w-full btn bg-blue-400 hover:bg-blue-500 text-white">
                    Entrar
                </button>
            </div>

            <!-- Link para cadastro (só professor) -->
            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Professor sem conta?
                    <a href="{{ route('register') }}"
                       class="font-medium text-blue-600 hover:text-blue-500">
                        Cadastre-se aqui
                    </a>
                </p>
            </div>
        </form>
</div>
