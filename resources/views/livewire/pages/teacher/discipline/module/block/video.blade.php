<?php

use Livewire\Volt\Component;
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
        $url = URL::temporarySignedRoute('media.stream', now()->addMinutes(30), ['lesson_id' => $this->lesson_id]);
        // Notify client to set source
        $this->dispatch('init-complete', url: $url);
    }

    public function boot()
    {
        $this->initVideo();
    }
}; ?>
<div wire:init="initVideo" class="relative w-full overflow-hidden bg-black rounded-lg">
    <video wire:ignore id="videoPlayer" controls width="800px" height="900px"></video>
</div>
@script
    <script>
        $wire.on('init-complete', event => {
            let video = document.getElementById("videoPlayer");
            video.src = event.url;
        });
    </script>
@endscript
