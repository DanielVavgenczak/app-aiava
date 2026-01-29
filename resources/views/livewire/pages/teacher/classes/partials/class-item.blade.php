<?php

use Livewire\Volt\Component;
use App\Models\Teacher\Classe;
use Illuminate\Support\Collection;
new class extends Component {
    public Classe $classe;
}; ?>

<div class="p-5 hover:bg-gray-100 flex justify-between">
    <div class="flex items-center gap-4">
        {{-- <x-mary-icon name="o-academic-cap" class="w-4 h-4 text-pink-700" /> --}}
        <div class="flex flex-col gap-2">
            <span class="text-gray-700 font-normal tracking-wider text-[15px]">{{ $classe->name }}</span>
            <span
                class="text-gray-700 font-normal tracking-wider text-[15px]">Alunos:{{ $classe->students->count() }}</span>
        </div>
    </div>
    <div class="flex justify-items-start gap-4">
        {{ $classe->discipline->title }}
    </div>
    <div id="actions" class="flex gap-4">
        <a href="{{ route('teacher.classes.show', $classe) }}" class="btn btn-sm btn-square btn-ghost capitalize">
            <x-mary-icon name="o-arrow-uturn-right" class="w-3 h-3" />
            acessar
        </a>
        <button class="btn btn-sm btn-square btn-ghost">
            <x-mary-icon name="o-pencil" class="w-3 h-3" />
            Editar
        </button>
        <button class="btn btn-sm btn-square btn-ghost">
            <x-mary-icon name="o-trash" class="w-5 h-5" />
            Deletar
        </button>
    </div>
</div>
