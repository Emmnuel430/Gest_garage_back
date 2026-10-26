# Backend - Gestion de Garage

Application backend Laravel 11 pour la gestion operationnelle, financiere et logistique d'un garage automobile.

## Prerequis

- PHP 8.2+
- Composer
- MySQL 8.0+
- Extension PHP : PDO, OpenSSL, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath, cURL

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
```

## Fonctionnalites Cles

### 1. Cycle de reparation et chronometrage

- Reception d'un vehicule (`ReceptionController`).
- Fiche de controle / etat des lieux (`CheckReceptionController`).
- Lancement de la reparation avec chrono dedie par vehicule (`ChronoController`).
- Enregistrement des pauses et calcul deterministe des heures nettes facturees.

### 2. Outils et Magasin d'outillage

- Gestion de l'inventaire des outils (`OutilController`).
- **Outils speciaux :**
    - Champs `est_special` (booleen) et `prix_par_heure` (montant en FCFA).
    - Facturation a l'heure selon la duree nette du chrono du vehicule (`ceil(duree_minutes / 60)`).
    - Facturation strictement attribuee par vehicule : si un mecanicien utilise le meme outil pour plusieurs vehicules distincts, chaque reparation enregistre son pret et calcule sa part independamment selon son chrono propre.
- Prets et retours d'outils affectes aux reparations (`PretOutilController`).
- **Partage et affectation multi-vehicules :** Un outil deja prete a un mecanicien peut etre affecte/partage vers un autre vehicule actif de ce mecanicien (`/update_pret/{id}` ou `/partager_pret/{id}`). Le stock magasin n'est pas redecremente et le second vehicule facture l'outil selon son chrono propre.
- **Autorisations et Gates :** Prise en charge des alias snake_case et kebab-case (`gerer-outils`, `gerer_outils`, `terminer-reparation`, `terminer_reparation`) dans `AppServiceProvider.php` pour les roles `admin`, `reception` et `caisse_outils`.

### 3. Facturation et Sortie

- **Suppression du prix fixe d'entree :** Le montant fixe d'entree (prise en charge) a ete integralement retire du systeme de calcul et des parametres.
- **Grille tarifaire :** Seuls subsistent le tarif horaire de base (`tarif_horaire`) et le tarif des jours supplementaires (`tarif_jours_supp`).
- Les outils speciaux utilises figurent de maniere detaillee sur le recu de caisse PDF (libelle, reference, tarif horaire, duree chrono et montant total).
- Emission de factures et generation PDF securisee via DomPDF (`FactureController`).
- Generation et verification du billet de sortie (`BilletSortieController`).

### 4. Temps Reel et Notifications

- Diffusion temps reel via Laravel Reverb (`NotificationDispatcher`).
- Notifications stockees de facon persistante en base MySQL (`notifications`) et poussees via WebSocket sur les canaux prives `private-user.{id}`.
- Envoi non bloquant encadre par `try/catch` pour garantir la resilience des transactions HTTP et SQL.

## Configuration des Variables d'Environnement

```env
APP_NAME="Gestion de Garage"
APP_ENV=local
APP_KEY=...
APP_DEBUG=true
APP_URL=http://localhost:8000
FRONTEND_URL="https://mon-garage-gest.vercel.app"

# Configuration CORS
# Le domaine Vercel https://mon-garage-gest.vercel.app (et ses previews) est autorise dans config/cors.php

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=garage_db
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_CONNECTION=reverb

REVERB_APP_ID=...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_HOST="127.0.0.1"
REVERB_PORT=8080
REVERB_SCHEME=http
```

## Batterie de Tests Backend

Une suite de tests automatises (Feature tests) valide l'integrite du calcul de facturation, les outils speciaux et les parametres :

```bash
# Executer l'ensemble des tests
php artisan test

# Executer specifiquement les tests de facturation des outils speciaux
php vendor/phpunit/phpunit/phpunit tests/Feature/FacturationOutilsSpeciauxTest.php
```

Scenarios couverts :

1. Verification de l'absence de `prix_entree` dans les parametres et tarifs.
2. Creation et mise a jour d'un outil avec `est_special` et `prix_par_heure`.
3. Calcul de facturation au chrono avec outil special et verification de l'absence de prise en charge sur la facture et le recu PDF.
4. Facturation distincte par vehicule lorsqu'un outil special est partage entre mecaniciens / reparations avec chronos differents.
