<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use App\Models\Discipline;
use Mary\Traits\Toast;

new #[Layout('layouts.app')] class extends Component {
    use Toast;
    public Discipline $discipline;
    public string $id;
    // module
    public string $moduleTitle = '';
    public bool $modalNewModule = false;

    public function mount(string $id)
    {
        $this->discipline = Discipline::with([
            'modules' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
        ])
            ->where('id', $id)
            ->first();
    }

    public function sendModule()
    {
        $validate = $this->validate([
            'moduleTitle' => 'required|string|min:3',
        ]);
        $module = $this->discipline->modules()->create([
            'title' => $this->moduleTitle,
            'discipline_id' => $this->id,
        ]);
        $this->discipline->load([
            'modules' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
        ]);

        $this->modalNewModule = false;
        $this->success("{$this->moduleTitle} cadastrado com sucesso", timeout: 4000);
        $this->moduleTitle = '';
    }
}; ?>

<div>
    <x-mary-modal wire:model="modalNewModule" class="backdrop-blur">
        <div class="mb-5">Novo modulo para o conteudo <strong>{{ $discipline->title }}</strong></div>
        <div>
            <form action="" wire:submit.prevent="sendModule">
                <div class="my-3">
                    <x-input-label for="title" :value="__('Titulo')" />
                    <x-text-input wire:model="moduleTitle" id="title" class="block w-full mt-1" type="text"
                        placeholder="Ex: Teoria Musical" name="title" required autofocus autocomplete="title" />
                    <x-input-error :messages="$errors->get('moduleTitle')" class="mt-2" />
                </div>
                <div class="flex items-center justify-end gap-4 mt-5">
                    <x-mary-button type="submit" spinner>Cadastrar</x-mary-button>
                    <x-mary-button label="Cancel" @click="$wire.modalNewModule = false" />
                </div>
            </form>
        </div>
    </x-mary-modal>

    <div class="py-6">
        <div class="space-y-2 max-w-7xl sm:px-6 lg:px-8">
            <div>
                <a href="{{ route('teacher.content') }}" wire:navigate>
                    <x-mary-icon name="o-arrow-left" class="w-4" />
                    <span class="text-sm font-semibold text-gray-600">voltar</span>
                </a>
            </div>
            <div class="p-4 bg-white sm:p-8 sm:rounded-lg">
                <div class="flex items-center justify-between mt-4 mb-10">
                    <h2 class="text-lg font-normal text-gray-00">
                        {{ $discipline->title }}
                    </h2>
                    <x-mary-button label="Novo modulo" @click="$wire.modalNewModule = true" icon-right="o-plus" />
                </div>
                <div class="w-1/3">
                    @foreach ($discipline->modules as $module)
                        <div x-data="{ isExpanded: false }" class="bg-gray-200">
                            <button id="controlsAccordionItemOne" type="button"
                                class="flex items-center justify-between w-full gap-4 p-4 text-left bg-neutral-50 underline-offset-2 hover:bg-neutral-50/75 focus-visible:bg-neutral-50/75 focus-visible:underline focus-visible:outline-none dark:bg-neutral-900 dark:hover:bg-neutral-900/75 dark:focus-visible:bg-neutral-900/75"
                                aria-controls="accordionItemOne" @click="isExpanded = ! isExpanded"
                                :class="isExpanded ? 'text-onSurfaceStrong dark:text-onSurfaceDarkStrong font-bold' :
                                    'text-onSurface dark:text-onSurfaceDark font-medium'"
                                :aria-expanded="isExpanded ? 'true' : 'false'">
                                {{ $module->title }}
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                    stroke-width="2" stroke="currentColor" class="transition size-5 shrink-0"
                                    aria-hidden="true" :class="isExpanded ? 'rotate-180' : ''">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>
                            <div x-cloak x-show="isExpanded" id="accordionItemOne" role="region"
                                aria-labelledby="controlsAccordionItemOne" x-collapse>
                                <ul>
                                    <li>
                                        aulas
                                    </li>
                                    <li>
                                        aulas
                                    </li>
                                    <li>
                                        aulas
                                    </li>
                                    <li>
                                        aulas
                                    </li>
                                    <li>
                                        aulas
                                    </li>
                                    <li>
                                        aulas
                                    </li>

                                </ul>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
