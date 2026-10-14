<?php

use Livewire\Component;
use Livewire\Attributes\{Layout};

new #[Layout('layouts::student')] class extends Component {

}; ?>
<div>
    <livewire:global.breadcrumb :items="[
        ['label' => 'Disciplinas', 'route' => 'student.discipline', 'icon' => 'o-academic-cap'],
        ['label' => 'Perfil'],
    ]" color="pink" />
    <div class="p-5 bg-white">
        <div class="flex flex-col items-center gap-3">
            <div class="flex justify-between w-full" id="student-profile-section">
                <div></div>
                <div id="student-profile-avatar">
                    @if (Auth::guard('student')->user()->hasAvatar())
                        <img src="{{ Auth::guard('student')->user()->avatar_url }}"
                            class="w-20 h-20 rounded-full object-cover" alt="{{ Auth::guard('student')->user()->full_name }}"/>
                    @else
                        <div class="flex flex-col items-center justify-center gap-3">
                            <livewire:global.user-avatar
                                initiais="{{ Auth::guard('student')->user()->initialLetterToAvatar() }}"/>
                            <h3 class="text-base text-gray-600">{{ Auth::guard('student')->user()->full_name }}</h3>
                        </div>
                    @endif
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
        <div class="flex flex-col gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-700">Informações da conta</h3>
                <div>
                    {{ Auth::guard('student')->user()->full_name }}
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-700">E-mail</h3>
                    {{ Auth::guard('student')->user()->email }}
                </div>
            </div>
        </div>
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
