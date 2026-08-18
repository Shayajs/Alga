<p align="center">
  <img src="public/favicon.png" width="64" height="64" alt="Alga">
</p>

<h1 align="center">Alga</h1>

<p align="center">
  <strong>Le board de la maison.</strong><br>
  Deux foyers, un planning écrit, des ops ménagères horodatées.
</p>

<p align="center">
  <img src="public/img/alga.png" width="160" alt="Marque Alga">
</p>

Prod : [alga.pp.ua](https://alga.pp.ua) · PWA (installable sur téléphone)

---

## C’est quoi

Alga répartit les tâches de la maison entre **deux couples**. Chaque jour et chaque semaine, les lots **A** et **B** tournent. Ce qui est coché est vrai.

| Lot | Tous les jours | Une fois par semaine |
| --- | --- | --- |
| **A** | Ménage du salon | Salon et Cuisine |
| **B** | Cuisine et linge | Toilettes et salle de bain |

Le jour opérationnel bascule à **03:00**, les heures restent en **UTC+2** (Europe/Paris).

**Lucas** a la console `/admin` : catalogue des tâches, règles, régénération du planning (8 semaines). Les cochages déjà faits ne bougent pas.

## Stack

Laravel · MariaDB · Docker · Nginx Proxy Manager (TLS) · PWA (`manifest.webmanifest` + `sw.js`)

## Lancer en local

```bash
cp .env.example .env
# APP_KEY : php artisan key:generate (dans le conteneur app)
docker compose up -d --build
docker exec alga_app php artisan migrate --force
docker exec alga_app php artisan db:seed
```

Le compose de prod joint le réseau Docker `www_laravel_net`. En local, il te faut ce réseau (stack Allotata) **ou** un override perso — `docker-compose-shaya.dev.yaml` n’est pas versionné.

## Prod (alga.pp.ua)

1. Déposer le `.env` production (`APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://alga.pp.ua`).
2. Dans NPM : Proxy Host `alga.pp.ua` → `alga_nginx` port **80**, SSL on.
3. Démarrer :

```bash
docker compose build app
docker compose up -d
docker exec alga_app php artisan migrate --force
docker exec alga_app php artisan config:cache
```

Ne jamais committer `.env`. `vendor/` et `node_modules/` sont ignorés.

## PWA

Icônes dans `public/` : `favicon.ico`, `favicon.png`, `icon-192.png`, `icon-512.png`, maskable.

Sur Android : menu → ajouter à l’écran d’accueil.  
Sur iPhone : Safari → Partager → Sur l’écran d’accueil.

## Tests

```bash
docker exec alga_app php artisan test
```
