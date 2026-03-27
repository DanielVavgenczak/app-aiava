Eu tenho um AVA onde o professor pode adicionar um aluno manualmente, e apois o professor registrar esse aluno
nos geramos um login para esse aluno, com o email informado pelo professor e a senha padrão para o primeiro login
onde o mesmo pode alterar no primeiro acesso.

Porem eu preciso melhorar esse login pois a aluno acessa a area dela pela url: http://ava.com/aluno/login
eu vou alterar para  http://ava.com/login, assim tanto o professor quanto a aluno podem fazer o login por essa mesma rota.

Essa e a minha migration de estudante 
    Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name');
            $table->string('email')->unique()->nullable();
            $table->string('phone')->unique()->nullable();
            $table->foreignUuid('teacher_id')->constrained('teachers')->onDelete('cascade')->nullable();
            $table->foreignUuid('classe_id')->constrained('classes')->onDelete('cascade');
            $table->timestamps();
        });


        Esse e o componente de login apenar do professor onde o mesmo faz o login e e onde eu desejo unificar
        o login dos tipos de usuarios 
        <?php

use App\Livewire\Forms\Teacher\{FormTeacherLogin};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
new #[Layout('layouts.guest')] class extends Component {
    use Toast;
    public FormTeacherLogin $form;

    public function mount()
    {
        if (session('error')) {
            $this->error(session('error'), icon: 'o-trash', css: 'alert-error');
            session()->forget('error');
            $this->js("console.log('error')");
        }
    }
    /**
     * Handle an incoming authentication request.
     */
    public function loginTeacher(): void
    {
        $this->validate();
        $this->form->authenticate();
        $this->redirect(route('teacher.index'), navigate: true);
    }
}; ?>

<div>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('error')" />
    <form wire:submit="loginTeacher">
        <!-- Email Address -->
        <div>
            <x-mary-input label="E-mail" wire:model="form.email" class="!outline-none" placeholder="E-mail do professor" />

        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-mary-input label="Senha" wire:model="form.password" class="!outline-none" type="password"
                placeholder="******" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember" class="flex items-center justify-between">
                <div>
                    <a href="{{ route('teacher.register') }}"
                        class="text-sm font-normal text-gray-600 tracking-wide">Não possue
                        uma
                        conta?</a>

                </div>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="tracking-wide text-sm text-gray-600 underline rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    href="{{ route('password.request') }}" wire:navigate>
                    {{ __('Esqueceu a senha?') }}
                </a>
            @endif
            <button class="ms-3 btn btn-md bg-pink-600 text-gray-50 capitalize">
                Logar
            </button>
        </div>
    </form>
</div>

Aqui como posso prosseguir para esse ideia,


Agora pensando, o aluno so vai ter acesso e registro quando o professor o registrar, 
então a parte de registro de um professor eu posso também deixar em uma url mais limpa
por exemplo: http://ava/register, porem apenas professores porem se registrar
