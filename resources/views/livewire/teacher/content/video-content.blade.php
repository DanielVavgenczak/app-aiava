<?php

use Livewire\Volt\Component;
use App\Models\{Lesson};
use Livewire\Attributes\On;

new class extends Component {
    public ?Lesson $lesson = null;
    public $selectedTab = 'infos-tab';
    public bool $sureDeleteBlock = false;

    #[On('load-video-lesson')]
    public function loadVideoLesson($lesson_id)
    {
        $this->lesson = Lesson::find($lesson_id);
    }
}; ?>

<div class="flex flex-col w-full gap-5">
    <x-mary-modal wire:model="sureDeleteBlock" class="backdrop-blur">
        <div class="flex flex-col items-center w-full gap-6 mb-5 emjustify-center">
            <h3 class="text-lg text-red-700">Tem certeza que deseja deletar o bloco?</h3>
        </div>
        <x-mary-button label="Cancel" @click="$wire.sureDeleteBlock = false" />
        <x-mary-button label="Sim" class="btn-error" />
    </x-mary-modal>
    @if ($lesson)
        <div class="flex justify-end w-full gap-3 px-3 py-3">
            <x-mary-button label="Deletar" icon-right="o-trash" @click="$wire.sureDeleteBlock = true"
                class="bg-red-200" />
            <x-mary-button label="Atualizar" icon-right="o-bars-arrow-down" class="bg-yellow-200" />
            <x-mary-button label="Editar" icon-right="o-plus" class="bg-blue-200" />
        </div>
        <div>
            <livewire:video-player-teacher lesson_id="{{ $lesson->id }}" />
            <div class="flex items-center justify-between p-5">
                <x-mary-button label="Anterior" icon="o-chevron-left" />
                <x-mary-button label="Proximo" icon-right="o-chevron-right" />
            </div>
        </div>
        <div class="p-5 bg-white">
            <x-mary-tabs wire:model="selectedTab">
                <x-mary-tab name="infos-tab" label="Informaçoes">
                    <div>
                        <h3>Informações do video</h3>
                        <ul>
                            <li class="px-2 py-3"><span class="text-sm font-semibold text-gray-700">Title:</span>
                                {{ $lesson->title }}
                            </li>
                            <li class="px-2 py-2"><span class="text-sm font-semibold text-gray-700">Hash Name:</span>
                                {{ $lesson->hash_name }}</li>
                            <li class="px-2 py-3"><span class="text-sm font-semibold text-gray-700">Original
                                    Name:</span>
                                {{ $lesson->original_name }}</li>
                            <li class="px-2 py-3"><span
                                    class="text-sm font-semibold text-gray-700">Description</span>{{ $lesson->description }}
                            </li>
                        </ul>
                    </div>
                </x-mary-tab>
                <x-mary-tab name="question-tab" label="Duvidas">
                    <div>Duvidas</div>
                </x-mary-tab>
                <x-mary-tab name="material-tab" label="Materiais de apoio">
                    <div>Materiais de apoio</div>
                </x-mary-tab>
            </x-mary-tabs>

        </div>
    @endif
</div>
