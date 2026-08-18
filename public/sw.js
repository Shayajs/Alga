const CACHE = 'alga-shell-v1';
const PRECACHE = [
    '/offline.html',
    '/css/alga.css',
    '/js/theme.js',
    '/js/modale.js',
    '/js/pwa.js',
    '/js/connexion.js',
    '/img/alga.png',
    '/icon-192.png',
    '/icon-512.png',
    '/manifest.webmanifest',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))
        )).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(reseauPuisHorsLigne(request));
        return;
    }

    if (estStatique(url.pathname)) {
        event.respondWith(cachePuisReseau(request));
    }
});

function estStatique(chemin) {
    return /\.(css|js|png|jpe?g|gif|ico|svg|webp|woff2?|ttf|otf|webmanifest)$/i.test(chemin)
        || chemin === '/offline.html';
}

async function reseauPuisHorsLigne(request) {
    try {
        const reponse = await fetch(request);
        return reponse;
    } catch (e) {
        const cache = await caches.open(CACHE);
        return (await cache.match('/offline.html')) || Response.error();
    }
}

async function cachePuisReseau(request) {
    const cache = await caches.open(CACHE);
    const enCache = await cache.match(request);
    const reseau = fetch(request).then((reponse) => {
        if (reponse && reponse.ok) {
            cache.put(request, reponse.clone());
        }
        return reponse;
    }).catch(() => enCache);

    return enCache || reseau;
}
