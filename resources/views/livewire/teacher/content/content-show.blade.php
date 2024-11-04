<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use App\Models\Discipline;

new #[Layout('layouts.app')] class extends Component {
    public Discipline $discipline;
    public string $id;
    public $item = true;

    // module
    public string $moduleTitle = '';
    public string $group = 'group0';
    public bool $modalModule = false;
    public array $groups = [
        'Historia da musica' => ['aula 01', 'aula 02'],
        'Teorial Musical' => ['aula 01', 'aula 02'],
        'Arranjo' => ['aula 01', 'aula 02'],
    ];
    public function mount(string $id)
    {
        $this->discipline = Discipline::with('modules')->where('id', $id)->first();
        //dd($this->discipline->modules());
    }
}; ?>

<div>
    {{-- <x-mary-modal wire:model="modalModule" class="backdrop-blur">
        <div class="mb-5">Press `ESC`, click outside or click `CANCEL` to close.</div>
        <div>
            <form action="">
                <x-mary-input label="Name" placeholder="Titulo do modulo" icon="o-user" hint="Your full name" />
                <div>
                    <x-mary-button>Registrar</x-mary-button>
                </div>
            </form>
        </div>
        <x-mary-button label="Cancel" @click="$wire.modalModule = false" />
    </x-mary-modal> --}}
    <div class="">
        <a href="{{ route('teacher.content') }}">
            <x-mary-icon name="o-arrow-left" class="w-4" />
            <span class="text-sm font-semibold text-gray-600">voltar</span>
        </a>
        <div class="flex items-center justify-between">
            <h3> {{ $discipline->title }}</h3>
            <x-mary-button label="Adicionar modulo" @click="$wire.modalModule = true" icon-right="o-plus"
                class="text-white bg-pink-700" />
        </div>
    </div>
    <div class="flex flex-col w-full">
        @foreach ($discipline->modules as $module)
            <div x-data="{ isExpanded: false }" class="w-1/4 bg-red-200">
                <x-mary-button label="{{ $module->title }}" id="controlsAccordionItemOne" type="button"
                    icon-right="o-chevron-down" @click="isExpanded = !isExpanded" />
                <div x-cloak x-show="isExpanded" id="accordionItemOne" role="region"
                    aria-labelledby="controlsAccordionItemOne" x-collapse>
                    <ul>
                        <li>
                            aulas
                        </li>
                    </ul>
                </div>
            </div>
        @endforeach

        <div>
            videos
        </div>
    </div>
</div>
