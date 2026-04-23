<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts::student')] class extends Component {
    //
}; ?>

<div class="p-5 bg-white rounded-lg shadow">
    <div class="flex items-center gap-4 mb-6" id="student-welcome-{{ Auth::guard('student')->user()->id }}">
        <div class="w-16 h-16 bg-pink-600 rounded-full flex items-center justify-center text-white text-2xl font-bold" id="student-avatar-{{ Auth::guard('student')->user()->id }}">
            {{ strtoupper(substr(Auth::guard('student')->user()->full_name, 0, 1)) }}
        </div>
        <div>
            <h1 class="text-2xl font-bold text-gray-800" id="student-name-{{ Auth::guard('student')->user()->id }}">
                Bem-vindo, {{ Auth::guard('student')->user()->full_name }}!
            </h1>
            <p class="text-gray-500">É bom ter você aqui</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4" id="student-quick-actions-{{ Auth::guard('student')->user()->id }}">
        <a href="{{ route('student.discipline') }}" wire:navigate 
           class="p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors"
           id="btn-my-disciplines-{{ Auth::guard('student')->user()->id }}">
            <x-mary-icon name="o-academic-cap" class="w-8 h-8 text-blue-600" />
            <p class="mt-2 font-semibold text-gray-700">Minhas Disciplinas</p>
            <p class="text-sm text-gray-500">Acesse seus cursos</p>
        </a>
        
        <a href="{{ route('student.forum.index') }}" wire:navigate 
           class="p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors"
           id="btn-forum-{{ Auth::guard('student')->user()->id }}">
            <x-mary-icon name="o-chat-bubble-left-right" class="w-8 h-8 text-green-600" />
            <p class="mt-2 font-semibold text-gray-700">Fórum</p>
            <p class="text-sm text-gray-500">Participe das discussões</p>
        </a>
        
        <a href="{{ route('student.profile') }}" wire:navigate 
           class="p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors"
           id="btn-profile-{{ Auth::guard('student')->user()->id }}">
            <x-mary-icon name="o-user-circle" class="w-8 h-8 text-purple-600" />
            <p class="mt-2 font-semibold text-gray-700">Meu Perfil</p>
            <p class="text-sm text-gray-500">Gerencie seus dados</p>
        </a>
    </div>
</div>
</div>
