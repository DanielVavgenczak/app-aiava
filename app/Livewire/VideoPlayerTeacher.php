<?php

namespace App\Livewire;

use Illuminate\Support\Facades\URL;
use Livewire\{Component};
use Livewire\Attributes\{On, Reactive};

class VideoPlayerTeacher extends Component
{
    #[Reactive]
    public string $lesson_id;

    /**
     *  Genrerate ur with videoID
     * @return void
     */
    public function initVideo(){
        // As mentioned above, generate url with $videoId
        $url =  URL::temporarySignedRoute(
            'media.stream', now()->addMinutes(30), ['lesson_id' =>  $this->lesson_id]
        );
        // Notify client to set source
        $this->dispatch('init-complete',  url: $url);
    }

    public function boot()
    {
        $this->initVideo();
    }
    public function render()
    {
        return view('livewire.video-player-teacher');
    }
}
