<?php

use Livewire\Volt\Component;
use App\Models\{Lesson};
use Livewire\Attributes\On;

new class extends Component {
    public Lesson $lesson;
    public $selectedTab = 'infos-tab';
    public bool $sureDeleteBlock = false;
    public string $video_url = '';
    public function mount(Lesson $lesson)
    {
        $this->lesson = $lesson;
        //dd('oi jo', $lesson);
        $this->loadBlock();
    }
    protected function loadBlock()
    {
        $this->video_url = route('media.stream', ['lesson_id' => $this->lesson->id]);
        $this->dispatch('vide-load', url: $this->video_url);
    }
    //#[On('next-video-content')]
    public function nextVideo()
    {
        $this->dispatch('next-video-content', id: $this->lesson->id);
    }
    #[On('load-video-lesson')]
    public function loadVideoLesson($lesson_id)
    {
        $this->lesson = Lesson::findOrFail($lesson_id);
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
        <!-- Player de vídeo -->
        <div>
            <livewire:pages.teacher.discipline.module.block.video :lesson_id="$lesson->id" />
        </div>
        <div class="flex items-center justify-between p-5">
            <x-mary-button label="Anterior" icon="o-chevron-left"
                @click="$dispatch('previous-video-content', { id: '{{ $lesson->id }}' })" />
            <x-mary-button label="Proximo" icon-right="o-chevron-right" @click="$wire.nextVideo()" />
        </div>
        <div class="p-5 bg-white">
            <x-mary-tabs wire:model="selectedTab">
                <x-mary-tab name="material-tab" label="Materiais de apoio">
                    <div>Materiais de apoio</div>
                </x-mary-tab>
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
            </x-mary-tabs>
        </div>
    @endif
</div>

@script
    <script>
        // Inicializa o player quando o componente é montado
        document.addEventListener('livewire:initialized', () => {
            console.log('livewire inciado de novo')
            const player = document.getElementById('videoPlayer-{{ $lesson->id }}');
            player.src = '{{ $video_url }}';
        });

        // Atualiza o player quando o vídeo muda
        Livewire.on('video-loaded', (url) => {
            const player = document.getElementById('videoPlayer-{{ $lesson->id }}');
            player.src = url;
            player.load();
            player.play().catch(e => console.log('Autoplay prevented:', e));
        });
    </script>
@endscript
