<div>
    Home
    <div x-data class="fixed inset-0 z-40 bg-black bg-opacity-50" x-show="$store.tourguide.openMai">
        <!-- Centralizando a div -->
        <div
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-full sm:w-1/2 bg-white rounded-lg shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">
            <x-mary-button icon="o-x-mark" class="btn-square" @click="$store.tourguide.cancelOptionsTourPage()" />
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-normal text-gray-700">Opções da home
                </h2>
            </div>
            <div class="grid grid-cols-4 gap-3 p-4 bg-gray-100">


            </div>
        </div>
        <img src="{{ asset('images/ai-sos.png') }}" class="fixed right-0 w-64 bottom-4 sm:w-48">
    </div>
</div>
