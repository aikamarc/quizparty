import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    // Use whatever host the visitor actually loaded the page from (localhost,
    // quizparty.test, a LAN IP, ...) rather than the fixed VITE_REVERB_HOST env
    // value — otherwise anyone not on the exact machine running Reverb (e.g. a
    // guest joining from another device) can never open the WebSocket connection.
    wsHost: window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
