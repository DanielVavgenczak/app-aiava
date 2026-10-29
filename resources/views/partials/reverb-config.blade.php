<script>
    window.__REVERB_CONFIG__ = {!! \Illuminate\Support\Js::from([
        'key' => config('reverb.apps.apps.0.key'),
        'host' => config('reverb.apps.apps.0.options.host'),
        'port' => config('reverb.apps.apps.0.options.port'),
        'scheme' => config('reverb.apps.apps.0.options.scheme'),
    ]) !!};
</script>
