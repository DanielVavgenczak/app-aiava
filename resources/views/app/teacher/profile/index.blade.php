<x-app-layout>
    <div class="py-12">
        <div class="py-4">
            <a href="{{ route('teacher.index') }}" wire:navigate>
                <x-mary-icon name="o-arrow-left" class="w-4" />
                <span class="text-xs font-semibold text-gray-600 uppercase">voltar</span>
            </a>
        </div>
        <livewire:pages.teacher.profile.index />
    </div>
</x-app-layout>
