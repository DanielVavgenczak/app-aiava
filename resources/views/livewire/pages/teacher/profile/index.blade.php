<?php

use Illuminate\View\View;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout};

new #[Layout('layouts.app')] class extends Component {
   

}; ?>
<div>
 <div class="py-4">
         <a href="{{ route('teacher.discipline') }}" wire:navigate>
             <x-mary-icon name="o-arrow-left" class="w-4" />
             <span class="text-xs font-semibold text-gray-600 uppercase tracking-wider">voltar</span>
         </a>
     </div>
<div class="p-5 bg-white">
    <div class="flex flex-col items-center gap-3">
        <div class="flex justify-between w-full ">
            <div></div>
            @if (Auth::guard('teacher')->user()->profile?->avatar)
                <img src="{{ asset('storage/' . Auth::guard('teacher')->user()->profile->avatar) }}"
                    class="w-20 h-20 rounded-full" />
            @else
                <div class="flex flex-col items-center justify-center gap-3">
                    <livewire:pages.teacher.components.avatar
                        initiais="{{ Auth::guard('teacher')->user()->initialLetterToAvatar() }}">
                        <h3 class="text-base text-gray-600">{{ Auth::guard('teacher')->user()->fullName() }}</h3>
                </div>
            @endif
            <x-mary-button label="Editar Perfil" icon-right="o-pencil-square"
                x-on:click="$dispatch('open-modal-update-profile', { open:true })" />
            <livewire:pages.teacher.profile.modals.update />
        </div>
    </div>
    <div class="flex flex-col gap-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-700">Informações da conta</h3>
            <div>
                {{ Auth::guard('teacher')->user()->fullName() }}
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-700">E-mail</h3>
                {{ Auth::guard('teacher')->user()->email }}
            </div>
        </div>
        <div>
            <h3 class="text-lg font-semibold">Informações do plano</h3>
            <div>
                <h4>Plano atual</h4>
                <p>Informações do plano</p>
            </div>
        </div>
    </div>
</div>
</div>
