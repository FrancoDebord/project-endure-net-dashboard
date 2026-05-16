const CACHE = 'endure-net-v1';

const STATIC_ASSETS = [
    'https://cdn.tailwindcss.com',
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
    'https://a.tile.openstreetmap.org',
];

// Pré-cache des assets statiques à l'installation
self.addEventListener('install', event => {
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Tuiles OSM : cache-first (changent rarement)
    if (url.hostname.includes('tile.openstreetmap.org')) {
        event.respondWith(
            caches.open(CACHE).then(cache =>
                cache.match(event.request).then(cached =>
                    cached || fetch(event.request).then(resp => {
                        cache.put(event.request, resp.clone());
                        return resp;
                    })
                )
            )
        );
        return;
    }

    // Assets CDN : cache-first
    if (url.hostname.includes('cdn.tailwindcss.com') ||
        url.hostname.includes('cdn.jsdelivr.net') ||
        url.hostname.includes('unpkg.com')) {
        event.respondWith(
            caches.open(CACHE).then(cache =>
                cache.match(event.request).then(cached =>
                    cached || fetch(event.request).then(resp => {
                        cache.put(event.request, resp.clone());
                        return resp;
                    })
                )
            )
        );
        return;
    }

    // Dashboard : network-first (données fraîches), fallback sur cache
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then(resp => {
                    const clone = resp.clone();
                    caches.open(CACHE).then(c => c.put(event.request, clone));
                    return resp;
                })
                .catch(() => caches.match(event.request))
        );
    }
});
