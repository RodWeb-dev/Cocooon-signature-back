# Cocoon-Signature - Back

## Le projet

Site e-commerce de **Cocoon-Signature**, atelier de 3 personnes dans la Loire (42), spécialisé dans le mobilier artisanal en bois de fabrication française en petites séries.

Domaine : `cocoon-signature.fr`

### Architecture

Architecture découplée front / back : deux dépôts Git indépendants communicant via une API REST.

```txt
[Navigateur]
     | HTTPS
     v
[Traefik - reverse proxy]
     |-- cocoon-signature.fr      --> [Nuxt / Node.js SSR]
     |                                       | HTTP interne Docker
     |                                       v
     |-- api.cocoon-signature.fr  --> [PHP API REST]
     |                                       |
     |                             [MariaDB] [Odoo Cloud] [Brevo / PayPlug]
     |
     +-- admin.cocoon-signature.fr --> [Interface admin - Tailscale]
```

**Regle critique** : le back PHP n'est jamais exposé publiquement. Nuxt appelle l'API via `http://cocoon-back:80` (réseau Docker interne).

### Stack globale

| Couche | Technologie |
| --- | --- |
| Frontend | Nuxt.js (Vue 3 + SSR) 4.4.6 |
| Backend | PHP 8.4 (API REST sans framework) |
| Base de données | MariaDB 11 |
| Emails | Brevo (prod) / Mailpit (dev) |
| Paiement | PayPlug |
| ERP | Odoo Cloud (XML-RPC / JSON-RPC) |
| Conteneurisation | Docker / Docker Compose |
| Reverse proxy | Traefik 3.6 |
| CI/CD | GitLab CI |

### Environnements

| Env | Hôte | Déclenchement |
| --- | --- | --- |
| Dev local | rddev / rdserv (Fedora 44) | branches feature/fix |
| Staging | rwd-test OVH (135.125.103.55) | merge sur `develop` (auto) |
| Production | rwd-prod-01 OVH (135.125.103.3) | merge sur `main` (manuel) |

### Pipeline CI/CD

```txt
[Dev local]  -->  push branche  -->  [GitLab CI] tests + build
                                           |
                                           +-- [Staging]  (auto sur develop)
                                           |
                                           +-- [Production]  (manuel sur main)
```

Stages : `test` (lint PHP + SAST), `secret-detection`, `build` (image Docker), `deploy`, `migrate`.

---

## Backend PHP

### Prérequis

- Docker et Docker Compose
- Réseau Docker `cocoon_network` créé (`docker network create cocoon_network`)
- Infrastructure Traefik lancée (`infra/docker-compose.yml`)

### Variables d'environnement

Copier `.env.example` en `.env` et renseigner les valeurs :

```txt
APP_ENV=development
FRONTEND_URL=http://cocoon-signature.test

DB_NAME=
DB_USER=
DB_PASSWORD=
DB_HOST=mariadb
DB_ROOT_PASSWORD=

JWT_SECRET=
JWT_EXPIRATION=900

ODOO_URL=
ODOO_DB=
ODOO_USER=
ODOO_UID=
ODOO_API_KEY=

MAILPIT_HOST=mailpit
MAILPIT_PORT=1025

BREVO_API_KEY=

PAYPLUG_SECRET_KEY=
PAYPLUG_WEBHOOK_SECRET=
```

Les secrets sensibles (clés API, mots de passe) sont stockés dans les variables CI/CD de GitLab et ne sont jamais versionnés.

### Lancement en dev

```bash
# Depuis le dossier back/
docker compose up -d
```

L'API est accessible sur `http://api.cocoon-signature.test` (entrée `/etc/hosts` requise).

Pour installer les dépendances PHP dans le conteneur :

```bash
docker exec cocoon-back composer install
```

### Migrations

Les migrations SQL sont numérotées (`001_` a `023_`) et appliquées dans l'ordre par `migrate.php` (idempotent).

```bash
docker exec cocoon-back php /var/www/html/migrate.php
```

Le script crée la table `migrations` si absente, puis joue uniquement les fichiers non encore appliques.

