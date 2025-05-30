<x-app-layout>
    <div class="py-4">
        <div class="py-4">
            <a href="{{ route('teacher.home') }}" wire:navigate>
                <x-mary-icon name="o-arrow-left" class="w-4" />
                <span class="text-xs font-semibold text-gray-600 uppercase">voltar</span>
            </a>
        </div>
        <div class="bg-white p-5">
            <livewire:pages.teacher.forum.index />
        </div>
    </div>
</x-app-layout>
