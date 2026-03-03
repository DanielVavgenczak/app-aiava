<nav class="">
    @auth

        <a href="{{ route('teacher.home') }}" class="px-3 py-2 text-white bg-pink-700 rounded-md">
            Plataforma
        </a>
    @else
        <a href="{{ route('login') }}" class="btn bg-pink-600 border-0  px-3 py-2 text-gray-50 rounded-md">
            Logar
        </a>

        @if (Route::has('register'))
            <a href="{{ route('register') }}"
                class="btn rounded-md px-3 py-2 text-gray-50 ring-1 bg-blue-800 border-0 transition  focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white">
                Registrar-se
            </a>
        @endif
    @endauth
</nav>
