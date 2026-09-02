# AADL - Gestion des Rendez-vous

Application Laravel 12 pour gérer les rendez-vous AADL avec espaces séparés pour les Souscripteurs, Responsables, Agents et Administrateurs.

## Objectif

Le projet permet à un Souscripteur de demander un rendez-vous, suivre son statut, obtenir une fiche RDV avec QR Code après acceptation, puis faire valider sa présence par un Agent. Les Responsables traitent les rendez-vous de leur Direction Régionale. Les Administrateurs gèrent les comptes internes et consultent les données.

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

## Acteurs

- Souscripteur: consulte son profil, crée un RDV, suit ses RDV et ouvre sa fiche RDV.
- Responsable: consulte les RDV de sa direction, accepte les RDV et les complète.
- Agent: scanne le QR Code et valide la présence pour sa direction.
- Admin: consulte les données et gère les comptes Responsables, Agents et Admins.

## Permissions Admin

L'Admin peut:

- consulter les Souscripteurs en lecture seule;
- consulter les Responsables, Agents, Admins et RDV;
- créer, modifier, supprimer et réinitialiser le mot de passe des Responsables;
- créer, modifier, supprimer et réinitialiser le mot de passe des Agents;
- créer, modifier, supprimer et réinitialiser le mot de passe des Admins;
- filtrer les RDV par date, direction et statut.

L'Admin ne peut pas:

- créer, modifier, supprimer ou réinitialiser le mot de passe d'un Souscripteur;
- modifier le statut d'un RDV depuis l'espace Admin;
- supprimer son propre compte Admin;
- supprimer le dernier compte Admin.

## Workflow RDV

Les statuts RDV sont:

1. `0 = RDV pris`: créé par le Souscripteur.
2. `1 = RDV accepté`: accepté par le Responsable.
3. `2 = RDV validé`: présence validée par l'Agent après scan QR.
4. `3 = RDV complété`: clôturé par le Responsable.

Quand tous ses RDV sont complétés, le Souscripteur peut créer un nouveau RDV.

## Règles métier

- La première date disponible est `aujourd'hui + 3 jours`.
- Un Souscripteur ne peut avoir qu'un seul RDV actif.
- Les statuts actifs sont `0`, `1` et `2`.
- Le statut `3` libère le Souscripteur pour une nouvelle demande.
- La capacité est limitée à 30 RDV par direction et par date.
- Les dates complètes sont affichées comme indisponibles.
- Un Souscripteur voit uniquement ses propres RDV.
- Un Responsable voit et modifie uniquement les RDV de sa direction.
- Un Agent valide uniquement les RDV de sa direction.
- Les transitions de statut sont contrôlées côté serveur.
- Les fiches RDV et URLs de vérification utilisent des Hashids.
- Le QR Code contient uniquement une URL de vérification, sans données personnelles.

## Directions et RDV

La `Direction Générale AADL` est disponible pour tous les Souscripteurs.

Chaque Souscripteur voit aussi uniquement sa Direction Régionale assignée via `souscripteurs.dr_id`.

Au moment de créer un RDV, les seules directions autorisées sont:

- `Direction Générale AADL`
- la Direction Régionale assignée au Souscripteur

Exemples:

- Souscripteur Tlemcen -> `Direction Générale AADL` + `Oran`
- Souscripteur Batna -> `Direction Générale AADL` + `Sétif`
- Souscripteur Alger Est -> `Direction Générale AADL` + `Alger Est`
- Souscripteur Alger Ouest -> `Direction Générale AADL` + `Alger Ouest`

Toute autre direction envoyée manuellement est rejetée côté backend.

## Référence AADL 2020

Le mapping des 48 wilayas est basé sur la structure régionale AADL 2020.

Directions Régionales:

- Alger Est
- Alger Ouest
- Oran
- Tiaret
- Constantine
- Sétif
- Annaba
- Ouargla

Destination générale:

- Direction Générale AADL

Tlemcen n'est pas une Direction Régionale. Tlemcen est une wilaya rattachée à `Oran`.

## Mapping Wilaya -> DR

| Code | Wilaya | Direction |
| --- | --- | --- |
| 01 | Adrar | Oran |
| 02 | Chlef | Oran |
| 03 | Laghouat | Ouargla |
| 04 | Oum El Bouaghi | Constantine |
| 05 | Batna | Sétif |
| 06 | Béjaïa | Alger Est |
| 07 | Biskra | Constantine |
| 08 | Béchar | Tiaret |
| 09 | Blida | Alger Ouest |
| 10 | Bouira | Alger Est |
| 11 | Tamanrasset | Ouargla |
| 12 | Tébessa | Annaba |
| 13 | Tlemcen | Oran |
| 14 | Tiaret | Tiaret |
| 15 | Tizi Ouzou | Alger Est |
| 16 | Alger | Spécial: `wilayas.dr_id = null` |
| 17 | Djelfa | Alger Ouest |
| 18 | Jijel | Sétif |
| 19 | Sétif | Sétif |
| 20 | Saïda | Tiaret |
| 21 | Skikda | Annaba |
| 22 | Sidi Bel Abbès | Oran |
| 23 | Annaba | Annaba |
| 24 | Guelma | Annaba |
| 25 | Constantine | Constantine |
| 26 | Médéa | Alger Ouest |
| 27 | Mostaganem | Oran |
| 28 | M'Sila | Sétif |
| 29 | Mascara | Tiaret |
| 30 | Ouargla | Ouargla |
| 31 | Oran | Oran |
| 32 | El Bayadh | Tiaret |
| 33 | Illizi | Ouargla |
| 34 | Bordj Bou Arréridj | Sétif |
| 35 | Boumerdès | Alger Est |
| 36 | El Tarf | Annaba |
| 37 | Tindouf | Oran |
| 38 | Tissemsilt | Tiaret |
| 39 | El Oued | Ouargla |
| 40 | Khenchela | Constantine |
| 41 | Souk Ahras | Annaba |
| 42 | Tipaza | Alger Ouest |
| 43 | Mila | Constantine |
| 44 | Aïn Defla | Alger Ouest |
| 45 | Naâma | Tiaret |
| 46 | Aïn Témouchent | Oran |
| 47 | Ghardaïa | Ouargla |
| 48 | Relizane | Oran |

