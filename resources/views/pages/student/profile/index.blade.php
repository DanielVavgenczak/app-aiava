<?php

use Livewire\Component;
use Livewire\Attributes\{Layout};

new #[Layout('layouts::student')] class extends Component {
    public function maskedPhone(): string
    {
        $phone = Auth::guard('student')->user()?->phone;

        if (! $phone) {
            return 'Não informado';
        }

        if (strlen($phone) <= 4) {
            return str_repeat('•', strlen($phone));
        }

        return substr($phone, 0, 2) . str_repeat('•', strlen($phone) - 4) . substr($phone, -2);
    }
}; ?>
<?php $student = Auth::guard('student')->user(); ?>
<div>
    <livewire:global.breadcrumb :items="[
        ['label' => 'Disciplinas', 'route' => 'student.discipline', 'icon' => 'o-academic-cap'],
        ['label' => 'Perfil'],
    ]" color="pink" />
    <div class="p-5 bg-white">
        <div class="flex flex-col items-center gap-3">
            <div class="flex items-center justify-between w-full gap-4" id="student-profile-section">
                <div aria-hidden="true"></div>
                <div class="flex flex-col items-center gap-2" id="student-profile-avatar">
                    @if ($student->hasAvatar())
                        <img src="{{ $student->avatar_url }}"
                            class="w-20 h-20 rounded-full object-cover"
                            alt="Foto de perfil de {{ $student->full_name }}" />
                    @else
                        <livewire:global.user-avatar
                            initiais="{{ $student->initialLetterToAvatar() }}" />
                    @endif
                    <h3 class="text-base text-gray-600">{{ $student->full_name }}</h3>
                </div>
                <div class="flex items-start gap-2">
                    <x-mary-button id="btn-edit-profile" label="Editar Perfil" icon-right="o-pencil-square"
                        x-on:click="$dispatch('open-modal-update-profile', { open:true })" />
                    <x-mary-button id="btn-open-password-change" label="Alterar senha" icon-right="o-lock-closed"
                        x-on:click="$dispatch('student::open-change-password')"
                        class="btn-ghost btn-sm" />
                </div>
                <livewire:student.profile.update />
                <livewire:student.profile.change-password />
            </div>
        </div>

        <section class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4"
            aria-labelledby="student-profile-account-title">
            <h2 id="student-profile-account-title" class="text-lg font-semibold text-gray-700">
                Informações da conta
            </h2>

            @if ($student->must_change_password)
                <div role="status"
                    class="mt-3 rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-800">
                    Você está usando uma senha temporária. Crie uma nova senha para proteger sua conta.
                </div>
            @endif

            <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Nome</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $student->full_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">E-mail</dt>
                    <dd class="mt-1 text-sm text-gray-800 break-all">{{ $student->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Telefone</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $this->maskedPhone() }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Situação</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $student->status_label }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Membro desde</dt>
                    <dd class="mt-1 text-sm text-gray-800">
                        {{ $student->created_at?->format('d/m/Y') ?? 'Não informado' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Turma</dt>
                    <dd class="mt-1 text-sm text-gray-800">
                        {{ optional($student->classe)->name ?? 'Não informada' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Disciplina</dt>
                    <dd class="mt-1 text-sm text-gray-800">
                        {{ optional($student->discipline)->title ?? 'Não informada' }}
                    </dd>
                </div>
                @if ($student->teacher)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Professor</dt>
                        <dd class="mt-1 text-sm text-gray-800">{{ $student->teacher->fullName() }}</dd>
                    </div>
                @endif
            </dl>
        </section>
    </div>

    <div x-data class="fixed inset-0 z-40 bg-gray-900/50" x-show="$store.tourguide.openMai">
        <div
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-full sm:w-1/2 bg-white rounded-lg shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">
            <div class="flex items-center justify-between">
                <h2 class="text-[14px] font-normal text-gray-700">
                    Tour do perfil
                </h2>
                <x-mary-button icon="o-x-mark" class="btn btn-sm btn-ghost" x-on:click="$store.tourguide.cancelOptionsTourPage()" />
            </div>
            <div class="p-4 bg-gray-100">
                <div class="flex flex-col gap-3">
                    <p class="text-sm tracking-wide text-gray-600">
                        Conheça as funcionalidades do seu perfil:
                    </p>
                    <x-mary-button label="Editar foto de perfil"
                        x-on:click="$store.tourguide.start('StartTourStudentProfile')"
                        class="btn-primary btn-sm" />
                </div>
            </div>
            <img src="{{ asset('images/ai-sos.png') }}" class="fixed right-0 w-64 bottom-4 sm:w-48">
        </div>
    </div>
</div>
