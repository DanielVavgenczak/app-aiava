<x-app-layout>
    <div class="py-4">
        <a href="{{ route('teacher.discipline') }}" wire:navigate>
            <x-mary-icon name="o-arrow-left" class="w-4" />
            <span class="text-xs font-semibold text-gray-600 uppercase">voltar</span>
        </a>
    </div>
    <div class="p-5 bg-white">
        <div class="mb-5">
            <h2 class="text-lg font-semibold text-gray-600">Escolhar uma disciplina</h2>
        </div>
        <div>
            <livewire:pages.teacher.discipline.index lazy />
        </div>
    </div>
</x-app-layout>
