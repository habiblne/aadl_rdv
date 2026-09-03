# AADL - Gestion des Rendez-vous

Application Laravel 12 de gestion des rendez-vous AADL avec espaces separes pour les Souscripteurs, Responsables, Agents et Administrateurs.

## Objectif

Le projet permet a un Souscripteur de demander un rendez-vous, suivre son statut, consulter sa fiche RDV, telecharger sa fiche PDF et faire verifier sa presence par QR Code. Les Responsables traitent les rendez-vous de leur direction, les Agents valident les rendez-vous apres verification, et les Administrateurs gerent les comptes internes et consultent les donnees.

## Technologies

- PHP 8.2+
- Laravel 12
- Laravel Breeze Blade
- MySQL 8
- Composer
- Node.js / npm
- Vite
- Tailwind CSS
- Alpine.js
- Vinkla Hashids
- Simple QR Code
- html5-qrcode
- Laravel DOMPDF

## Acteurs

- Souscripteur: consulte son profil, cree un RDV, suit ses RDV, ouvre sa fiche RDV et telecharge sa fiche PDF.
- Responsable: consulte les RDV de sa direction, accepte les RDV et les complete.
- Agent: scanne le QR Code et valide la presence pour sa direction.
- Admin: consulte les donnees et gere les comptes Responsables, Agents et Admins.

## Workflow RDV

Les statuts RDV sont:

1. `0 = RDV pris`: cree par le Souscripteur.
2. `1 = RDV accepte`: accepte par le Responsable.
3. `2 = RDV valide`: presence validee par l'Agent apres scan QR.
4. `3 = RDV complete`: cloture par le Responsable.

Quand tous ses RDV sont completes, le Souscripteur peut creer un nouveau RDV.

## Regles Metier

- La premiere date disponible est `aujourd'hui + 3 jours`.
- Un Souscripteur ne peut avoir qu'un seul RDV actif.
- Les statuts actifs sont `0`, `1` et `2`.
- Le statut `3` libere le Souscripteur pour une nouvelle demande.
- La capacite est limitee a 30 RDV par direction et par date.
- Les dates completes sont affichees comme indisponibles.
- Un Souscripteur voit uniquement ses propres RDV.
- Un Responsable voit et modifie uniquement les RDV de sa direction.
- Un Agent valide uniquement les RDV de sa direction.
- Les transitions de statut sont controlees cote serveur.
- Les fiches RDV et URLs de verification utilisent des Hashids.
- Le QR Code contient uniquement une URL de verification, sans donnees personnelles.

## Fiche RDV, PDF et QR Code

- La fiche RDV web est disponible uniquement pour les statuts `1`, `2` et `3`.
- Le telechargement PDF est disponible via `/souscripteur/rdvs/{hashid}/pdf`.
- Le PDF est reserve au Souscripteur authentifie proprietaire du RDV.
- Le PDF est disponible uniquement pour les statuts `1`, `2` et `3`; le statut `0` reste indisponible.
- Le PDF contient le logo AADL, les informations du Souscripteur, la structure d'accueil, la date, le motif, le statut, la reference RDV et un QR Code.
- Le NIN est masque dans le PDF.
- Le QR Code du PDF utilise la meme URL de verification avec Hashid que la fiche web.
- Aucune donnee personnelle n'est encodee dans le QR Code.

## Pagination

Les grandes listes utilisent une pagination Laravel de 15 elements par page avec conservation des filtres et recherches via `withQueryString()`:

- Souscripteur RDV
- Responsable RDV
- Admin RDV
- Admin Souscripteurs
- Admin Responsables
- Admin Agents
- Admin Admins

## Directions et RDV

La `Direction Generale AADL` est disponible pour tous les Souscripteurs.

Chaque Souscripteur voit aussi uniquement sa Direction Regionale assignee via `souscripteurs.dr_id`.

Au moment de creer un RDV, les seules directions autorisees sont:

- `Direction Generale AADL`
- la Direction Regionale assignee au Souscripteur

Toute autre direction envoyee manuellement est rejetee cote backend.

## Reference AADL 2020

Le mapping des 48 wilayas est base sur la structure regionale AADL 2020.

Directions Regionales:

- Alger Est
- Alger Ouest
- Oran
- Tiaret
- Constantine
- Setif
- Annaba
- Ouargla

