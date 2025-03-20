<x-app-layout>
    <div class="py-6 ">
        <div class="space-y-2 max-w-7xl sm:px-6 lg:px-8">
            <div class="flex flex-col flex-1 p-4 bg-white dark:bg-slate-900 bg-back sm:p-8 sm:rounded-lg">
                <div class="mt-4 mb-10">
                    <h2 class="text-lg font-semibold text-gray-700">Escolha uma disciplina</h2>
                </div>
                <livewire:teacher.discipline.index lazy />
            </div>
        </div>
    </div>
</x-app-layout>
