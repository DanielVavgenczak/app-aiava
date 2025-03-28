    <aside class="fixed left-0 z-30 w-1/6 min-h-screen px-3 py-5 bg-white top-20 dark:bg-slate-900">
        <div class="flex flex-col items-center gap-3">
            @if (!Auth::user()->profile?->avatar)
                <livewire:teacher.components.avatar initiais="{{ Auth::user()->initialLetterToAvatar() }}">
                @else
                    <img src="{{ asset('storage/' . Auth::user()->profile->avatar) }}" class="w-20 h-20 rounded-full" />
            @endif
            <h4 class="font-semibold text-gray-800 capitalize">{{ Auth::user()->first_name }}</h4>
        </div>
        <div class="px-2 py-3">
            <ul>
                <li class="flex gap-4 py-4">
                    <x-mary-icon name="o-home" />
                    <a class="capitalize" wire.navigate href="{{ route('teacher.home') }}">home</a>
                </li>
                <li class="flex gap-4 py-4">
                    <x-mary-icon name="o-academic-cap" outiline />
                    <a class="capitalize" wire.navigate href="{{ route('teacher.discipline') }}">
                        conteudos
                    </a>
                </li>
                <li class="flex gap-4 py-4">
                    <x-mary-icon name="o-envelope-open" outiline />
                    <a class="capitalize" href="#">E-mails</a>
                </li>
                <li class="flex gap-4 py-4">
                    <x-mary-icon name="o-users" outiline />
                    <a class="capitalize" href="#">turmas</a>
                </li>
                <li class="flex gap-4 py-4">
                    <x-mary-icon name="o-chat-bubble-left-right" outiline />
                    <a class="capitalize" href="#">forum</a>
                </li>
            </ul>
        </div>
        <div class="px-2 py-3 relative top-5 border-t-[2px] w-full h-auto bottom-0 flex justify-center items-end">
            <p class="text-sm text-center text-gray-600">By Sos Solutions</p>
        </div>
    </aside>
