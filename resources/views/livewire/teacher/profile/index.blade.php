<div class="p-6 bg-gray-50">
    <div class="flex flex-col items-center gap-3">
        <div class="flex justify-between w-full ">
            <div></div>
            @if (Auth::user()->profile?->avatar)
                <img src="{{ asset('storage/' . Auth::user()->profile->avatar) }}" class="w-20 h-20 rounded-full" />
            @else
                <div class="flex flex-col items-center justify-center gap-3">
                    <livewire:teacher.components.avatar initiais="{{ Auth::user()->initialLetterToAvatar() }}">
                        <h3 class="text-base text-gray-600">{{ Auth::user()->fullName() }}</h3>
                </div>
            @endif
            <x-mary-button label="Editar Perfil" icon-right="o-pencil-square"
                @click="$dispatch('open-modal-update-profile', { open:true })" />
            <livewire:teacher.profile.modals.update />
        </div>
    </div>
    <div class="flex flex-col gap-4">
        <div>
            <h3 class="text-lg font-semibold">Informações da conta</h3>
            <div>
                {{ Auth::user()->fullName() }}
            </div>
            <div>
                <h3 class="text-lg font-semibold">E-mail</h3>
                {{ Auth::user()->email }}
            </div>
        </div>
        <div>
            <h3 class="text-lg font-semibold">Informações do plano</h3>
            <div>
                <h4>Plano atual</h4>
            </div>
        </div>
    </div>
</div>
