<nav class="">
    @auth

        <a href="{{ route('teacher.home') }}" class="px-3 py-2 text-white bg-pink-700 rounded-md">
            Plataforma
        </a>
    @else
        <a href="{{ route('login') }}" class="px-3 py-2 text-gray-700 rounded-md">
            Log in
        </a>

        @if (Route::has('register'))
            <a href="{{ route('register') }}"
                class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white">
                Register
            </a>
        @endif
    @endauth
</nav>
