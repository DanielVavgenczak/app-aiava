<div wire:init="initVideo">
    <video wire:ignore id="videoPlayer" controls width="800px" height="900px" />
</div>
@scriptmanuel
    <script>
        $wire.on('init-complete', event => {
            let video = document.getElementById("videoPlayer");
            video.src = event.url;
        });
    </script>
@endscript
