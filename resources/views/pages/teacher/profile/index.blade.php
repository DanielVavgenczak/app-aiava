<?php

use App\Models\Teacher\Classe;
use Livewire\Component;
use Livewire\Attributes\{Layout};

new #[Layout('layouts::teacher')] class extends Component {


}; ?>
<?php
$teacher = Auth::guard('teacher')->user();
$disciplineIds = $teacher->disciplines()->pluck('id');
$classesCount = Classe::whereIn('discipline_id', $disciplineIds)->count();
?>
<div>
    <livewire:global.breadcrumb :items="[
        ['label' => 'Disciplinas', 'route' => 'teacher.discipline', 'icon' => 'o-academic-cap'],
        ['label' => 'Perfil'],
    ]" />
    <div class="p-5 bg-white">
        <div class="flex flex-col items-center gap-3">
            <div class="flex items-center justify-between w-full gap-4" id="teacher-profile-section">
                <div aria-hidden="true"></div>
                <div class="flex flex-col items-center gap-2" id="teacher-profile-avatar">
                    @if ($teacher->hasAvatar())
                        <img src="{{ $teacher->avatar_url }}"
                            class="w-20 h-20 rounded-full object-cover"
                            alt="Foto de perfil de {{ $teacher->fullName() }}" />
                    @else
                        <livewire:global.user-avatar
                            initiais="{{ $teacher->initialLetterToAvatar() }}" />
                    @endif
                    <h3 class="text-base text-gray-600">{{ $teacher->fullName() }}</h3>
                </div>
                <div class="flex items-start gap-2">
                    <x-mary-button id="btn-edit-profile" label="Editar Perfil" icon-right="o-pencil-square"
                        x-on:click="$dispatch('open-modal-update-profile', { open:true })" />
                    <x-mary-button id="btn-open-password-change" label="Alterar senha" icon-right="o-lock-closed"
                        x-on:click="$dispatch('teacher::open-change-password')"
                        class="btn-ghost btn-sm" />
                </div>
                <livewire:teacher.profile.update />
                <livewire:teacher.profile.change-password />
            </div>
        </div>

        <section class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4"
            aria-labelledby="teacher-profile-account-title">
            <h2 id="teacher-profile-account-title" class="text-lg font-semibold text-gray-700">
                Informações da conta
            </h2>

            <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Nome</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $teacher->fullName() }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">E-mail</dt>
                    <dd class="mt-1 text-sm text-gray-800 break-all">{{ $teacher->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Função</dt>
                    <dd class="mt-1 text-sm text-gray-800">Professor</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Membro desde</dt>
                    <dd class="mt-1 text-sm text-gray-800">
                        {{ $teacher->created_at?->format('d/m/Y') ?? 'Não informado' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">E-mail verificado</dt>
                    <dd class="mt-1 text-sm text-gray-800">
                        {{ $teacher->email_verified_at ? 'Verificado' : 'Não verificado' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Disciplinas</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $disciplineIds->count() }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Turmas</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $classesCount }}</dd>
                </div>
            </dl>
        </section>
    </div>

    <div x-data x-cloak class="fixed inset-0 z-40 bg-gray-900/50" x-show="$store.tourguide.openMai">
        <div role="dialog" aria-modal="true" aria-labelledby="teacher-profile-tour-title"
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-full sm:w-1/2 bg-white rounded-lg shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">
            <div class="flex items-center justify-between">
                <h2 id="teacher-profile-tour-title" class="text-[14px] font-normal text-gray-700">
                    Tour do perfil
                </h2>
                <x-mary-button icon="o-x-mark" aria-label="Fechar tour do perfil" class="btn btn-sm btn-ghost" x-on:click="$store.tourguide.cancelOptionsTourPage()" />
            </div>
            <div class="p-4 bg-gray-100">
                <div class="flex flex-col gap-3">
                    <p class="text-sm tracking-wide text-gray-600">
                        Conheça as funcionalidades do seu perfil:
                    </p>
                    <x-mary-button label="Editar foto de perfil"
                        x-on:click="$store.tourguide.start('StartTourTeacherProfile')"
                        class="btn-primary btn-sm" />
                </div>
            </div>
            <img src="{{ asset('images/ai-sos.png') }}" alt="" aria-hidden="true" class="fixed right-0 w-64 bottom-4 sm:w-48">
        </div>
    </div>
</div>
