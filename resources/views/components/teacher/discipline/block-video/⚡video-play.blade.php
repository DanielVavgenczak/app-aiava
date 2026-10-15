<?php

use Livewire\Component;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\{On, Reactive};

new class extends Component {
    #[Reactive]
    public string $lesson_id = '';
    /**
     *  Genrerate ur with videoID
     * @return void
     */
    public function initVideo()
    {
        // As mentioned above, generate url with $videoId
        $url = URL::temporarySignedRoute('teacher.media.stream', now()->addMinutes(30), ['lesson_id' => $this->lesson_id]);
        // Notify client to set source
        $this->dispatch('init-complete', url: $url);
    }

    public function boot()
    {
        $this->initVideo();
    }
}; ?>
<div wire:init="initVideo" id="video-player-{{ $lesson_id ?? '' }}" class="relative w-full aspect-video overflow-hidden bg-black rounded-lg">
    <video wire:ignore id="videoPlayer-{{ $lesson_id ?? '' }}" class="w-full h-full object-contain" controls playsinline preload="metadata"></video>
</div>
@script
    <script>
        $wire.on('init-complete', event => {
            const video = document.getElementById("videoPlayer-{{ $lesson_id }}");
            if (!video) return;
            video.src = event.url;
        });
    </script>
@endscript
