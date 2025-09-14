# Marso Catalog

Egy egyszerű termékkatalógus alkalmazás, amely bemutatja a backend (Symfony + API Platform) és frontend (Vue 3 + Tailwind CSS) integrációját.  
A projekt célja a felhasználóbarát, reszponzív felület kialakítása, ahol a termékek listázhatók, kereshetők és szűrhetők.

## Technológiák

### Backend
- PHP 8.3 (Symfony keretrendszer)
- API Platform (REST API)
- MySQL 8.0
- Doctrine ORM
- Docker + Docker Compose

### Frontend
- Vue.js 3
- Tailwind CSS
- Axios
- Vite dev szerver

## Fő funkciók
- Termékek és kategóriák kezelése adatbázisban
- CSV fájlból importálható termékek (`bin/console app:import-products path/to/file.csv`)
- Lapozható terméklista (20 elem oldalanként)
- Termék részletes oldal
- Keresés terméknévben és leírásban
- Szűrés évszak szerint (nyári, téli, 4 évszakos)
- Szűrés átmérő szerint (12–22 col)
- Rendezés ár, név, dátum alapján
- Főoldalon véletlenszerűen kiválasztott termékek
- Reszponzív, mobilbarát design

## Opcionális funkciók (megvalósítva)
- ✅ Docker Compose alapú futtatás
- ✅ API Platform használata
- ✅ Keresés és szűrés
- ✅ Frontend AJAX / Fetch alapú lekérésekkel

## Telepítés és futtatás

### 1. Backend (Docker Compose)
A projekt gyökeréből futtasd:

```bash
docker compose up -d --build
```

Ez elindítja:
- PHP-FPM (Symfony backend)
- MySQL adatbázis
- Nginx szerver

Migráció futtatása:
```bash
docker compose exec php php bin/console doctrine:migrations:migrate -n
```

Swagger UI elérés:  
👉 [http://localhost:8080/api](http://localhost:8080/api)

### 2. Frontend
A `frontend` mappából:

```bash
npm install
npm run dev
```

Frontend elérés:  
👉 [http://localhost:5173](http://localhost:5173)

## Használat
- Nyisd meg a frontend felületet: `http://localhost:5173`
- A főoldalon 8 véletlenszerű termék jelenik meg.
- A **Termékek** oldalon kereshetsz név / leírás alapján, vagy szűrhetsz évszak és átmérő szerint.
- A backend API böngészhető a Swagger UI segítségével: `http://localhost:8080/api`

## Repository
A projekt Git verziókövetéssel készült.  
A teljes forráskód és commit történet elérhető a repository-ban.

## Készítette:
Gyarmati Bence @ 2025