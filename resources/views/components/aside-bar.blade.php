    <aside class="w-1/6 min-h-screen px-3 py-5 bg-white dark:bg-slate-900">
        <div class="flex flex-col items-center gap-3">
            <img src="https://i.pravatar.cc/150?u=a042581f4e29026704d" class="w-20 h-20 rounded-full" />
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
                    <a class="capitalize" wire.navigate href="{{ route('teacher.content') }}">
                        conteudos
                    </a>
                </li>
                {{-- <li class="flex gap-4 py-4">
                    <x-mary-icon name="o-chat-bubble-left-ellipsis" outiline />
                    <a class="capitalize" href="#">mensagens</a>
                </li>
                <li class="flex gap-4 py-4">
                    <x-mary-icon name="o-users" outiline />
                    <a class="capitalize" href="#">turmas</>
                </li>
                <li class="flex gap-4 py-4">
                    <x-mary-icon name="o-chat-bubble-left-right" outiline />
                    <a class="capitalize" href="#">turmas</>
                </li> --}}
            </ul>
        </div>
    </aside>
