<div class="">
    <div class="py-4"></div>
    <div class="p-5">
        <div class="w-full">
            <p>Em breve uma pagina home para o professor, com uma agenda personalizada</p>
        </div>
        <!-- tour home -->
        <div x-data class="fixed inset-0 z-40 bg-gray-900/50" x-show="$store.tourguide.openMai">
            <!-- Centralizando a div -->
            <div
                class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-full sm:w-1/2 bg-white rounded-lg shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">
                <!--TODO: create header to all tours page --> 
                
                <div class="flex items-center justify-between">
                  <h2 class="text-[14px] font-normal text-gray-700">
                    Opções da home
                  </h2>
                  <x-mary-button icon="o-x-mark" class="btn btn-sm btn-ghost" x-on:click="$store.tourguide.cancelOptionsTourPage()" />
                </div>
                <!-- dont have button to actions -->
                <div class="p-4 bg-gray-100">
                  <p class="text-sm tracking-wide text-gray-600">
                    Em breve
                  </p>
                  </div>
            </div>
            <img src="{{ asset('images/ai-sos.png') }}" class="fixed right-0 w-64 bottom-4 sm:w-48">
        </div>
    </div>
</div>