**Regles** : une migration jouee en prod ne doit jamais etre modifiee. Toujours valider sur staging avant la prod.

### Scripts Cron (synchronisation Odoo)

```bash
docker exec cocoon-back php /var/www/html/Cron/sync-categories.php
docker exec cocoon-back php /var/www/html/Cron/sync-subcategories.php
docker exec cocoon-back php /var/www/html/Cron/sync-products.php
docker exec cocoon-back php /var/www/html/Cron/sync-variants.php
```

### Structure du code

```txt
src/
+-- public/
|   +-- index.php          Point d'entree unique (DocumentRoot)
|   +-- .htaccess          Reecriture URL vers index.php
+-- Core/
|   +-- Router.php         Routeur HTTP (GET/POST/PATCH/DELETE, params {id})
|   +-- Routes.php         Declaration de toutes les routes
|   +-- DBConnection.php   Singleton PDO MariaDB
|   +-- Request.php        Value object (method, uri, body, user, params, query)
|   +-- Security/
|   |   +-- JWT.php        Encode / decode token HS256
|   |   +-- RateLimit.php  Limitation en base (login 5/15min, register 3/h)
|   |   +-- FilterInput.php Sanitisation et validation des entrees
|   +-- Mail/
|   |   +-- MailTransportInterface.php
|   |   +-- SmtpTransport.php   Dev (Mailpit TCP)
|   |   +-- BrevoTransport.php  Prod (SDK Brevo)
|   +-- Exceptions/        HttpException, 401, 403, 404, 405
+-- Controllers/           Un controller par ressource
+-- Services/              EmailService, OdooFactory, OdooStubService, OdooApiService
+-- Models/                Requetes PDO preparees par entite
+-- Cron/                  Scripts de synchronisation Odoo
+-- migrations/            Fichiers SQL numerotes (001-023)
+-- Templates/Mail/        Templates PHP pour les emails
```

### API REST - Endpoints principaux

| Groupe | Endpoints |
| --- | --- |
| Auth | POST /register /login /logout /refresh /forgot-password /reset-password, GET /verify-email/{token} |
| Users | GET/PATCH/DELETE /me, CRUD /me/addresses |
| Products | GET /products, GET /products/{slug}, GET+POST /products/{slug}/reviews |
| Collections | GET /collections, GET /collections/{slug} |
| Categories | GET /categories, GET /subcategories |
| Cart | GET/POST/PATCH/DELETE items, POST /merge |
| Newsletter | POST (public), PATCH (auth) |
| Orders | POST (creer), GET (liste + detail) - a implementer |
| Payment | POST /payplug/webhook - a implementer |

Format de reponse : `{ "data": <valeur|null>, "message": <cle i18n|null>, "error": <cle i18n|null> }`

### Authentification

- JWT HS256 (access token, duree configurable via `JWT_EXPIRATION`)
- Refresh token HttpOnly cookie (7 jours, rotation a chaque usage)
- Le refresh token n'est jamais dans le body JSON

### Securite applicative

- Requetes SQL preparees (PDO, jamais de concatenation)
- `FilterInput` : strip_tags, trim, validation email et mot de passe
- `RateLimit` : stocke en base (table `rate_limits`), par IP et par email
- Bcrypt pour les mots de passe
- UUID v7 pour les identifiants utilisateurs
- Tokens a usage unique (reset_pwd, verify_email) expirant en 1h / 24h

### Dependances PHP

| Package | Usage |
| --- | --- |
| `vlucas/phpdotenv` ^5.6 | Variables d'environnement |
| `firebase/php-jwt` ^7.0 | JWT HS256 |
| `getbrevo/brevo-php` ^4.0 | Emails transactionnels |
| `payplug/payplug-php` ^4.0 | Paiement PayPlug |
| `phpoffice/phpspreadsheet` ^5.8 | Export tableur |

### Conventions de code

- `PascalCase` : classes
- `camelCase` : methodes et proprietes
- `SCREAMING_SNAKE_CASE` : constantes
- `snake_case` : champs base de donnees
- `declare(strict_types=1)` sur chaque fichier
- Commentaires en anglais
