<div wire:init="initVideo">
    <video wire:ignore id="videoPlayer" controls width="800px" height="900px" />
</div>
@script
    <script>
        $wire.on('init-complete', event => {
            console.log('URL recebida:', event.url);
            let video = document.getElementById("videoPlayer");
            video.src = event.url;
        });
    </script>
@endscript
