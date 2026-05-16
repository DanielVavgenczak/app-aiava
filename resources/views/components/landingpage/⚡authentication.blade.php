<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<nav class="">
    @if (Auth::guard('teacher')->check())
        {{-- Professor logado --}}
        <a href="{{ route('teacher.discipline') }}" class="px-3 py-2 text-white bg-pink-700 rounded-md">
            Painel do Professor
        </a>
        <a href="{{ route('logout') }}" class="px-3 py-2 text-gray-600 hover:text-gray-900"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            Sair
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
            @csrf
        </form>

    @elseif (Auth::guard('student')->check())
        {{-- Aluno logado --}}
        <a href="{{ route('student.discipline') }}" class="px-3 py-2 text-white bg-pink-700 rounded-md">
            Painel do Aluno
        </a>
        <a href="{{ route('logout') }}" class="px-3 py-2 text-gray-600 hover:text-gray-900"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            Sair
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
            @csrf
        </form>

    @else
        {{-- Visitante (não logado) --}}
        <a href="{{ route('login') }}" class="btn bg-blue-400 border-0 px-3 py-2 text-gray-50 rounded-md">
            Logar
        </a>

        @if (Route::has('register'))
            <a href="{{ route('register') }}"
                class="btn rounded-md px-3 py-2 text-gray-50 ring-1 bg-blue-400 border-0 transition focus:outline-none focus-visible:ring-[#FF2D20]">
                Registrar-se
            </a>
        @endif
    @endif
</nav>