Destination generale:

- Direction Generale AADL

Tlemcen n'est pas une Direction Regionale. Tlemcen est une wilaya rattachee a `Oran`.

## Mapping Wilaya -> DR

| Code | Wilaya | Direction |
| --- | --- | --- |
| 01 | Adrar | Oran |
| 02 | Chlef | Oran |
| 03 | Laghouat | Ouargla |
| 04 | Oum El Bouaghi | Constantine |
| 05 | Batna | Setif |
| 06 | Bejaia | Alger Est |
| 07 | Biskra | Constantine |
| 08 | Bechar | Tiaret |
| 09 | Blida | Alger Ouest |
| 10 | Bouira | Alger Est |
| 11 | Tamanrasset | Ouargla |
| 12 | Tebessa | Annaba |
| 13 | Tlemcen | Oran |
| 14 | Tiaret | Tiaret |
| 15 | Tizi Ouzou | Alger Est |
| 16 | Alger | Special: `wilayas.dr_id = null` |
| 17 | Djelfa | Alger Ouest |
| 18 | Jijel | Setif |
| 19 | Setif | Setif |
| 20 | Saida | Tiaret |
| 21 | Skikda | Annaba |
| 22 | Sidi Bel Abbes | Oran |
| 23 | Annaba | Annaba |
| 24 | Guelma | Annaba |
| 25 | Constantine | Constantine |
| 26 | Medea | Alger Ouest |
| 27 | Mostaganem | Oran |
| 28 | M'Sila | Setif |
| 29 | Mascara | Tiaret |
| 30 | Ouargla | Ouargla |
| 31 | Oran | Oran |
| 32 | El Bayadh | Tiaret |
| 33 | Illizi | Ouargla |
| 34 | Bordj Bou Arreridj | Setif |
| 35 | Boumerdes | Alger Est |
| 36 | El Tarf | Annaba |
| 37 | Tindouf | Oran |
| 38 | Tissemsilt | Tiaret |
| 39 | El Oued | Ouargla |
| 40 | Khenchela | Constantine |
| 41 | Souk Ahras | Annaba |
| 42 | Tipaza | Alger Ouest |
| 43 | Mila | Constantine |
| 44 | Ain Defla | Alger Ouest |
| 45 | Naama | Tiaret |
| 46 | Ain Temouchent | Oran |
| 47 | Ghardaia | Ouargla |
| 48 | Relizane | Oran |

## Cas Special Alger

La wilaya officielle `16 = Alger` reste unique. Le projet ne cree pas de codes artificiels comme `16E` ou `16O`.

Alger n'est pas rattachee automatiquement a `Alger Est` ou `Alger Ouest` dans `wilayas.dr_id`.

Les Souscripteurs d'Alger sont distingues explicitement avec `souscripteurs.dr_id`:

- Souscripteur Alger Est -> `dr_id = Alger Est`
- Souscripteur Alger Ouest -> `dr_id = Alger Ouest`

## Donnees Seedees

Le seeder est idempotent et cree:

- les directions confirmees;
- les 48 wilayas avec mapping AADL 2020;
- `SUB001` avec `wil = 16`, rattache a `Alger Est`;
- `responsable@aadl.test` rattache a `Alger Est`;
- `agent@aadl.test` rattache a `Alger Est`;
- `responsable.dg@aadl.test` rattache a `Direction Generale AADL`;
- `agent.dg@aadl.test` rattache a `Direction Generale AADL`;
- `admin@aadl.test`.

Les comptes `@aadl.test`, y compris les comptes Responsable DG et Agent DG, sont reserves au developpement et aux tests locaux.

En environnement non-production, le seeder ajoute aussi des donnees de preview de pagination clairement test-only.

## Interface

- Page publique `/` redesigned avec une hero section 2 colonnes.
- Photo reelle du nouveau siege AADL: `public/images/aadl-headquarters.jpg`.
- Logo officiel: `public/images/aadl-logo.png`.
- Favicon AADL: `public/favicon.ico`.
- Navbar fixe avec lien vers le site officiel AADL: `https://www.aadl.com.dz/`.
- Dark mode global avec sauvegarde dans `localStorage` via la cle `aadl-theme`.
- Fiche RDV PDF propre, blanche et adaptee a l'impression.

## Installation

Prerequis:

