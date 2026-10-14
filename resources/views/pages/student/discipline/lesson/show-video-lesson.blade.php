<?php

use Livewire\Component;
use Livewire\Attributes\{On, Computed};
use App\Models\Lesson;
use Illuminate\Support\Facades\URL;

new class extends Component {
    public Lesson $lesson;
    public $show = false;
    public bool $hasPrevious = false;
    public bool $hasNext = false;
    public $showEndMessage = false;
    public $video_url = ''; // ✅ Muda de computed pra propriedade normal

    #[On('stundent-lesson-streaming-block')]
    public function streamingLesson($lesson_id)
    {
        $student = auth()->guard('student')->user();

        $lesson = Lesson::where('id', $lesson_id)
            ->whereHas('module', function ($query) use ($student) {
                $query->where('discipline_id', $student?->discipline_id);
            })
            ->first();

        if (!$lesson) {
            return;
        }

        $this->lesson = $lesson;
        $this->show = true;
        $this->updateNavigationStates();
        $this->showEndMessage = false;

        // ✅ Inicia vídeo imediatamente após carregar a lesson
        $this->initVideo();
    }

    public function navigate($direction)
    {
        if (!$this->lesson) return;

        $operator = $direction === 'next' ? '>' : '<';
        $order = $direction === 'next' ? 'asc' : 'desc';

        $adjacentLesson = Lesson::where('module_id', $this->lesson->module_id)
            ->where('created_at', $operator, $this->lesson->created_at)
            ->orderBy('created_at', $order)
            ->first();

        if ($adjacentLesson) {
            $this->streamingLesson($adjacentLesson->id);
            $this->dispatch('update-student-lesson-highlight', lesson_id: $adjacentLesson->id);
        } else {
            if ($direction === 'next') {
                $this->showEndMessage = true;
            }
        }
    }

    protected function updateNavigationStates()
    {
        if (!$this->lesson) {
            $this->hasPrevious = false;
            $this->hasNext = false;
            return;
        }

        $this->hasPrevious = Lesson::where('module_id', $this->lesson->module_id)
            ->where('created_at', '<', $this->lesson->created_at)
            ->exists();

        $this->hasNext = Lesson::where('module_id', $this->lesson->module_id)
            ->where('created_at', '>', $this->lesson->created_at)
            ->exists();
    }

    public function initVideo()
    {
        if (!empty($this->lesson->path_file)) {
            $this->video_url = URL::temporarySignedRoute('media.stream', now()->addMinutes(30), ['lesson_id' => $this->lesson->id]);

            // ✅ Dispara o evento com o nome correto
            $this->dispatch('video-load', url: $this->video_url);
        }
    }

    // ✅ Remove o boot() e coloca a lógica no lugar certo
};
?>

<div class="bg-white rounded-xl shadow-md overflow-hidden">
    @if (!empty($lesson))
        <div class="relative pt-[56.25%] bg-gray-900">
            <div
                wire:loading
                wire:target="initVideo"
                class="absolute inset-0 flex items-center justify-center bg-gray-800">
                <div class="animate-pulse flex flex-col items-center">
                    <x-mary-loading />
                    <span class="text-gray-400">Carregando vídeo...</span>
                </div>
            </div>

            <div class="absolute inset-0">
                <video
                    wire:ignore
                    id="videoPlayer"
                    class="w-full h-full object-contain"
                    controls
                    playsinline
                    preload="metadata">
                    <source src="{{ $video_url }}" type="video/mp4">
                        Seu navegador não suporta vídeos HTML5.
                </video>


            </div>
        </div>

        @if($showEndMessage)
        <div class="mx-5 mt-3 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center gap-3">
                <x-mary-icon name="o-trophy" class="w-5 h-5 text-green-600 flex-shrink-0" />
                <div class="flex-1">
                    <p class="text-green-800 font-medium text-sm">Você concluiu esta aula!</p>
                    <p class="text-green-600 text-xs mt-1">Todos os blocos foram assistidos nessa aula.</p>
                </div>
                <button wire:click="$set('showEndMessage', false)" class="text-green-600 hover:text-green-800 flex-shrink-0">
                    <x-mary-icon name="o-x-mark" class="w-4 h-4" />
                </button>
            </div>
        </div>
        @endif

        <div class="w-full p-5 flex justify-between items-center">
            @if($hasPrevious)
                <button type="button" class="btn btn-sm text-gray-600" wire:click="navigate('previous')">
                    <x-mary-icon name="o-chevron-left" /> Anterior
                </button>
            @else
                <button type="button" class="btn btn-sm text-gray-600 cursor-not-allowed" disabled>
                    <x-mary-icon name="o-chevron-left" /> Anterior
                </button>
            @endif

            @if($hasNext)
                <button type="button" class="btn btn-sm text-gray-600" wire:click="navigate('next')">
                    Próximo <x-mary-icon name="o-chevron-right" />
                </button>
            @else
                <div class="px-4 py-2 text-gray-500 text-sm bg-gray-100 rounded-lg">
                    Última aula ✓
                </div>
            @endif
        </div>

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
            </div>

            <p class="mt-3 text-gray-600">{{ $lesson->description }}</p>

            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        @if ($lesson->teacher->hasAvatar())
                            <img src="{{ $lesson->teacher->avatar_url }}"
                                class="transition-all duration-300 ease-in-out border-2 border-gray-200 rounded-full dark:border-slate-700"
                                width="80" height="80" style="width: 80px; height: 80px; object-fit:cover;" loading="lazy" />
                        @else
                            <livewire:global.user-avatar
                                initiais="{{ $lesson->teacher->initialLetterToAvatar() }}" class="w-20 h-20" />
                        @endif
                    </div>
                    <div class="flex flex-col gap-1">
                        <h4 class="text-sm font-medium text-gray-500">Professor</h4>
                        <p class="text-sm text-gray-800 font-medium">
                            {{ $lesson->teacher->fullName() }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="p-8 text-center w-full bg-gray-100 h-full">
            <x-mary-icon name="o-video-camera" class="w-8 h-8 text-gray-600" />
            <h3 class="mt-2 tracking-wide text-sm font-medium text-gray-900">Nenhuma aula selecionada</h3>
            <p class="mt-1 text-sm text-gray-500 tracking-wide">Escolha uma aula da lista para começar</p>
        </div>
    @endif
</div>

@script
<script>
    // ✅ SÓ UM SCRIPT, SIMPLES E LIMPO
    document.addEventListener('livewire:initialized', () => {
        const player = document.getElementById('videoPlayer');

        if (player) {
            // ✅ Escuta o evento correto
            Livewire.on('video-load', (event) => {
                console.log('Video loading:', event.url);
                player.src = event.url;

                // Tenta dar play automaticamente
                player.play().catch(e => {
                    console.log('Autoplay prevented:', e);
                });
            });
        }
    });

</script>
@endscript
