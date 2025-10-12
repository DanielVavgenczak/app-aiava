<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{On, Computed};
use App\Models\Lesson;
new class extends Component {
    public Lesson $lesson;
    public $show = false;

    #[Computed]
    public $video_url = '';

    #[On('stundent-lesson-streaming-block')]
    public function streamingLesson($lesson_id)
    {
        $this->lesson = Lesson::where('id', $lesson_id)->first();
        $this->show = true;
    }

    public function initVideo()
    {
        if (!empty($this->lesson->path_file)) {
            $this->video_url = URL::temporarySignedRoute('media.stream', now()->addMinutes(30), ['lesson_id' => $this->lesson->id]);
            $this->dispatch('video-load', url: $this->video_url);
        }
    }
    public function boot()
    {
        $this->initVideo();
    }
}; ?>

<div class="bg-white rounded-xl shadow-md overflow-hidden">
    @if (!empty($lesson))
        <!-- Container do Player (Aspect Ratio 16:9) -->
        <div class="relative pt-[56.25%] bg-gray-900">
            <!-- Placeholder enquanto o vídeo carrega -->
            <div wire:loading wire:target="initVideo"
                class="absolute inset-0 flex items-center justify-center bg-gray-800">
                <div class="animate-pulse flex flex-col items-center">
                    <x-mary-loading />
                    <span class="text-gray-400">Carregando vídeo...</span>
                </div>
            </div>

            <!-- Player de Vídeo -->
            <div wire:init="initVideo" class="absolute inset-0">
                <video wire:ignore id="videoPlayer" class="w-full h-full object-contain" controls playsinline
                    preload="metadata">
                    <source src="{{ $video_url }}" type="video/mp4">
                    Seu navegador não suporta vídeos HTML5.
                </video>
            </div>
        </div>

        <!-- Informações da Aula -->
        <div class="p-4 border-t border-gray-100">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">{{ $lesson->title }}</h2>
                    @if ($lesson->duration)
                        <span class="text-sm text-gray-500 mt-1 inline-flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ gmdate('i:s', $lesson->duration) }} minutos
                        </span>
                    @endif
                </div>
                <div class="flex space-x-2">
                    <button class="p-2 text-gray-500 hover:text-pink-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                    </button>
                    <button class="p-2 text-gray-500 hover:text-pink-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                        </svg>
                    </button>
                </div>
            </div>

            <p class="mt-3 text-gray-600">{{ $lesson->description }}</p>

            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <img class="h-8 w-8 rounded-full"
                            src="{{ asset('storage/' . $lesson->teacher->profile->avatar ?? '') ?? asset('images/no-avatar.png') }}"
                            alt="">
                    </div>
                    <div class="flex flex-col gap-1">
                        <h4>Professor</h4>
                        <p class="text-sm text-gray-500">
                            {{ $lesson->teacher->fullName() }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Estado vazio -->
        <div class="p-8 text-center w-full bg-gray-100 h-full">
            <x-mary-icon name="o-video-camera" class="w-8 h-8 text-gray-600" />
            <h3 class="mt-2 tracking-wide text-sm font-medium text-gray-900">Nenhuma aula selecionada</h3>
            <p class="mt-1 text-sm text-gray-500 tracking-wide">Escolha uma aula da lista para começar</p>
        </div>
    @endif
</div>

@script
    <script>
        document.addEventListener('livewire:initialized', () => {
            const player = document.getElementById('videoPlayer');

            // Inicializa o player quando o componente estiver pronto
            Livewire.on('video-ready', (url) => {
                if (player) {
                    player.src = url;
                    // Para melhor UX, você pode adicionar mais controles aqui
                    player.addEventListener('loadedmetadata', () => {
                        player.play().catch(e => console.log('Auto-play prevented:', e));
                    });
                }
            });
        });
    </script>
@endscript
</div>
@script
    <script>
        $wire.on('video-load', event => {
            let video = document.getElementById("videoPlayer");
            video.src = event.url;
        });
    </script>
@endscript