## Cas spécial Alger

La wilaya officielle `16 = Alger` reste unique. Le projet ne crée pas de codes artificiels comme `16E` ou `16O`.

Alger n'est pas rattachée automatiquement à `Alger Est` ou `Alger Ouest` dans `wilayas.dr_id`.

Les Souscripteurs d'Alger sont distingués explicitement avec `souscripteurs.dr_id`:

- Souscripteur Alger Est -> `dr_id = Alger Est`
- Souscripteur Alger Ouest -> `dr_id = Alger Ouest`

## Données seedées

Le seeder est idempotent et crée:

- les directions confirmées;
- les 48 wilayas avec mapping AADL 2020;
- `SUB001` avec `wil = 16`, rattaché à `Alger Est`;
- `responsable@aadl.test` rattaché à `Alger Est`;
- `agent@aadl.test` rattaché à `Alger Est`;
- `responsable.dg@aadl.test` rattaché à `Direction Générale AADL`;
- `agent.dg@aadl.test` rattaché à `Direction Générale AADL`;
- `admin@aadl.test`.

Une ancienne direction `Tlemcen` non référencée peut être nettoyée. Si elle est référencée par des données existantes, elle n'est pas supprimée automatiquement afin de préserver les données.

## Installation

Prérequis:

- PHP disponible en ligne de commande
- Composer
- MySQL démarré
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

## Base de données

Créer/configurer la base MySQL, puis exécuter:

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

## Tests et build

```powershell
php artisan test
node --test tests/js/*.test.mjs
npm run build
```

Statut actuel des tests PHP:

```text
264 passed
```

## Identifiants de test

Ces identifiants sont réservés au développement local.

- Souscripteur: `SUB001` / `password`
- Responsable: `responsable@aadl.test` / `password`
- Agent: `agent@aadl.test` / `password`
- Responsable DG: `responsable.dg@aadl.test` / `password`
- Agent DG: `agent.dg@aadl.test` / `password`
- Admin: `admin@aadl.test` / `password`

## Routes principales

- `/`
- `/souscripteur/login`
- `/souscripteur/dashboard`
- `/souscripteur/profil`
- `/souscripteur/rdvs`
- `/souscripteur/rdvs/create`
- `/souscripteur/rdvs/indisponibilites`
- `/souscripteur/rdvs/{hashid}/fiche`
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

## Interface

- Page publique `/` avec lien vers le site officiel AADL: `https://www.aadl.com.dz/`.
- Logo officiel: `public/images/aadl-logo.png`.
- Dark mode global avec sauvegarde dans `localStorage` via la clé `aadl-theme`.
- La fiche RDV imprimable reste claire/blanche à l'impression.

## QR Code et caméra

Le scan QR utilise la caméra du navigateur. L'utilisateur doit autoriser l'accès caméra.

En développement, utiliser `localhost` ou `127.0.0.1`. En production, HTTPS est requis par les navigateurs modernes pour accéder à la caméra.

## Structure du projet

- `app/Http/Controllers`: authentification, workflow RDV et administration.
- `app/Models`: modèles Eloquent.
- `app/Support`: aide Hashids RDV.
- `database/migrations`: structure de base.
- `database/seeders`: données de développement et mapping AADL 2020.
- `resources/views`: interfaces Blade.
- `resources/views/components`: composants réutilisables.
- `resources/js`: scripts frontend, scanner QR et dark mode.
- `routes/web.php`: routes web.
- `tests/Feature`: tests fonctionnels Laravel.
- `tests/js`: tests JavaScript.

## Dépannage

### PHP introuvable dans PATH

Ajouter le dossier contenant `php.exe` au `PATH`, ouvrir un nouveau terminal, puis vérifier:

```powershell
php -v
```

### Composer manquant

Installer Composer depuis le site officiel, sélectionner le bon PHP, puis vérifier:

```powershell
composer -V
```

### MySQL arrêté

Démarrer le service MySQL utilisé localement avant `php artisan migrate`, `db:seed` ou `test`.

### Port 3306 indisponible

Vérifier le port:

```powershell
Test-NetConnection 127.0.0.1 -Port 3306
```

Adapter `DB_PORT` si nécessaire.

### Connexion base refusée

Vérifier `.env`, démarrer MySQL, confirmer que la base existe, puis exécuter:

```powershell
php artisan config:clear
```

### Permission caméra refusée

Autoriser la caméra dans le navigateur pour la page Agent scanner, puis recharger la page.

### QR scanner ne fonctionne pas

Utiliser `http://localhost`, `http://127.0.0.1` ou HTTPS.

### Échec npm build

Réinstaller les dépendances frontend puis relancer:

```powershell
npm install
npm run build
```
