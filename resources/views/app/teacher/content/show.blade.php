<x-app-layout>
    <div class="py-6">
        <div class="space-y-2 max-w-7xl sm:px-6 lg:px-8">
            <div>
                <a href="{{ route('teacher.content') }}">
                    <x-mary-icon name="o-arrow-left" class="w-4" />
                    <span class="text-sm font-semibold text-gray-600">voltar</span>
                </a>
            </div>
            <div class="p-4 bg-white sm:p-8 sm:rounded-lg">
                <div class="mt-4 mb-10">
                    <h2 class="text-xl font-semibold text-gray-800">
                        Contentudo detalhes aqui
                    </h2>
                </div>
                <div class="">
                    <livewire:teacher.content.content-show />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
