import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const runtimeConfig = window.__REVERB_CONFIG__ ?? {};
const scheme = runtimeConfig.scheme
    ?? import.meta.env.VITE_REVERB_SCHEME
    ?? (window.location.protocol === 'https:' ? 'https' : 'http');
const host = runtimeConfig.host
    ?? import.meta.env.VITE_REVERB_HOST
    ?? window.location.hostname;
const port = runtimeConfig.port
    ?? import.meta.env.VITE_REVERB_PORT
    ?? (scheme === 'https' ? 443 : 8080);
const key = runtimeConfig.key ?? import.meta.env.VITE_REVERB_APP_KEY;

if (key) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
    });
} else {
    delete window.Echo;
    console.warn('[Reverb] Chave não configurada; notificações em tempo real desativadas.');
}