- PHP disponible en ligne de commande
- Composer
- MySQL demarre
- Node.js et npm

Installation:

```powershell
composer install
npm install
copy .env.example .env
php artisan key:generate
```

Exemple `.env`:

```env
APP_NAME="AADL Rendez-vous"
APP_ENV=local
APP_KEY=
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aadl_rdv
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
```

## Base de Donnees

Creer/configurer la base MySQL, puis executer:

```powershell
php artisan migrate
php artisan db:seed
```

## Lancement

```powershell
php artisan serve
npm run dev
```

Application locale:

```text
http://127.0.0.1:8000
```

## Tests et Build

Commandes:

```powershell
php artisan test
node --test tests/js/*.test.mjs
npm run build
```

Derniere verification effectuee:

```text
php artisan test: 271 passed, 1498 assertions
npm run build: passed
```

## Identifiants de Test

Ces identifiants sont reserves au developpement local.

- Souscripteur: `SUB001` / `password`
- Responsable: `responsable@aadl.test` / `password`
- Agent: `agent@aadl.test` / `password`
- Responsable DG: `responsable.dg@aadl.test` / `password`
- Agent DG: `agent.dg@aadl.test` / `password`
- Admin: `admin@aadl.test` / `password`

## Routes Principales

- `/`
- `/souscripteur/login`
- `/souscripteur/dashboard`
- `/souscripteur/profil`
- `/souscripteur/rdvs`
- `/souscripteur/rdvs/create`
- `/souscripteur/rdvs/indisponibilites`
- `/souscripteur/rdvs/{hashid}/fiche`
- `/souscripteur/rdvs/{hashid}/pdf`
- `/responsable/login`
- `/responsable/dashboard`
- `/responsable/rdvs`
- `/responsable/rdvs/{rdv}/accepter`
- `/responsable/rdvs/{rdv}/completer`
- `/agent/login`
- `/agent/dashboard`
- `/agent/scanner`
- `/agent/rdvs/{hashid}/verification`
- `/agent/rdvs/{hashid}/valider`
- `/admin/login`
- `/admin/dashboard`
- `/admin/souscripteurs`
- `/admin/responsables`
- `/admin/responsables/create`
- `/admin/responsables/{responsable}/edit`
- `/admin/responsables/{responsable}/mot-de-passe`
- `/admin/agents`
- `/admin/agents/create`
- `/admin/agents/{agent}/edit`
- `/admin/agents/{agent}/mot-de-passe`
- `/admin/admins`
- `/admin/admins/create`
- `/admin/admins/{admin}/edit`
- `/admin/admins/{admin}/mot-de-passe`
- `/admin/rdvs`

## QR Code et Camera

Le scan QR utilise la camera du navigateur. L'utilisateur doit autoriser l'acces camera.

En developpement, utiliser `localhost` ou `127.0.0.1`. En production, HTTPS est requis par les navigateurs modernes pour acceder a la camera.

## Structure du Projet

- `app/Http/Controllers`: authentification, workflow RDV et administration.
- `app/Models`: modeles Eloquent.
- `app/Support`: aide Hashids RDV.
- `database/migrations`: structure de base.
- `database/seeders`: donnees de developpement et mapping AADL 2020.
- `resources/views`: interfaces Blade.
- `resources/views/components`: composants reutilisables.
- `resources/js`: scripts frontend, scanner QR et dark mode.
- `routes/web.php`: routes web.
- `tests/Feature`: tests fonctionnels Laravel.
- `tests/js`: tests JavaScript.

## Depannage

### PHP introuvable dans PATH

Ajouter le dossier contenant `php.exe` au `PATH`, ouvrir un nouveau terminal, puis verifier:

```powershell
php -v
```

### Composer manquant

Installer Composer depuis le site officiel, selectionner le bon PHP, puis verifier:

```powershell
composer -V
```

### MySQL arrete

Demarrer le service MySQL utilise localement avant `php artisan migrate`, `db:seed` ou `test`.

### Connexion base refusee

Verifier `.env`, demarrer MySQL, confirmer que la base existe, puis executer:

```powershell
php artisan config:clear
```

### Permission camera refusee

Autoriser la camera dans le navigateur pour la page Agent scanner, puis recharger la page.

### Echec npm build

Reinstaller les dependances frontend puis relancer:

```powershell
npm install
npm run build
```
