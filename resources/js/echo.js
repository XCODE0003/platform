import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;
// Verbose Pusher logs in browser console
try { window.Pusher.logToConsole = true } catch (_) {}

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

// Basic lifecycle logs
console.log('[echo] initialized', {
  host: import.meta.env.VITE_REVERB_HOST,
  port: import.meta.env.VITE_REVERB_PORT,
  key: import.meta.env.VITE_REVERB_APP_KEY,
});
try {
  window.Echo.connector.pusher.connection.bind('connected', () => console.log('[echo] connected'))
  window.Echo.connector.pusher.connection.bind('error', (e) => console.warn('[echo] error', e))
} catch (_) {}
