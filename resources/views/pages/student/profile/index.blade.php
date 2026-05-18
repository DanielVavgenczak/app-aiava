<?php

use Livewire\Component;
use Livewire\Attributes\{Layout};

new #[Layout('layouts::student')] class extends Component {


}; ?>
<div>
 <div class="py-4">
         <a href="{{ route('student.discipline') }}" wire:navigate>
             <x-mary-icon name="o-arrow-left" class="w-4" />
             <span class="text-xs font-semibold text-gray-600 uppercase tracking-wider">voltar</span>
         </a>
     </div>
<div class="p-5 bg-white">
    <div class="flex flex-col items-center gap-3">
        <div class="flex justify-between w-full ">
            <div></div>
            @if (Auth::guard('student')->user()->profile?->avatar)
                <img src="{{ asset('storage/' . Auth::guard('student')->user()->profile->avatar) }}"
                    class="w-20 h-20 rounded-full" alt="{{ Auth::guard('student')->user()->full_name }}"/>
            @else
                <div class="flex flex-col items-center justify-center gap-3">
                    <livewire:global.user-avatar
                        initiais="{{ Auth::guard('student')->user()->initialLetterToAvatar() }}"/>
                        <h3 class="text-base text-gray-600">{{ Auth::guard('student')->user()->full_name }}</h3>
                </div>
            @endif
            <x-mary-button label="Editar Perfil" icon-right="o-pencil-square"
                x-on:click="$dispatch('open-modal-update-profile', { open:true })" />
            <livewire:student.profile.update />
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
</div>
