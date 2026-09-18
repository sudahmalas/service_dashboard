import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// ── Konfigurasi Dinamis Runtime dari Laravel Blade (.env) ──────────────────
const cfg = (typeof window !== 'undefined' && window.__CONFIG__?.reverb) ? window.__CONFIG__.reverb : {};

// Ground truth protokol browser:
const isBrowserHttps = typeof window !== 'undefined' && window.location.protocol === 'https:';
const currentHostname = typeof window !== 'undefined' ? window.location.hostname : '127.0.0.1';
const isLocal = ['localhost', '127.0.0.1', '::1'].includes(currentHostname);

// Host: Jika di hosting (bukan localhost), utamakan hostname browser saat ini agar selalu sinkron
const host = (!isLocal && currentHostname) ? currentHostname : (cfg.host || import.meta.env.VITE_REVERB_HOST || currentHostname || '127.0.0.1');

// Force TLS jika halaman dibuka via HTTPS (wajib bagi browser agar tidak mixed content)
const forceTLS = isBrowserHttps || cfg.scheme === 'https';

// Resolusi Port:
// - Jika diatur manual di .env (cfg.port), gunakan port tersebut
// - Jika di hosting (production):
//     * HTTPS / WSS: Port 443 (standar SSL, browser tidak akan menambahkan :port)
//     * HTTP / WS:   Port 80 (standar web)
// - Jika di localhost development:
//     * Port 8090 (Reverb dev server)
let resolvedPort = cfg.port ? parseInt(cfg.port) : null;
if (!resolvedPort || (forceTLS && resolvedPort === 80)) {
    if (forceTLS) {
        resolvedPort = 443;
    } else if (isLocal) {
        resolvedPort = import.meta.env.VITE_REVERB_PORT ? parseInt(import.meta.env.VITE_REVERB_PORT) : 8090;
    } else {
        resolvedPort = (typeof window !== 'undefined' && window.location.port) ? parseInt(window.location.port) : 80;
    }
}

const appKey = cfg.appKey || import.meta.env.VITE_REVERB_APP_KEY;

export const echo = new Echo({
    broadcaster: 'reverb',
    key: appKey,
    wsHost: host,
    wsPort: forceTLS ? 443 : resolvedPort,
    wssPort: 443,
    forceTLS: forceTLS,
    enabledTransports: ['ws', 'wss'],
});

export default echo;
