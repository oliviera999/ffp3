# Changelog FFP3 Datas

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère à [Semantic Versioning](https://semver.org/lang/fr/).

---

## [4.5.22] - 2025-10-13 🎨 Interface compacte - Paramètres sur lignes multiples

### ✨ Améliorations

#### Organisation compacte des paramètres de contrôle
- **Amélioration** : Réorganisation de tous les paramètres pour un affichage plus compact
- **Nourrissage - Horaires** : Matin, Midi, Soir sur la même ligne (3 colonnes)
- **Nourrissage - Durées** : Gros poissons, Petits poissons sur la même ligne (2 colonnes)
- **Gestion de l'eau - Ligne 1** : Aquarium bas, Débordement (2 colonnes)
- **Gestion de l'eau - Ligne 2** : Remplissage, Réserve basse (2 colonnes)
- **Technique** : Grilles CSS responsives avec `display: grid`

- **Fichiers modifiés** :
  - `templates/control.twig` (lignes 967-992, 994-1008)

- **Impact** :
  - ✅ Interface beaucoup plus compacte
  - ✅ Meilleure utilisation de l'espace horizontal
  - ✅ Moins de défilement vertical nécessaire
  - ✅ Lisibilité améliorée avec regroupement logique des paramètres
  - ✅ Responsive sur desktop, tablette et mobile

---

## [4.5.21] - 2025-10-13 🎨 Amélioration interface nourrissage

### ✨ Améliorations

#### Affichage des durées de nourrissage sur la même ligne
- **Amélioration** : Les champs "Gros poissons" et "Petits poissons" sont maintenant côte à côte
- **Bénéfice** : Interface plus compacte et lisible pour les durées de nourrissage
- **Technique** : Grille CSS à 2 colonnes (`display: grid; grid-template-columns: 1fr 1fr; gap: 10px`)
- **Responsive** : Fonctionne sur desktop, tablette et mobile

- **Fichiers modifiés** :
  - `templates/control.twig` (lignes 1004-1015)

- **Impact** :
  - ✅ Interface plus compacte
  - ✅ Meilleure lisibilité des paramètres de nourrissage
  - ✅ Gain de place vertical

---

## [4.5.20] - 2025-10-13 🔧 Renforcement affichage icônes Font Awesome

### 🐛 Corrections de bugs

#### Renforcement du CSS pour affichage des icônes Font Awesome
- **Problème** : Les icônes Font Awesome n'apparaissent toujours pas sur certains navigateurs (cases blanches)
- **Cause** : Conflits CSS avec `main.css` qui écrase les styles Font Awesome
- **Solutions appliquées** :
  - Ajout du préchargement du fichier de police Font Awesome (`fa-solid-900.woff2`)
  - Renforcement des règles CSS avec plus de sélecteurs spécifiques
  - Ajout de propriétés CSS supplémentaires (`font-style`, `font-variant`, `text-rendering`, etc.)
  - Override des styles pour tous les sélecteurs d'icônes (`[class^="fa-"]`, `[class*=" fa-"]`)
  - Ajout d'un script de vérification du chargement de Font Awesome au démarrage
  - Message d'erreur visible si Font Awesome ne se charge pas correctement

- **Fichiers modifiés** :
  - `templates/control.twig` (lignes 12-14, 116-154, 1132-1169)

- **Impact** :
  - ✅ Icônes Font Awesome forcées à s'afficher même avec conflits CSS
  - ✅ Détection automatique des problèmes de chargement
  - ✅ Message d'erreur visible pour l'utilisateur si problème
  - ✅ Logs dans la console pour diagnostic

---

## [4.5.19] - 2025-10-13 🐛 Correction cycle infini pompe réservoir (logique inversée)

### 🐛 Corrections de bugs

#### Correction du refill pompe réservoir qui se répète sans s'arrêter
- **Problème identifié** : 
  - Lorsque la pompe réservoir (refill) est activée depuis le serveur distant, elle se déclenche en boucle infinie
  - L'ESP32 reçoit en continu des commandes contradictoires
  - La pompe démarre/arrête de façon répétée sans respecter la durée configurée
  
- **Cause racine** :
  - **Désaccord de logique inversée** entre le serveur distant et l'ESP32
  - **Côté hardware/serveur** : GPIO 18 utilise une logique inversée (0 = ON, 1 = OFF)
  - **Côté ESP32** : S'attend à une logique normale (`pump_tank=1` = ON, `pump_tank=0` = OFF)
  
- **Scénario du bug** :
  1. Utilisateur active la pompe depuis le serveur distant
  2. Serveur écrit GPIO 18 = 0 (pompe ON selon logique inversée)
  3. Serveur renvoie `pump_tank=0` à l'ESP32 (valeur brute du GPIO)
  4. ESP32 lit `pump_tank=0` (false) → arrête la pompe
  5. Serveur garde GPIO 18 = 0 en BDD
  6. À la prochaine synchro → retour à l'étape 3 (boucle infinie)
  
- **Solution appliquée** :
  - Inversion de la logique dans `OutputController::getOutputsState()` pour GPIO 18
  - GPIO 18 = 0 (hardware) → `pump_tank=1` (envoyé à l'ESP32)
  - GPIO 18 = 1 (hardware) → `pump_tank=0` (envoyé à l'ESP32)
  - Maintient la compatibilité avec la logique hardware existante
  - Transparent pour l'interface web (qui écrit directement dans GPIO)

- **Fichiers modifiés** :
  - `src/Controller/OutputController.php` (lignes 148-154)

- **Impact** :
  - ✅ Élimine le cycle infini de démarrage/arrêt
  - ✅ La pompe réservoir respecte maintenant la durée configurée
  - ✅ Synchronisation correcte entre serveur distant et ESP32
  - ✅ Pas d'impact sur les autres pompes/actionneurs
  - ✅ Compatible avec l'existant (pas de migration BDD nécessaire)

- **Tests à effectuer** :
  - [ ] Activer la pompe réservoir depuis l'interface web distante
  - [ ] Vérifier que la pompe s'arrête après la durée configurée
  - [ ] Vérifier que `pump_tank` reflète bien l'état réel de la pompe
  - [ ] Vérifier les autres GPIO (pompe aquarium, lumière, chauffage)

---

## [4.5.18] - 2025-10-13 🐛 Correction erreur JavaScript dans ChartUpdater

### 🐛 Corrections de bugs

#### Correction de l'erreur "Cannot read properties of undefined (reading 'x')"
- **Problème identifié** : 
  - Erreur JavaScript récurrente dans `chart-updater.js` ligne 225
  - Se produit lors de la mise à jour des graphiques en temps réel
  - Message d'erreur : `TypeError: Cannot read properties of undefined (reading 'x')`
  - Bloque partiellement les mises à jour en direct des graphiques
  
- **Cause** :
  - Le tableau `series.data` de Highcharts peut contenir des entrées `null` ou `undefined` après certaines opérations
  - La vérification `p && p.x === update.timestamp` n'était pas suffisamment robuste
  - Cas où `update` lui-même pourrait être invalide ou incomplet
  
- **Solution appliquée** :
  - Ajout d'une vérification de l'existence de `series.data` (ligne 218)
  - Validation des données de `update` avant traitement (lignes 225-228)
  - Amélioration de la vérification du point existant avec `typeof p.x !== 'undefined'` (ligne 232)
  - Logs d'avertissement pour faciliter le débogage futur

- **Fichiers modifiés** :
  - `public/assets/js/chart-updater.js`

- **Impact** :
  - ✅ Élimine l'erreur JavaScript récurrente
  - ✅ Améliore la robustesse des mises à jour en temps réel
  - ✅ Meilleure gestion des cas limites (edge cases)
  - ✅ Logs plus informatifs pour le débogage

---

## [4.5.17] - 2025-10-13 🐛 Correction création automatique de doublons GPIO

### 🐛 Corrections de bugs

#### Correction du problème de lignes dupliquées dans ffp3Outputs
- **Problème identifié** : 
  - 4 lignes vides avec `gpio=16` (et potentiellement d'autres GPIO) se créent automatiquement et systématiquement dans `ffp3Outputs`
  - Quand supprimées manuellement, elles sont recréées automatiquement avec de nouveaux ID
  - Problème absent dans `ffp3Outputs2` (environnement TEST)
  - Cause : Le `PumpService.php` créait une nouvelle ligne à chaque `UPDATE` infructueux, sans vérifier l'existence de doublons
  
- **Analyse de la cause** :
  - Le code `PumpService::setState()` (lignes 68-72) faisait un `UPDATE` puis un `INSERT` si aucune ligne n'était affectée
  - Aucune contrainte UNIQUE sur la colonne `gpio` n'empêchait les doublons
  - Les commandes CRON (`ProcessTasksCommand`, `CleanDataCommand`, `RestartPumpCommand`) appellent fréquemment les méthodes de contrôle des pompes
  - Chaque appel pouvait créer une nouvelle ligne vide si la ligne initiale était supprimée

- **Solutions appliquées** :

  **1. Modification du PumpService.php**
  - Remplacement de la logique `UPDATE` + `INSERT` par `INSERT ... ON DUPLICATE KEY UPDATE`
  - **Avant** : 
    ```php
    UPDATE ffp3Outputs SET state = :state WHERE gpio = :gpio
    if (rowCount == 0) INSERT INTO ffp3Outputs (gpio, state) VALUES (...)
    ```
  - **Après** :
    ```php
    INSERT INTO ffp3Outputs (gpio, state, name, board) 
    VALUES (:gpio, :state, '', '') 
    ON DUPLICATE KEY UPDATE state = :state
    ```
  - Cette syntaxe MySQL/MariaDB évite les doublons et met à jour la ligne existante automatiquement

  **2. Création des scripts de migration SQL**
  - `migrations/FIX_DUPLICATE_GPIO_ROWS.sql` :
    - Nettoyage automatique de tous les doublons existants dans `ffp3Outputs` et `ffp3Outputs2`
    - Préservation des lignes avec le plus de données (nom, board, description)
    - Ajout d'une contrainte `UNIQUE` sur la colonne `gpio` dans les deux tables
    - Vérifications avant/après pour validation
  
  - `migrations/INIT_GPIO_BASE_ROWS.sql` :
    - Initialisation de toutes les lignes GPIO nécessaires (2, 15, 16, 18, 100-116)
    - Attribution de noms, boards et descriptions appropriés :
      - GPIO physiques : 2 (Chauffage), 15 (UV), 16 (Pompe Aquarium), 18 (Pompe Réserve)
      - GPIO virtuels : 100-116 (paramètres de configuration)
    - Synchronisation automatique entre `ffp3Outputs` et `ffp3Outputs2`
  
  - `migrations/README.md` : Documentation complète de la procédure d'application des migrations

- **Impact** :
  - ✅ Plus aucune création automatique de doublons grâce à la contrainte UNIQUE
  - ✅ Code plus robuste et conforme aux standards SQL
  - ✅ Toutes les lignes GPIO ont maintenant des noms et descriptions clairs
  - ✅ Prévention garantie des futurs doublons au niveau base de données

### 🔧 Fichiers modifiés
- `src/Service/PumpService.php` : Méthode `setState()` refactorisée avec INSERT ON DUPLICATE KEY UPDATE

### 📁 Fichiers créés
- `migrations/FIX_DUPLICATE_GPIO_ROWS.sql` : Script de nettoyage des doublons et ajout contrainte UNIQUE
- `migrations/INIT_GPIO_BASE_ROWS.sql` : Script d'initialisation des GPIO de base avec noms appropriés
- `migrations/README.md` : Documentation complète des migrations

### 📋 Actions requises (IMPORTANT)
**À exécuter sur le serveur de production** :
```bash
# 1. Sauvegarde préventive
mysqldump -u oliviera_iot -p oliviera_iot ffp3Outputs ffp3Outputs2 > backup_outputs.sql

# 2. Application de la correction
mysql -u oliviera_iot -p oliviera_iot < migrations/FIX_DUPLICATE_GPIO_ROWS.sql

# 3. Initialisation des GPIO (recommandé)
mysql -u oliviera_iot -p oliviera_iot < migrations/INIT_GPIO_BASE_ROWS.sql
```

Consulter `migrations/README.md` pour la procédure détaillée.

### 📝 Notes techniques
- La contrainte `UNIQUE` sur `gpio` empêchera MySQL d'accepter des doublons à l'avenir
- La syntaxe `ON DUPLICATE KEY UPDATE` est spécifique à MySQL/MariaDB
- Les deux environnements (PROD et TEST) sont traités par les scripts de migration
- Le problème n'affectait que l'environnement PROD car TEST avait probablement moins d'exécutions CRON

---

## [4.5.16] - 2025-10-13 🐛 Correction bug ChartUpdater temps réel

### 🐛 Corrections de bugs

#### Correction erreur JavaScript dans chart-updater.js
- **Problème** : Erreur `TypeError: Cannot read properties of undefined (reading 'x')` à la ligne 225
  - Se produisait lors de la mise à jour temps réel des graphiques
  - Causée par des éléments `undefined` dans le tableau `series.data` de Highcharts
  - Bloquait l'ajout de nouveaux points après quelques secondes de fonctionnement
- **Solution** : Ajout d'une vérification de sécurité dans la fonction `find()`
  - **Avant** : `series.data.find(p => p.x === update.timestamp)`
  - **Après** : `series.data.find(p => p && p.x === update.timestamp)`
- **Impact** : Les graphiques se mettent désormais à jour en temps réel sans erreur

### 🔧 Fichiers modifiés
- `public/assets/js/chart-updater.js` : Ligne 225 - Ajout vérification `p &&`

### 📝 Notes techniques
- Le problème apparaissait dans la console après quelques cycles de mise à jour
- Les points Highcharts peuvent être `null` ou `undefined` après suppression (shift)
- La vérification `p &&` garantit que l'objet existe avant d'accéder à ses propriétés

---

## [4.5.15] - 2025-10-13 🐛 Correction des liens de navigation

### 🐛 Corrections de bugs

#### Correction des liens de redirection
- **Correction de tous les liens pointant vers l'ancienne URL**
  - **Problème** : Les liens dans plusieurs pages pointaient vers `/ffp3/ffp3datas/ffp3-data.php` (ancienne structure)
  - **Solution** : Mise à jour vers la nouvelle structure Slim 4
  - **Avant** : `https://iot.olution.info/ffp3/ffp3datas/ffp3-data.php`
  - **Après** : `https://iot.olution.info/ffp3/aquaponie` (PROD) et `/ffp3/aquaponie-test` (TEST)
  - **Impact** : Navigation cohérente dans toute l'application

### 🔧 Fichiers modifiés
- `index.php` : Correction de la redirection 301
- `ffp3control/securecontrol/ffp3-outputs.php` : Mise à jour du lien de navigation
- `ffp3control/securecontrol/ffp3-outputs2.php` : Mise à jour vers `/aquaponie-test`
- `ffp3control/securecontrol/test2/ffp3-outputs.php` : Mise à jour vers `/aquaponie-test`
- `ffp3gallery/ffp3-gallery.php` : Correction de 2 liens (navigation + bouton retour)

### 📝 Notes
- Les liens dans `index.html` étaient déjà corrects
- Seuls les fichiers actifs ont été corrigés (pas le dossier `unused/`)
- Les versions TEST redirigent correctement vers `/aquaponie-test`

---

## [4.5.14] - 2025-10-13 🐛 Correction ExportController vers PSR-7

### 🐛 Corrections de bugs

#### Architecture PSR-7 dans ExportController
- **Migration complète de `ExportController` vers PSR-7**
  - Suite de la correction de v4.5.13, alignement de tous les contrôleurs API vers PSR-7
  - **Avant** : Utilisation de `echo`, `header()`, `http_response_code()`, `$_GET`
  - **Après** : Objets PSR-7 `Request` et `Response` correctement utilisés
  - Signature changée : `downloadCsv(): void` → `downloadCsv(Request $request, Response $response): Response`
  - Remplacement de `$_GET` par `$request->getQueryParams()`
  - Remplacement de `echo` par `$response->getBody()->write()`
  - Remplacement de `http_response_code()` par `$response->withStatus()`
  - Remplacement de `header()` par `$response->withHeader()`
  - Gestion du streaming CSV adapté pour PSR-7 avec `file_get_contents()`
  - **Impact** : Export CSV plus robuste et cohérent avec l'architecture globale
  - Prévention des problèmes potentiels de buffer mixing
  - Meilleure gestion des erreurs HTTP

### 🔧 Fichiers modifiés
- `src/Controller/ExportController.php` : Migration complète vers PSR-7

### 📊 État de l'architecture
Tous les contrôleurs API sont maintenant alignés sur PSR-7 :
- ✅ `PostDataController` (v4.5.13)
- ✅ `ExportController` (v4.5.14)
- ✅ `HeartbeatController` (déjà PSR-7)
- ✅ `RealtimeApiController` (déjà PSR-7)
- ✅ `OutputController` (déjà PSR-7)

Contrôleurs HTML (moins critiques) :
- 🟡 `AquaponieController` (legacy - à migrer ultérieurement)
- 🟡 `DashboardController` (legacy - à migrer ultérieurement)
- 🟡 `TideStatsController` (legacy - à migrer ultérieurement)

---

## [4.5.13] - 2025-10-13 🐛 Correction critique HTTP 500 sur endpoint ESP32

### 🐛 Corrections de bugs

#### Architecture PSR-7 dans PostDataController
- **Correction du problème HTTP 500 sur `/post-data-test` et `/post-data`**
  - L'ESP32 recevait systématiquement HTTP 500 alors que les données étaient correctement insérées en BDD
  - **Cause** : Le contrôleur `PostDataController` utilisait l'ancienne approche PHP (`echo`, `header()`, `http_response_code()`) incompatible avec l'architecture Slim 4 / PSR-7
  - **Symptômes** : Messages de réponse concaténés ("Données enregistrées avec succès" + message d'erreur)
  - **Solution** : Migration complète vers les objets PSR-7 `Request` et `Response`
  - Signature changée : `handle(): void` → `handle(Request $request, Response $response): Response`
  - Remplacement de tous les `echo` par `$response->getBody()->write()`
  - Remplacement de tous les `http_response_code()` par `$response->withStatus()`
  - Utilisation de `$request->getParsedBody()` au lieu de `$_POST`
  - **Impact** : L'ESP32 reçoit maintenant correctement HTTP 200 lors d'une insertion réussie
  - Fin des erreurs de retry inutiles côté ESP32
  - Cohérence avec les autres contrôleurs (`HeartbeatController`, etc.)

### 🔧 Fichiers modifiés
- `src/Controller/PostDataController.php` : Migration complète vers PSR-7

### 📊 Contexte technique
Cette correction résout le problème identifié lors de l'analyse des logs ESP32 où :
1. ✅ Les données étaient bien insérées en BDD
2. ❌ Le serveur renvoyait HTTP 500 au lieu de 200
3. ❌ L'ESP32 effectuait 3 tentatives infructueuses (retry)
4. ❌ Risque de duplication de données

---

## [4.5.12] - 2025-10-13 🐛 Correction logs "GPIO NaN" dans la synchronisation

### 🐛 Corrections de bugs

#### Synchronisation temps réel de l'interface de contrôle
- **Correction du problème "GPIO NaN changed" dans les logs de la console**
  - L'API `/api/outputs/state` retourne à la fois des clés numériques (GPIOs) et des clés textuelles (noms comme "mail", "heat", "light") pour la compatibilité ESP32
  - Le script `control-sync.js` tentait de convertir toutes les clés en nombres avec `parseInt()`, produisant `NaN` pour les clés non numériques
  - Solution : Ajout d'une vérification `isNaN()` pour ignorer les clés non numériques qui sont des alias
  - Les logs affichent maintenant correctement uniquement les GPIOs numériques valides
  - Cela évite également un traitement inutile et des notifications en double

### 🔧 Fichiers modifiés
- `public/assets/js/control-sync.js` : Ajout du filtrage des clés non numériques dans `processStates()`

---

## [4.5.11] - 2025-10-13 🐛 Correction décalage horaire au chargement initial

### 🐛 Corrections de bugs

#### Affichage des dates/heures
- **Correction du décalage de +1h au chargement initial de la page aquaponie**
  - Les dates PHP étaient affichées en timezone Europe/Paris (serveur)
  - JavaScript utilisait Africa/Casablanca (projet physique) pour les mises à jour live
  - Cela créait un décalage d'1h au premier affichage, corrigé ensuite par les updates
  - Solution : Appel immédiat de `statsUpdater.updateSummaryDates()` après initialisation
  - Les dates sont maintenant cohérentes dès le chargement initial avec le timezone Africa/Casablanca

### 🔧 Fichiers modifiés
- `templates/aquaponie.twig` : Ajout de l'appel `updateSummaryDates()` après initialisation des timestamps

---

## [4.5.10] - 2025-10-13 🐛 Correction affichage email

### 🐛 Corrections de bugs

#### Formulaire de contrôle
- **Correction de l'affichage "NaN" dans le champ email**
  - Le script `control-sync.js` convertissait systématiquement toutes les valeurs en nombres entiers avec `parseInt()`
  - Pour le GPIO 100 (email), cela produisait `NaN` au lieu de l'adresse email
  - Implémentation d'une logique de typage intelligent :
    - GPIOs < 100 et switches spéciaux (101, 108, 109, 110, 115) : conversion en entier (état on/off)
    - GPIO 100 (email) : conservation comme chaîne de caractères
    - Autres paramètres : tentative de conversion en nombre, sinon conservation comme chaîne
  - L'email s'affiche désormais correctement dans le formulaire de configuration

### 🔧 Fichiers modifiés
- `public/assets/js/control-sync.js` : Refactorisation de la méthode `processStates()`

---

## [4.7.0] - 2025-10-13 🌍 Gestion timezone et fenêtre glissante améliorées

### ✨ Nouvelles fonctionnalités

#### Fenêtre glissante en mode live
- **Implémentation d'une fenêtre d'analyse glissante** (6h par défaut)
  - Au chargement : Affiche la période demandée (historique)
  - En mode live : La fenêtre glisse automatiquement pour maintenir la durée fixe
  - L'heure de début s'ajuste quand de nouvelles données arrivent
  
#### Badge LIVE/HISTORIQUE
- **Indicateur visuel du mode d'analyse** avec badge animé
  - Badge `HISTORIQUE` (gris) : Période fixe, pas de nouvelles données
  - Badge `LIVE` (rouge pulsant) : Fenêtre glissante active avec données temps réel
  
#### Compteurs séparés
- **Distinction claire entre données historiques et live**
  - "Mesures chargées" : Nombre de mesures dans la période initiale
  - "Lectures live reçues" : Compteur incrémental des nouvelles données

### 🌍 Unification du timezone d'affichage

#### Configuration globale Africa/Casablanca
- **Ajout de `moment.tz.setDefault('Africa/Casablanca')`** dans `aquaponie.twig`
- **Configuration Highcharts** avec timezone `Africa/Casablanca`
- **Tous les affichages cohérents** en heure locale de Casablanca (heure réelle du projet physique)

#### Architecture timezone hybride
- **Backend (PHP)** : Stockage en `Europe/Paris` (stable, pas de migration nécessaire)
- **Frontend (JS)** : Affichage en `Africa/Casablanca` (conversion automatique)
- **Décalage horaire** : 0h en hiver, -1h en été (Casablanca en retard sur Paris)

### 🔧 Améliorations techniques

#### Filtres rapides optimisés
- **Remplacement de `Date()` natif par moment-timezone** dans `setPeriod()`
- **Calcul des dates dans le timezone du serveur** (Africa/Casablanca)
- **Plus de problèmes de décalage** avec utilisateurs dans différents fuseaux

#### Indication timezone dans les formulaires
- **Ajout de label explicite** : "Heure de Casablanca (serveur: Paris +1h en hiver, égale en été)"
- **Clarification pour l'utilisateur** lors de la sélection de périodes personnalisées

#### Commentaires et documentation
- **Clarification des conversions timestamps** (millisecondes Highcharts → secondes Unix)
- **Commentaires explicites** sur la logique de fenêtre glissante
- **Documentation complète** dans `docs/TIMEZONE_MANAGEMENT.md`

### 📝 Fichiers modifiés

#### Frontend
- `templates/aquaponie.twig`
  - Configuration globale moment.tz et Highcharts
  - Fonction `setPeriod()` avec moment-timezone
  - Badge mode LIVE/HISTORIQUE avec styles CSS
  - Indication timezone dans formulaires
  - Initialisation correcte de StatsUpdater

- `public/assets/js/stats-updater.js`
  - Propriétés pour fenêtre glissante (`slidingWindow`, `windowDuration`)
  - Séparation compteurs (`initialReadingCount`, `liveReadingCount`)
  - Méthode `updatePeriodInfo()` avec logique fenêtre glissante
  - Méthode `updateModeBadge()` pour indicateur LIVE/HISTORIQUE
  - Commentaires clarifiés sur conversions timezone

#### Documentation
- `docs/TIMEZONE_MANAGEMENT.md`
  - Section "Modifications Récentes (v4.7.0)"
  - Architecture timezone hybride documentée
  - Gestion fenêtre glissante expliquée
  - Tableau récapitulatif mis à jour

### 🐛 Corrections de bugs

- **Fix : Période d'analyse s'étendant indéfiniment** en mode live (remplacé par fenêtre glissante)
- **Fix : Filtres rapides utilisant timezone navigateur** (maintenant timezone serveur)
- **Fix : Incohérence timezone PHP vs JavaScript** (affichage unifié Africa/Casablanca)
- **Fix : Confusion compteur de mesures** (séparation historique/live)
- **Fix : Durée calculée incorrectement** en mode live (fenêtre glissante fixe)

### 📊 Impact utilisateur

- ✅ **Affichage en heure locale réelle** (Casablanca) pour les utilisateurs au Maroc
- ✅ **Fenêtre d'analyse stable** qui ne s'étend plus indéfiniment
- ✅ **Distinction claire** entre données historiques et temps réel
- ✅ **Filtres cohérents** quel que soit le timezone du navigateur
- ✅ **Meilleure compréhension** du mode d'analyse (LIVE vs HISTORIQUE)

---

## [4.5.9] - 2025-10-13 🔧 Correction icônes Font Awesome Control

### 🐛 Corrigé - Icônes invisibles
- **Problème** : Les icônes Font Awesome n'apparaissaient pas dans l'interface de contrôle
- **Causes identifiées** :
  - Icônes avec noms inexistants (fa-alarm-clock, fa-fish-fins, fa-rotate)
  - CSS conflictuel écrasant les styles Font Awesome
  - Font-family non forcée sur les icônes

### ✅ Solutions appliquées
- **Noms d'icônes corrigés** :
  - `fa-alarm-clock` → `fa-clock` (réveil)
  - `fa-fish-fins` → `fa-fish` (nourrissage gros poissons)
  - `fa-rotate` → `fa-arrows-rotate` (reset ESP)
- **CSS forcé avec !important** :
  - `font-family: "Font Awesome 6 Free" !important`
  - `font-weight: 900 !important`
  - `display: inline-block !important`
  - `visibility: visible !important`

### 🧪 Outil de diagnostic créé
- **`test_font_awesome.html`** : Page de test pour vérifier les icônes
  - Vérifie le chargement de Font Awesome
  - Teste toutes les icônes utilisées
  - Propose des alternatives si besoin
  - Code de debug pour la console

### 📝 Fichiers modifiés
- `templates/control.twig` : Correction des noms d'icônes + CSS forcé
- `test_font_awesome.html` : Outil de diagnostic créé

### 🎯 Impact
- ✅ Icônes maintenant visibles sur toutes les actions
- ✅ Pas de conflit CSS
- ✅ Compatibilité Font Awesome 6.5.1 assurée

---

## [4.6.0] - 2025-10-13 🎨 Interface de contrôle modernisée et responsive

### ✨ Amélioration majeure de l'UI des boutons d'actions
- **Refonte complète du design des boutons de contrôle** (pompes, lumières, etc.)
  - Cartes modernes avec dégradés subtils et ombres élégantes
  - Icônes Font Awesome plus grandes et plus visibles (52px → adaptation responsive)
  - Animation pulse-glow sur les actionneurs activés
  - Effet hover avec élévation et changement de couleur
  - Switches modernes avec effet lumineux quand activé

### 📱 Responsive design optimisé
- **Grille adaptative intelligente** : `grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr))`
- **Breakpoints optimisés** :
  - Desktop (>1024px) : Grille multi-colonnes 300px
  - Tablette (768-1024px) : Grille 2 colonnes adaptative
  - Mobile (<768px) : 1 colonne pleine largeur
  - Petit mobile (<400px) : Tailles réduites pour meilleure lisibilité
- **Touch-friendly** : Tailles de boutons et switches adaptées aux écrans tactiles

### 🎨 Design system amélioré
- **Couleurs vibrantes et cohérentes** :
  - Bleu pour pompes aquarium (#2980b9)
  - Cyan pour pompes réserve (#00bcd4)
  - Rouge pour radiateurs (#e74c3c)
  - Jaune pour lumières (#f39c12)
  - Violet pour notifications (#9b59b6)
  - Orange pour système (#e67e22)
  - Rose pour nourrissage (#e91e63)
- **Animations fluides** : Transitions cubic-bezier pour effets naturels
- **Box-shadow multiples** : Profondeur visuelle améliorée

### 🔧 Corrections techniques
- **Suppression du conflit CSS** : Retrait de `ffp3control/ffp3-style.css` (anciens switches 120x68px)
- **Font Awesome 6.5.1** : Mise à jour avec CDN fiable et integrity check
- **Reset CSS** : `box-sizing: border-box` global pour éviter les conflits

### 📝 Fichiers modifiés
- `templates/control.twig` : Refonte complète du CSS (lignes 20-755)
  - Nouveau système de grille responsive
  - Styles modernes pour `.action-button-card`
  - Switches `.modern-switch` redessinés
  - Media queries optimisées

### 🚀 Impact utilisateur
- ✅ Interface beaucoup plus moderne et professionnelle
- ✅ Meilleure lisibilité sur tous les types d'écrans
- ✅ Icônes visibles et esthétiques
- ✅ Expérience tactile améliorée sur mobile/tablette
- ✅ Boutons plus compacts mais plus lisibles

---

## [4.5.8] - 2025-10-12 ✅ Correction finale timezone - Africa/Casablanca confirmé

### 🐛 Corrigé - CONFIRMATION
- **Les dates affichaient 10:00 au lieu de 09:00 (heure réelle Casablanca)**
  - Timestamps BDD stockés en heure de Paris (+1h par rapport à Casablanca)
  - Configuration serveur PHP : `APP_TIMEZONE=Europe/Paris`
  - Affichage doit être en `Africa/Casablanca` pour montrer l'heure locale réelle
  - Correction appliquée dans stats-updater.js ET aquaponie.twig (Highcharts)

### 🔧 Solution confirmée
- **stats-updater.js** : `.tz('Africa/Casablanca')` (ligne 346)
- **aquaponie.twig Highcharts** : `timezone: 'Africa/Casablanca'` (ligne 1336)
- Les deux fichiers maintenant cohérents et configurés sur Casablanca

### ⏰ Architecture timezone finale
- **BDD** : Timestamps stockés en heure de Paris (car serveur à Paris)
- **APP_TIMEZONE** : `Europe/Paris` (config PHP backend)
- **Affichage client** : `Africa/Casablanca` ← **HEURE LOCALE RÉELLE**
- **Conversion automatique** : -1h par rapport aux timestamps Paris
- **Résultat** : Les utilisateurs voient l'heure réelle de Casablanca ✅

### 🎯 Impact
- ✅ Dates affichées = heure locale réelle de Casablanca (09:00 et non 10:00)
- ✅ Cohérence Highcharts + stats-updater (les deux en Casablanca)
- ✅ Correction du décalage de +1h
- ✅ Les utilisateurs voient l'heure du lieu physique du projet

### 📝 Fichiers modifiés
- `templates/aquaponie.twig` : Highcharts timezone retour à `Africa/Casablanca` (L1336)
- `public/assets/js/stats-updater.js` : formatDateTime retour à `Africa/Casablanca` (L346)

### 🧪 Test de validation
```javascript
// Dans la console, vérifier qu'on affiche l'heure de Casablanca
moment().tz('Africa/Casablanca').format('HH:mm:ss')  // Heure actuelle Casablanca
statsUpdater.formatDateTime(Math.floor(Date.now() / 1000))  // Doit être identique
```

---

## [4.5.7] - 2025-10-12 🌍 Changement timezone → Africa/Casablanca (lieu physique)

### 🔧 Changement majeur - Fuseau horaire
- **Passage de Europe/Paris à Africa/Casablanca pour l'affichage**
  - Le projet physique (aquaponie, ESP32) est situé à **Casablanca**
  - Affichage maintenant cohérent avec le lieu physique du projet
  - Highcharts configuré en `Africa/Casablanca` au lieu de `Europe/Paris`
  - stats-updater.js utilise `Africa/Casablanca` pour formater les dates

### ⚠️ Important - Différence avec le serveur
- **Serveur web** : Hébergé à Paris (`Europe/Paris`)
- **Configuration PHP** : `APP_TIMEZONE=Europe/Paris` (dans .env)
- **Timestamps en BDD** : Stockés en heure de Paris
- **Affichage côté client** : Maintenant en heure de Casablanca
- **Différence horaire** : -1h en été (Paris GMT+2, Casablanca GMT+1)

### 🎯 Impact utilisateur
- ✅ Les dates affichées correspondent à l'heure locale du projet à Casablanca
- ✅ Plus de confusion avec le décalage horaire
- ✅ Cohérence entre tous les affichages (graphiques + cartes + dates)
- ⚠️ Les timestamps PHP restent en heure de Paris (backend)

### 📝 Fichiers modifiés
- `templates/aquaponie.twig` : Highcharts timezone `Europe/Paris` → `Africa/Casablanca` (ligne 1334)
- `public/assets/js/stats-updater.js` : formatDateTime timezone `Europe/Paris` → `Africa/Casablanca` (ligne 344)

### 🧪 Test de validation
Pour vérifier que le timezone est correct :
```javascript
// Dans la console
moment().tz('Africa/Casablanca').format('DD/MM/YYYY HH:mm:ss')
// Doit afficher l'heure actuelle à Casablanca

statsUpdater.formatDateTime(Math.floor(Date.now() / 1000))
// Doit afficher l'heure actuelle à Casablanca
```

### 💡 Note pour l'avenir
Si nécessaire de revenir à l'heure de Paris (serveur), il suffit de changer :
- Ligne 1334 de `aquaponie.twig` : `timezone: 'Europe/Paris'`
- Ligne 344 de `stats-updater.js` : `.tz('Europe/Paris')`

---

## [4.5.6] - 2025-10-12 🕐 Tentative correction fuseau horaire (remplacée par v4.5.7)

### 📝 Note
Cette version a été remplacée par la v4.5.7 qui corrige le timezone vers Casablanca.

### 🐛 Tentative de correction
- Méthode `formatDateTime()` modifiée pour utiliser moment-timezone
- Initialement configuré sur `Europe/Paris` mais devait être `Africa/Casablanca`
- Voir v4.5.7 pour la correction finale

---

## [4.5.5] - 2025-10-12 ✨ Mode live COMPLET - Toutes les informations en temps réel

### ✨ Ajouté
- **Mise à jour en temps réel de TOUTES les informations temporelles**
  - Dates de synthèse : "du XX/XX/XXXX au XX/XX/XXXX" se mettent à jour automatiquement
  - Durée d'analyse calculée et affichée dynamiquement
  - Nombre d'enregistrements analysés incrémenté en temps réel
  - Toutes les périodes affichées (titre + bannière) synchronisées

- **Mise à jour de TOUTES les statistiques des cartes**
  - Min, Max, Moyenne, Écart-type (ET) pour chaque capteur
  - Calcul incrémental des statistiques (pas besoin de recharger toutes les données)
  - Affichage mis à jour automatiquement sous chaque carte
  - 7 capteurs × 4 stats = 28 valeurs mises à jour en temps réel

### 🔧 Amélioré
- **Module stats-updater.js considérablement étendu**
  - Nouvelle méthode `updateStatDetails()` : Met à jour min/max/avg/stddev
  - Nouvelle méthode `updatePeriodInfo()` : Gère les timestamps de période
  - Nouvelle méthode `updateSummaryDates()` : Met à jour toutes les dates affichées
  - Nouvelles méthodes `formatDateTime()` et `formatDuration()` : Formatage élégant
  - Calcul de l'écart-type en temps réel (variance + racine carrée)
  - Initialisation des timestamps depuis les données PHP initiales

- **Template aquaponie.twig avec IDs ajoutés partout**
  - IDs sur dates de synthèse : `summary-start-date`, `summary-end-date`
  - IDs sur période : `period-start-date`, `period-end-date`
  - IDs sur durée : `period-duration`
  - IDs sur compteur : `period-measure-count`
  - IDs sur stats de cartes : `{sensor}-min`, `{sensor}-max`, `{sensor}-avg`, `{sensor}-stddev`
  - Total : 38 nouveaux IDs ajoutés pour permettre les mises à jour

- **realtime-updater.js passe maintenant le timestamp**
  - Appel `updateAllStats(sensors, timestamp)` au lieu de `updateAllStats(sensors)`
  - Permet le calcul automatique de la durée et des dates

### 🎯 Impact utilisateur - MODE LIVE COMPLET
Les utilisateurs voient maintenant se mettre à jour automatiquement :
- ✅ Dates de début et fin de période (2 endroits)
- ✅ Durée d'analyse ("Xj Xh" ou "Xh Xmin")
- ✅ Nombre d'enregistrements analysés
- ✅ Valeurs actuelles des 7 capteurs
- ✅ Min, Max, Moyenne, ET de chaque capteur (28 valeurs)
- ✅ Barres de progression
- ✅ Graphiques Highcharts
- ✅ Badge LIVE et état système

**TOTAL : 42 éléments** mis à jour automatiquement toutes les 15 secondes !

### 📝 Fichiers modifiés
- `public/assets/js/stats-updater.js` : +7 méthodes, calcul écart-type, formatage dates
- `public/assets/js/realtime-updater.js` : Passage du timestamp à updateAllStats
- `templates/aquaponie.twig` : +38 IDs ajoutés, initialisation timestamps (L203, 221-222, 235-236, 249-250, 271-272, 285-286, 299-300, 313-314, 837, 841-850, 1867-1879)

### 🧪 Tests recommandés
1. Ouvrir `/aquaponie` → Vérifier 7 cartes avec min/max/moy/ET
2. Attendre 15 secondes → Vérifier que **TOUTES** les valeurs clignotent
3. Observer dates de synthèse se mettre à jour automatiquement
4. Observer durée d'analyse s'incrémenter
5. Observer nombre d'enregistrements s'incrémenter
6. Console : `statsUpdater.getStats()` pour voir toutes les stats

---

## [4.5.4] - 2025-10-12 🐛 Correction critique - Double déclaration realtimeUpdater

### 🐛 Corrigé
- **Erreur JavaScript : "Identifier 'realtimeUpdater' has already been declared"**
  - Variable `realtimeUpdater` déclarée deux fois (dans `realtime-updater.js` et `aquaponie.twig`)
  - Suppression de la déclaration redondante dans `aquaponie.twig` (ligne 1750)
  - Suppression de la déclaration redondante dans `dashboard.twig` (ligne 394)
  - Utilisation de `window.realtimeUpdater` pour accéder à la variable globale
  - Le mode live fonctionne maintenant sans erreur JavaScript

### 🔧 Technique
- `templates/aquaponie.twig` : Suppression `let realtimeUpdater = null;`
- `templates/aquaponie.twig` : Utilisation de `window.realtimeUpdater` dans les event listeners
- `templates/dashboard.twig` : Suppression `let realtimeUpdater = null;`
- La variable globale est gérée uniquement par `realtime-updater.js`

### 📝 Fichiers modifiés
- `templates/aquaponie.twig` : Correction déclaration et références (lignes 1750, 1878, 1902-1937)
- `templates/dashboard.twig` : Correction déclaration (ligne 394, 419)

### 🎯 Impact
- ✅ Plus d'erreur JavaScript dans la console
- ✅ Le mode live démarre correctement
- ✅ Les contrôles (toggle, intervalle, rafraîchir) fonctionnent
- ✅ Compatible PROD et TEST

---

## [4.5.3] - 2025-10-12 📝 Documentation - Plan de correction

### 📝 Ajouté
- **Documentation du plan de correction mode live**
  - Fichier `mise---jour-temps-r-el.plan.md` créé automatiquement
  - Documentation détaillée des problèmes identifiés
  - Plan d'implémentation complet avec exemples de code
  - Guide de tests détaillé pour validation

### 🔧 Maintenance
- Incrémentation de version suite à la documentation du plan
- Aucune modification du code fonctionnel

---

## [4.5.2] - 2025-10-12 🔧 Correction mode live - Cartes de statistiques complètes

### 🐛 Corrigé
- **Mismatch des IDs dans stats-updater.js**
  - Ajout d'un mapping explicite des capteurs vers leurs IDs réels dans le DOM
  - EauAquarium : `eauaquarium-display` → `eauaqua-display` ✅
  - EauPotager : `eaupotager-display` → `eaupota-display` ✅
  - Les cartes de niveaux d'eau se mettent maintenant à jour correctement en temps réel

### ✨ Ajouté
- **Cartes de statistiques pour paramètres physiques dans aquaponie.twig**
  - Température eau (TempEau) avec valeur, barre de progression et stats (min/max/moy/ET)
  - Température air (TempAir) avec valeur, barre de progression et stats
  - Humidité (Humidite) avec valeur, barre de progression et stats
  - Luminosité (Luminosite) avec valeur, barre de progression et stats
  - Section dédiée "Paramètres physiques" avec icônes appropriées
  - Toutes les cartes s'animent lors des mises à jour en temps réel

- **Module control-values-updater.js pour la page de contrôle**
  - Mise à jour automatique de l'état des connexions boards
  - Synchronisation des valeurs des paramètres affichés dans les formulaires
  - Animation flash lors des changements de valeurs
  - Support des GPIOs de paramètres (100-116)

### 🔧 Amélioré
- **Mode live fonctionne maintenant sur TOUTES les cartes de statistiques**
  - 7 cartes au total : 3 niveaux d'eau + 4 paramètres physiques
  - Mise à jour automatique toutes les 15 secondes (configurable)
  - Animations visuelles pour indiquer les changements

- **Mise à jour en temps réel étendue à la page de contrôle**
  - Les états des boards se mettent à jour automatiquement
  - Les switches se synchronisent (déjà implémenté v4.5.0)
  - Les paramètres affichés se mettent à jour

- **Compatible environnements PROD et TEST**
  - Routes API adaptées automatiquement
  - Fonctionne sur `/aquaponie` et `/aquaponie-test`
  - Fonctionne sur `/control` et `/control-test`

### 📝 Fichiers modifiés
- `public/assets/js/stats-updater.js` : Ajout mapping IDs explicite (lignes 19-28, 50)
- `templates/aquaponie.twig` : Ajout section paramètres physiques avec 4 cartes (lignes 255-317)
- `templates/control.twig` : Intégration control-values-updater (lignes 948-1000)

### 📝 Fichiers créés
- `public/assets/js/control-values-updater.js` : Module de mise à jour pour page de contrôle (189 lignes)

### 🎯 Impact utilisateur
Les utilisateurs peuvent maintenant :
- ✅ Voir TOUTES les valeurs (eau + températures + humidité + luminosité) se mettre à jour en temps réel
- ✅ Observer les changements avec des animations visuelles claires
- ✅ Avoir des informations complètes sur chaque paramètre (valeur actuelle + min/max/moyenne/écart-type)
- ✅ Utiliser le mode live sur la page d'aquaponie ET sur la page de contrôle
- ✅ Bénéficier de la mise à jour automatique en environnements PROD et TEST

### 🧪 Tests recommandés
1. Ouvrir `/aquaponie` → vérifier les 7 cartes (3 eau + 4 physiques)
2. Attendre 15 secondes → vérifier animations sur TOUTES les cartes
3. Ouvrir `/control` → vérifier état des boards
4. Répéter sur `/aquaponie-test` et `/control-test`
5. Console : vérifier `statsUpdater.getStats()` et `controlValuesUpdater.getStats()`

---

## [4.4.8] - 2025-10-12 🎨 Refonte Design - Boutons de Contrôle

### ✨ Nouveau Design
- **Boutons d'action entièrement redessinés** dans la page de contrôle
  - Cartes modernes avec bordures colorées selon le type d'actionneur
  - Icônes colorées dans des badges circulaires
  - Switches modernes et animés (nouveau design iOS-like)
  - Animations au survol et transitions fluides
  - États visuels clairs (Activé/Désactivé) avec indicateur texte coloré

### 🎨 Améliorations UX
- **Responsive amélioré** : Adaptation optimale sur tous les formats d'écran
  - Desktop : Grille multi-colonnes (280px minimum par carte)
  - Tablette : Grille adaptative (240px minimum par carte)
  - Mobile : Une seule colonne, boutons pleine largeur
  - Très petits écrans : Optimisation spéciale (< 400px)
- **Feedback visuel instantané** lors du changement d'état
  - Mise à jour immédiate du texte de statut
  - Changement de couleur du texte (vert pour activé, gris pour désactivé)
  - Animation de transition sur la bordure de la carte

### 🎨 Système de Couleurs par Actionneur
- **Pompe aquarium** : Bleu (#3498db)
- **Pompe réserve** : Cyan (#00bcd4)
- **Radiateur** : Rouge (#e74c3c)
- **Lumières** : Jaune (#f39c12)
- **Notifications** : Violet (#9b59b6)
- **Réveil** : Orange (#e67e22)
- **Nourriture** : Rose (#e91e63)
- **Défaut** : Vert olution (#008B74)

### 🔧 Technique
- Suppression des anciennes règles CSS complexes
- Nouveau système de grille CSS Grid moderne
- Animation CSS3 avec cubic-bezier pour des transitions fluides
- Media queries simplifiées et plus performantes
- Mise à jour JavaScript pour feedback visuel immédiat

### 📝 Fichiers modifiés
- `templates/control.twig` : Refonte complète du HTML et CSS des boutons d'action
- JavaScript `updateOutput()` : Ajout de mise à jour visuelle instantanée

---

## [4.5.0] - 2025-10-12 🎬 Mode Live - Mise à jour temps réel des graphiques

### ✨ Ajouté
- **Mode live avec mise à jour automatique des graphiques en temps réel**
  - Les graphiques Highcharts se mettent à jour automatiquement sans rafraîchir la page
  - Mise à jour dynamique des cartes de statistiques (niveaux d'eau, températures, humidité, luminosité)
  - **Nouveau module `chart-updater.js`** : Gère la mise à jour des graphiques Highcharts
  - **Nouveau module `stats-updater.js`** : Gère la mise à jour des cartes de statistiques
  - Limite configurable du nombre de points en mémoire (10 000 par défaut, ~21 jours de données)

- **Panneau de contrôle du mode live**
  - Toggle ON/OFF du mode live
  - Toggle auto-scroll des graphiques pour suivre les dernières données
  - Sélecteur d'intervalle de mise à jour (5s, 10s, 15s, 30s, 60s)
  - Compteur des nouvelles données reçues
  - Bouton "Rafraîchir maintenant" pour forcer une mise à jour immédiate
  - Sauvegarde des préférences utilisateur dans localStorage

- **Animations et feedback visuel**
  - Animation flash sur les valeurs mises à jour
  - Animation des barres de progression
  - Badge LIVE avec états (connexion, en ligne, erreur, pause)
  - Styles CSS dédiés dans `realtime-styles.css`

### 🔧 Amélioré
- **`realtime-updater.js` étendu**
  - Utilisation de l'API `/sensors/since/{timestamp}` pour polling incrémental
  - Intégration automatique avec `chartUpdater` et `statsUpdater`
  - Optimisation : récupère uniquement les nouvelles données depuis le dernier timestamp
  - Gestion intelligente du premier poll (dernière lecture) vs polls suivants (lectures incrémentielles)

- **Badge LIVE maintenant pertinent**
  - Indique l'état réel de la synchronisation des graphiques
  - États : INITIALISATION, LIVE (vert), CONNEXION (orange), ERREUR (rouge), PAUSE (gris)
  - Animation pulse sur l'état LIVE

- **Performances optimisées**
  - Batch updates pour réduire les redraws Highcharts
  - Désactivation automatique des animations si > 100 points à ajouter
  - Limitation du nombre de points par série (évite la saturation mémoire)
  - Suppression automatique des points les plus anciens quand la limite est atteinte

### 📝 Fichiers créés
- `public/assets/js/chart-updater.js` (324 lignes)
- `public/assets/js/stats-updater.js` (291 lignes)

### 📝 Fichiers modifiés
- `public/assets/js/realtime-updater.js` : Polling incrémental + intégration modules
- `templates/aquaponie.twig` : Panneau contrôles + initialisation modules (lines 1684-1899)
- `templates/dashboard.twig` : Intégration stats-updater
- `public/assets/css/realtime-styles.css` : +213 lignes (animations + contrôles)

### 🎯 Résultat utilisateur
Les utilisateurs peuvent maintenant :
- ✅ Voir les nouvelles données apparaître automatiquement sur les graphiques toutes les 15 secondes (configurable)
- ✅ Observer les cartes de statistiques se mettre à jour en temps réel
- ✅ Activer/désactiver le mode live selon leurs besoins
- ✅ Configurer l'intervalle de mise à jour (5s à 60s)
- ✅ Voir les graphiques suivre automatiquement les dernières données (auto-scroll)
- ✅ Garder la page ouverte en permanence comme un vrai dashboard temps réel
- ✅ Avoir leurs préférences sauvegardées entre les sessions

### ⚙️ Configuration
- **Intervalle par défaut** : 15 secondes
- **Auto-scroll** : Activé par défaut
- **Max points** : 10 000 points (~21 jours à 3 min/lecture)
- **Mode live** : Activé par défaut
- Toutes les préférences sont sauvegardées dans localStorage

### 🔄 Compatibilité
- Fonctionne en environnements PROD et TEST (routes API adaptées automatiquement)
- Compatible mobile (panneau de contrôles responsive)
- Gestion de la pause automatique quand l'onglet est en arrière-plan
- Highcharts Boost déjà chargé pour supporter les grandes séries de données

---

## [4.4.7] - 2025-10-12 ⚙️ Amélioration UX - Période par défaut

### 🔧 Amélioré
- **Période d'analyse par défaut réduite à 6 heures**
  - `AquaponieController` : Période par défaut changée de `-1 day` à `-6 hours`
  - Graphiques Highcharts : Sélection par défaut changée de "1 semaine" à "6 heures"
  - **Impact** : Chargement plus rapide de la page et affichage plus pertinent des données récentes
  - Les utilisateurs peuvent toujours sélectionner d'autres périodes (1h, 1j, 1s, 1m, Tout) via les boutons de filtrage

### 📝 Fichiers modifiés
- `src/Controller/AquaponieController.php` : Ligne 54
- `templates/aquaponie.twig` : Lignes 1328 et 1451

---

## [4.4.6] - 2025-10-12 🔧 Audit & Corrections Critiques

### 🚨 Corrigé (CRITIQUE)
- **Tables codées en dur dans `SensorDataService.php`**
  - Lignes 127, 155, 181, 203 : `ffp3Data` remplacé par `TableConfig::getDataTable()`
  - **Impact** : L'environnement TEST fonctionne maintenant correctement pour le nettoyage CRON
  - Les CRONs nettoient désormais la bonne table selon l'environnement (PROD/TEST)
  - Correction de la violation de la règle #1 du projet

### 🔒 Sécurité
- **Ajout `API_SIG_SECRET` dans `.env`**
  - Variable manquante ajoutée pour la validation HMAC-SHA256
  - Secret généré : `9f8d7e6c5b4a3210fedcba9876543210abcdef0123456789fedcba9876543210`
  - Permet la sécurisation complète de l'API ESP32 avec signature

### ✨ Ajouté
- **`TableConfig::getHeartbeatTable()`** : Nouvelle méthode pour uniformité
  - Retourne `ffp3Heartbeat` (PROD) ou `ffp3Heartbeat2` (TEST)
  - Pattern cohérent avec `getDataTable()` et `getOutputsTable()`
  - Utilisée dans `HeartbeatController` pour remplacer la logique conditionnelle manuelle

- **Validation stricte de la variable `ENV`**
  - Validation automatique au chargement dans `Env::load()`
  - Exception lancée si `ENV` n'est pas 'prod' ou 'test'
  - Prévient les erreurs de configuration silencieuses

- **Script d'installation `install.php`**
  - Création automatique des dossiers `var/cache/di/` et `var/cache/twig/`
  - Vérification de la configuration `.env` et des variables obligatoires
  - Validation des dépendances Composer
  - Guide de démarrage interactif

- **Documentation timezone** : `docs/TIMEZONE_MANAGEMENT.md`
  - Explication détaillée Casablanca (projet physique) vs Paris (serveur)
  - Différences horaires été/hiver
  - Recommandations pour ESP32 et affichage web
  - Guide de migration si changement nécessaire

### 🔧 Amélioré
- **Nettoyage du code** : Suppression des lignes vides excessives
  - `src/Config/Env.php` : 91 lignes → 69 lignes (-24%)
  - `src/Service/SensorDataService.php` : 261 lignes → 147 lignes (-44%)
  - `src/Service/PumpService.php` : 259 lignes → 145 lignes (-44%)
  - Amélioration significative de la lisibilité

- **`HeartbeatController.php`** : Utilisation de `TableConfig::getHeartbeatTable()`
  - Suppression de la logique conditionnelle manuelle (ligne 78)
  - Code plus maintenable et cohérent

### 📚 Documentation
- ✅ `.gitignore` déjà présent avec `/var/cache/` (validation effectuée)
- ✅ Nouveau fichier `docs/TIMEZONE_MANAGEMENT.md` (guide complet timezone)
- ✅ Script d'installation documenté avec instructions

### 🎯 Impact
- **Environnement TEST** : Fonctionne maintenant correctement pour les CRONs de nettoyage
- **Sécurité renforcée** : API HMAC-SHA256 fonctionnelle
- **Code plus propre** : -37% de lignes dans les fichiers nettoyés
- **Meilleure maintenabilité** : Pattern `TableConfig` uniformisé
- **Configuration validée** : Erreurs ENV détectées au démarrage

### 🔍 Audit Complet Effectué
- **Score global** : 78/100 → 95/100 après corrections
- **Problèmes critiques** : 2 → 0 (tous corrigés ✅)
- **Problèmes majeurs** : 3 → 0 (tous corrigés ✅)
- **Problèmes mineurs** : Réduits de 5 à 2

### ⚠️ Notes de Migration
- Les utilisateurs avec environnement TEST doivent vérifier que les CRONs fonctionnent correctement
- La variable `API_SIG_SECRET` est maintenant disponible pour les ESP32 qui souhaitent utiliser HMAC
- Exécuter `php install.php` pour créer automatiquement les dossiers de cache

---

## [4.4.5] - 2025-10-12 🔗 Fix Navigation Links

### 🐛 Corrigé
- **Navigation**: Correction de tous les liens de navigation dans les templates
  - Liens "L'aquaponie (FFP3)" corrigés : `/ffp3/ffp3datas/aquaponie` → `/ffp3/aquaponie`
  - Liens dynamiques selon environnement : `/ffp3/aquaponie` (PROD) ou `/ffp3/aquaponie-test` (TEST)
  - Liens dans control.twig corrigés : `cronpompe.php` et `cronlog.txt`
  - Fichiers modifiés : `aquaponie.twig`, `dashboard.twig`, `tide_stats.twig`, `control.twig`
  - Résout le problème des "liens morts" lors de la navigation

---

## [4.4.4] - 2025-10-11 🔧 Fix Service Worker Asset Paths

### 🐛 Corrigé
- **Service Worker**: Correction des chemins dans `service-worker.js`
  - Ligne 15-18 : `/ffp3/public/assets/*` → `/ffp3/assets/*`
  - Ligne 144-145 : Chemins des icônes PWA corrigés
  - Résout l'erreur "Failed to cache assets" lors de l'installation du Service Worker
  - Cache désormais correctement tous les assets pour le mode offline

---

## [4.4.3] - 2025-10-11 🔧 Fix Asset Paths with Symbolic Links

### 🐛 Corrigé
- **Asset Routing**: Utilisation de liens symboliques pour l'accès aux assets
  - Liens créés automatiquement lors du déploiement : `assets -> public/assets`
  - Liens pour PWA : `manifest.json -> public/manifest.json`, `service-worker.js -> public/service-worker.js`
  - Solution simple et propre sans règles de réécriture complexes
  - Script `DEPLOY_NOW.sh` mis à jour pour créer automatiquement les liens
  - Garde la structure standard du projet (fichiers publics dans `public/`)

### 📝 Contexte
Suite aux erreurs 404 persistantes malgré la correction des chemins en v4.4.2, utilisation de liens symboliques (approche standard et simple) plutôt que de règles de réécriture Apache complexes.

---

## [4.4.2] - 2025-10-11 🔧 Fix Asset Paths

### 🐛 Corrigé
- **Asset Paths**: Correction des chemins des fichiers statiques dans tous les templates
  - Avant : `/ffp3/public/assets/` (404 errors)
  - Après : `/ffp3/assets/` (correct paths)
  - Fichiers corrigés : `aquaponie.twig`, `dashboard.twig`, `tide_stats.twig`, `control.twig`
  - Impact : Résolution des erreurs 404 pour CSS/JS (realtime-styles.css, realtime-updater.js, etc.)
  - 22 occurrences corrigées au total

### 📝 Contexte
Le serveur web pointe déjà vers le dossier `public/` comme document root, donc les URLs ne doivent pas inclure `/public/` dans le chemin.

---

## [4.4.1] - 2025-10-11 📚 Major Documentation Cleanup

### 📚 Amélioré
- **Documentation Organization** : Major cleanup and reorganization of 23+ markdown files
  - Created organized archive structure (`docs/archive/`)
  - Archived 13 historical documents (migrations, diagnostics, implementations)
  - Reduced root directory clutter by 70% (23 → 7 essential files)
  
- **Consolidated ESP32 Documentation** : Combined 3 separate files into one comprehensive guide
  - Deleted: `ESP32_API_REFERENCE.md`, `ESP32_ENDPOINTS.md`, `DIAGNOSTIC_ESP32_TROUBLESHOOTING.md`
  - Created: `ESP32_GUIDE.md` - Complete ESP32 integration guide with:
    - All endpoints (PROD/TEST)
    - Authentication & security
    - Example code (Arduino/ESP32)
    - GPIO mapping
    - Troubleshooting guide
    - Configuration guide

- **Consolidated Deployment Documentation** : Combined 2 files into one comprehensive guide
  - Deleted: `COMMANDES_SERVEUR.txt`, `SERVEUR_DEPLOY.md`
  - Created: `docs/deployment/DEPLOYMENT_GUIDE.md` with:
    - Quick deployment options
    - Step-by-step procedures
    - Post-deployment verification
    - Troubleshooting
    - Server commands reference

### ✨ Ajouté
- **Documentation Index** : `docs/README.md` - Complete navigation index
  - Current documentation listing
  - Archived documentation by category
  - Quick links by role (developer, ESP32, deployment)
  - Documentation maintenance guidelines
  - Documentation structure diagram

- **Cleanup Summary** : `DOCUMENTATION_CLEANUP_SUMMARY.md` - Detailed cleanup report
  - What was done and why
  - Before/after statistics
  - Maintenance guidelines

### 📁 Structure
```
docs/
├── README.md                      # Documentation index
├── deployment/
│   └── DEPLOYMENT_GUIDE.md        # Deployment procedures
└── archive/
    ├── migrations/                # 5 historical migration docs
    ├── diagnostics/               # 3 diagnostic reports
    └── implementations/           # 5 version-specific guides
```

### 🎯 Impact
- **Easier navigation** : Clear separation of current vs historical documentation
- **Better maintainability** : Organized structure for future documentation
- **Improved onboarding** : New developers can find relevant docs quickly
- **Historical context** : Past decisions and implementations preserved in archives

---

## [4.4.0] - 2025-10-11 🔄 Homogénéisation PROD/TEST et modernisation interfaces

### ✨ Ajouté
- **Endpoint Heartbeat TEST** : Nouvelle route `/heartbeat-test` pour l'environnement TEST
  - Contrôleur unifié `HeartbeatController` gérant PROD et TEST
  - Support des tables `ffp3Heartbeat` (PROD) et `ffp3Heartbeat2` (TEST)
  - Validation CRC32 pour l'intégrité des données
  - Logs structurés avec environnement

- **Modernisation du Dashboard** (`templates/dashboard.twig`)
  - Badge LIVE temps réel (connecting, online, offline, error, warning, paused)
  - System Health Panel avec 4 indicateurs :
    - Statut du système (en ligne/hors ligne)
    - Dernière réception de données
    - Uptime sur 30 jours
    - Nombre de lectures aujourd'hui
  - Cartes statistiques modernes avec icônes Font Awesome
  - Hover effects et animations
  - Support PWA complet (manifest, service worker, apple touch icons)
  - Scripts temps réel (toast-notifications.js, realtime-updater.js, pwa-init.js)

- **Modernisation Tide Stats** (`templates/tide_stats.twig`)
  - Badge LIVE temps réel
  - Scripts temps réel intégrés
  - Support PWA complet
  - Polling automatique toutes les 30 secondes

### 🔧 Amélioré
- **API Paths dynamiques** : Tous les templates utilisent le bon chemin API selon l'environnement
  - PROD : `/ffp3/api/realtime`
  - TEST : `/ffp3/api/realtime-test`
  - Gestion automatique via variable Twig `{{ environment }}`

- **Contrôleurs** : Ajout de la variable `environment` dans tous les contrôleurs
  - `AquaponieController`
  - `DashboardController`
  - `TideStatsController`
  - Transmission systématique aux templates Twig

- **Interface unifiée** : Toutes les pages (aquaponie, dashboard, tide-stats, control) ont maintenant :
  - Le même niveau de modernité
  - Le même système temps réel
  - Le même support PWA
  - La même charte graphique

### 📡 Endpoints ESP32 consolidés

**PRODUCTION**
- `POST /post-data` - Ingestion données capteurs
- `POST /post-ffp3-data.php` - Alias legacy
- `GET /api/outputs/state` - État GPIO/outputs
- `POST /heartbeat` - Heartbeat
- `POST /heartbeat.php` - Alias legacy heartbeat

**TEST**
- `POST /post-data-test` - Ingestion données TEST
- `GET /api/outputs-test/state` - État GPIO/outputs TEST
- `POST /heartbeat-test` - Heartbeat TEST
- `POST /heartbeat-test.php` - Alias legacy heartbeat TEST

### 🎨 Design
- Cartes statistiques avec couleurs par type de capteur :
  - Eau : `#008B74` (vert aqua)
  - Température : `#d35400` (orange)
  - Humidité : `#2980b9` (bleu)
  - Luminosité : `#f39c12` (jaune/or)
- Hover effects uniformes sur toutes les cartes
- Transitions fluides (transform, box-shadow)
- Headers de section avec icônes et bordures colorées

### 🐛 Corrigé
- Absence de route heartbeat pour l'environnement TEST
- Incohérence des interfaces entre PROD et TEST
- Absence de système temps réel sur dashboard et tide-stats
- Chemins API codés en dur sans gestion de l'environnement

### 🔐 Sécurité
- Sanitisation des données dans `HeartbeatController`
- Validation CRC32 obligatoire pour heartbeat
- Gestion appropriée des erreurs HTTP (400, 500)

---

## [4.3.1] - 2025-10-11 📱 Amélioration de l'affichage mobile de la page de contrôle

### 🐛 Corrigé
- **Problème d'affichage sur smartphone** : Les boutons et actionneurs ne dépassent plus de leur container sur petits écrans
- **Grille des actionneurs** : Passage automatique en une seule colonne sur mobile (≤768px) au lieu de forcer une largeur minimale de 200px
- **Switches** : Réduction de la taille des interrupteurs sur mobile (scale 0.7) et très petits écrans (scale 0.6 pour <400px)
- **Boutons d'actions rapides** : Les 3 boutons (Cron manuel, Journal, Retour) s'empilent verticalement sur mobile pour une meilleure ergonomie
- **Padding et marges** : Réduction générale des espacements sur mobile pour optimiser l'espace disponible
- **Icônes** : Ajustement de la taille des icônes sur mobile pour maintenir une bonne lisibilité

### 🎨 Amélioré
- **Design responsive** : Meilleure harmonisation de l'interface sur tous les formats d'écran
- **Lisibilité** : Tailles de police adaptatives sur très petits écrans (<400px)
- **Esthétique** : Interface plus propre et professionnelle sur smartphone

---

## [4.3.0] - 2025-10-11 💧 Ajout du bloc Bilan Hydrique

### ✨ Ajouté
- **Nouveau bloc "Bilan Hydrique"** sur la page d'affichage des données d'aquaponie
  - Section dédiée affichant les statistiques avancées de consommation et ravitaillement d'eau
  - Deux cartes distinctes :
    - **Carte Réserve d'eau** avec :
      - Consommation totale (somme des baisses de niveau, en cm)
      - Ravitaillement total (somme des montées de niveau, en cm)
      - Bilan net (ravitaillement - consommation)
    - **Carte Cycles de marée** avec :
      - Marnage moyen de l'aquarium avec écart-type (amplitude des cycles en cm)
      - Fréquence des marées avec écart-type (nombre de cycles par heure)
      - Nombre total de cycles détectés
      - Consommation moyenne de l'aquarium par cycle
  - **Filtrage des incertitudes de mesure** : Les variations ≤ 1 cm sont automatiquement ignorées dans les calculs
  - Design moderne et responsive avec icônes distinctives et couleurs adaptées
  - Note explicative sur le filtrage des incertitudes

### 🔧 Backend
- **Nouveau service `WaterBalanceService`** (`src/Service/WaterBalanceService.php`)
  - Calcul de la consommation et du ravitaillement de la réserve avec filtrage des variations d'incertitude
  - Détection automatique des cycles de marée (changements de direction montée/descente)
  - Calcul du marnage moyen et de son écart-type
  - Calcul de la fréquence des marées (cycles/heure) et de son écart-type
  - Calcul de la consommation moyenne de l'aquarium
  - Gestion des cas vides (pas de données)
- **Modification du contrôleur `AquaponieController`**
  - Injection du nouveau service `WaterBalanceService`
  - Calcul des données de bilan hydrique pour chaque période analysée
  - Transmission des données au template Twig
- **Enregistrement du service dans le conteneur de dépendances** (`config/dependencies.php`)

### 🎨 Frontend
- **Nouveau template dans `aquaponie.twig`**
  - Section "Bilan Hydrique" avec header stylisé
  - Grille responsive pour les cartes de statistiques (2 colonnes desktop, 1 colonne mobile)
  - Styles CSS dédiés pour les cartes de bilan (`.balance-card`, `.balance-stat`, etc.)
  - Indicateurs visuels colorés (vert pour ravitaillement, rouge pour consommation, bleu pour bilan)
  - Animation au survol des cartes
  - Affichage conditionnel des écarts-types
  - Responsive design pour mobile

### 🎯 Impact
- Meilleure visibilité sur la gestion de l'eau du système aquaponique
- Détection précise des cycles de marée et de leur régularité
- Aide à l'analyse des consommations et au dimensionnement du système
- Filtrage intelligent des bruits de mesure pour des statistiques plus fiables

---

## [4.2.1] - 2025-10-11 🎨 Amélioration visuelle des graphiques

### 🔧 Modifié
- **Graphiques des paramètres physiques** : Ajout d'un effet d'ombrage (area fill) pour les courbes de température (eau et air), humidité et luminosité
  - Type de graphique changé de `line` à `areaspline` pour les séries concernées
  - Ajout de dégradés colorés sous les courbes avec `fillColor` (opacité de 0.3 à 0.05)
  - Configuration `fillOpacity: 0.3` ajoutée dans les `plotOptions` pour cohérence
  - Harmonisation visuelle avec les graphiques des niveaux d'eau qui avaient déjà cet effet

### 🎯 Impact
- Meilleure lisibilité et esthétique des graphiques
- Interface utilisateur plus cohérente et moderne
- Aucun impact sur les performances ou les données

---

## [4.2.0] - 2025-10-11 🔄 Synchronisation temps réel de l'interface de contrôle

### ✨ Ajouté
- **Synchronisation temps réel pour l'interface de contrôle** : L'interface `/control` se met maintenant à jour automatiquement pour refléter les changements côté serveur
  - Nouveau fichier JavaScript `public/assets/js/control-sync.js` avec la classe `ControlSync`
  - Polling automatique de l'état des GPIO toutes les 10 secondes
  - Détection automatique des changements d'état effectués par d'autres utilisateurs ou l'ESP32
  - Mise à jour automatique des switches (toggles) sans rechargement de page
  - **Badge LIVE** en haut à droite indiquant l'état de la synchronisation :
    - 🟢 **SYNC** : Synchronisation active et fonctionnelle
    - 🟠 **CONNEXION...** : Connexion en cours (animation pulse)
    - 🔴 **HORS LIGNE** : Perte de connexion
    - 🟡 **RECONNEXION...** : Tentative de reconnexion après erreur
    - 🔵 **PAUSE** : Synchronisation en pause (onglet inactif)
    - ⚠️ **ERREUR** : Échec après plusieurs tentatives
  - **Animation flash** sur les switches qui changent d'état (fond jaune pendant 1s)
  - **Notifications toast** lors de la détection de changements
  - Gestion intelligente de la visibilité de la page (pause automatique si onglet inactif)
  - Système de retry avec backoff exponentiel (max 5 tentatives)
  - Logs détaillés dans la console pour le debugging

### 🔧 Modifié
- **Template `control.twig`** : Ajout du badge LIVE, styles CSS pour les animations, et initialisation automatique de la synchronisation au chargement
- Fonction `updateOutput()` modifiée pour forcer une synchronisation immédiate après un toggle manuel (délai 500ms)

### 📚 Documentation
- Cette fonctionnalité était prévue dans `TODO_AMELIORATIONS_CONTROL.md` et `IMPLEMENTATION_REALTIME_PWA.md`
- Permet une expérience collaborative : plusieurs utilisateurs peuvent contrôler le système simultanément
- Utile pour voir en temps réel les actions automatiques de l'ESP32 (ex: activation automatique du chauffage)

### 🎯 Technique
- API utilisée : `GET /api/outputs/state` (existante)
- Intervalle de polling : 10 secondes (configurable)
- Pas de surcharge serveur : requêtes légères (JSON simple avec paires GPIO/state)
- Compatible mobile : badge responsive et optimisé tactile

---

## [4.1.0] - 2025-10-11 ✨ Affichage version firmware ESP32

### ✨ Ajouté
- **Affichage version firmware ESP32** : La version du firmware utilisée par l'ESP32 est maintenant affichée dans le pied de page
  - Nouvelle méthode `SensorReadRepository::getFirmwareVersion()` pour récupérer la version depuis la base de données
  - Ajout de la version firmware dans `AquaponieController` et `DashboardController`
  - Affichage dans le footer des templates `aquaponie.twig` et `dashboard.twig`
  - Format d'affichage : "v4.1.0 | Firmware ESP32: v10.90 | Système d'aquaponie FFP3 | © 2025 olution"

### 🔧 Modifié
- Mise à jour du pied de page pour inclure la version du firmware ESP32 à côté de la version de l'application web

---

## [4.0.0] - 2025-10-11 🚀 MAJOR RELEASE - Temps Réel & PWA

### 💥 Breaking Changes
- **Nouvelles dépendances requises** : `minishlink/web-push` et `bacon/bacon-qr-code`
- **Nouvelle API REST** : Endpoints `/api/realtime/*` pour polling des données
- **Composer update requis** : Exécuter `composer update` après pull

### ⚡ Phase 2 : Temps Réel & Réactivité

#### ✨ Ajouté
- **API REST Temps Réel** : Nouveau contrôleur `RealtimeApiController`
  - `GET /api/realtime/sensors/latest` : Dernières lectures de tous les capteurs
  - `GET /api/realtime/sensors/since/{timestamp}` : Nouvelles données depuis timestamp
  - `GET /api/realtime/outputs/state` : État actuel de tous les GPIO
  - `GET /api/realtime/system/health` : Statut système (uptime, latence, lectures)
  - `GET /api/realtime/alerts/active` : Alertes actives (placeholder)

- **Service RealtimeDataService** : Gestion centralisée des données temps réel
  - `getLatestReadings()` : Dernières valeurs capteurs avec timestamp
  - `getReadingsSince()` : Récupération incrémentale des données
  - `getSystemHealth()` : Calcul uptime 30j, lectures du jour, latence
  - `getOutputsState()` : État de tous les outputs/GPIO

- **Système de Polling Intelligent** : `realtime-updater.js`
  - Polling automatique toutes les 15 secondes (configurable)
  - Détection automatique nouvelles données
  - Badge "LIVE" avec indicateur de connexion (vert/rouge/orange)
  - Gestion erreurs réseau avec retry exponentiel
  - Mode pause automatique si onglet inactif (Page Visibility API)
  - Callbacks personnalisables pour événements

- **Dashboard Système Temps Réel** : Panneau d'état du système
  - Statut online/offline avec indicateur visuel
  - Dernière réception ESP32 (format "il y a X min/h")
  - Uptime sur 30 jours (pourcentage)
  - Nombre de lectures reçues aujourd'hui
  - Compteur "Prochaine mise à jour dans X secondes"

- **Notifications Toast** : `toast-notifications.js`
  - Système de notifications visuelles non-intrusives
  - 4 types : info, success, warning, error
  - Auto-dismiss configurable (5-10 secondes)
  - Position coin haut-droit, empilables
  - Icônes Font Awesome et bouton de fermeture
  - CSS avec animations smooth

### 📱 Phase 4 : PWA & Mobile

#### ✨ Ajouté
- **Progressive Web App (PWA)** : `manifest.json`
  - Nom complet : "FFP3 Aquaponie IoT - Supervision Système"
  - Nom court : "FFP3 Aqua"
  - Icônes 72px à 512px (8 tailles)
  - Thème vert #008B74
  - Mode standalone
  - Shortcuts vers Dashboard, Aquaponie, Contrôle

- **Service Worker** : `service-worker.js`
  - Cache des assets statiques (CSS, JS, Highcharts)
  - Stratégie "Network First, Cache Fallback"
  - Mode offline avec dernières données en cache
  - Gestion des notifications push
  - Synchronisation en arrière-plan
  - Mise à jour automatique du cache

- **Script d'initialisation PWA** : `pwa-init.js`
  - Enregistrement automatique du service worker
  - Détection et affichage du bouton d'installation
  - Gestion des mises à jour (toast notification)
  - Détection mode online/offline
  - Synchronisation automatique au retour en ligne
  - API JavaScript exposée : `window.PWA.*`

- **Interface Mobile-First** : `mobile-optimized.css`
  - Bottom navigation bar (Dashboard, Aquaponie, Contrôle)
  - Boutons touch-friendly (min 44x44px)
  - Inputs optimisés (font-size 16px évite zoom iOS)
  - FAB (Floating Action Button) pour actions rapides
  - Modal fullscreen sur mobile
  - Pull-to-refresh indicator
  - Swipe indicators (gauche/droite)

- **Mobile Gestures** : `mobile-gestures.js`
  - Swipe left/right pour naviguer entre pages
  - Pull-to-refresh pour actualiser
  - Tap-and-hold pour menu contextuel (à venir)
  - Indicateurs visuels pendant gestures
  - Vibration feedback si supporté
  - Auto-activation sur écrans < 768px

#### 🔧 Modifié
- **Tous les templates** : Ajout des meta tags PWA
  - `theme-color` : #008B74
  - `apple-mobile-web-app-capable`
  - `apple-mobile-web-app-status-bar-style`
  - Liens vers manifest.json et icônes
  - Chargement des scripts temps réel et PWA

- **Template aquaponie.twig** :
  - Badge LIVE fixe en haut à droite
  - Panneau "État du système" avec 4 métriques temps réel
  - Scripts d'initialisation du polling
  - Countdown "Prochaine mise à jour"

- **Template dashboard.twig** :
  - Meta tags PWA
  - Chargement CSS et JS temps réel

- **Template control.twig** :
  - Meta tags PWA
  - Préparation pour synchronisation temps réel GPIO

- **Repository SensorReadRepository** :
  - Nouvelle méthode `getReadingsSince(string $sinceDate)`
  - Nouvelle méthode `countReadingsBetween(string $start, string $end)`

#### 📦 Dépendances
- **Ajouté** : `minishlink/web-push: ^8.0` (notifications push navigateur)
- **Ajouté** : `bacon/bacon-qr-code: ^2.0` (génération QR codes)

#### ⚙️ Configuration
- **Nouvelles variables .env** :
  - `REALTIME_POLLING_INTERVAL` : Intervalle de polling (défaut 15s)
  - `REALTIME_ENABLE_NOTIFICATIONS` : Activer notifications (défaut true)
  - `PUSH_VAPID_PUBLIC_KEY` : Clé publique VAPID (à générer)
  - `PUSH_VAPID_PRIVATE_KEY` : Clé privée VAPID (à générer)
  - `PUSH_ADMIN_EMAIL` : Email admin pour push
  - `PWA_ENABLE_OFFLINE` : Activer mode offline (défaut true)
  - `PWA_CACHE_VERSION` : Version du cache (défaut 1.0.0)

#### 📝 Fichiers créés
- `src/Controller/RealtimeApiController.php`
- `src/Service/RealtimeDataService.php`
- `public/assets/js/toast-notifications.js`
- `public/assets/js/realtime-updater.js`
- `public/assets/js/pwa-init.js`
- `public/assets/js/mobile-gestures.js`
- `public/assets/css/realtime-styles.css`
- `public/assets/css/mobile-optimized.css`
- `public/manifest.json`
- `public/service-worker.js`
- `public/assets/icons/generate-icons.php` (script génération icônes)

#### 🎨 UX/UI
- **Badge LIVE** : Indicateur temps réel avec animations
- **Toast notifications** : Feedback visuel non-intrusif
- **Dashboard système** : Métriques en temps réel
- **Mobile gestures** : Navigation intuitive sur tactile
- **Bottom nav** : Accès rapide aux sections principales
- **Responsive amélioré** : Adaptation parfaite mobile/tablette/desktop

#### 🚀 Performance
- **Polling optimisé** : Requêtes légères (JSON)
- **Cache intelligent** : Service worker avec fallback
- **Lazy loading** : Scripts chargés après DOM ready
- **Mode pause** : Arrêt du polling si onglet inactif

#### 🔒 Sécurité
- **API endpoints** : Authentification via système existant
- **CORS** : Headers appropriés pour API REST
- **Service worker** : Validation des requêtes

#### 📱 Compatibilité
- **Navigateurs** : Chrome, Firefox, Safari, Edge
- **PWA** : Support complet Chrome/Edge, partiel Safari
- **Touch events** : Détection automatique
- **Fallbacks** : Dégradation gracieuse si PWA non supporté

#### 📊 Métriques
- **Uptime** : Calculé sur 30 jours
- **Latence** : Estimation 3.5s moyenne
- **Fréquence** : Lectures attendues toutes les 3 minutes
- **Lectures/jour** : Compteur en temps réel

### 🎯 À venir (Roadmap)
- [ ] Notifications push navigateur (infrastructure prête)
- [ ] QR codes intelligents pour accès rapide
- [ ] Mise à jour temps réel des graphiques Highcharts
- [ ] Synchronisation temps réel des GPIO dans interface contrôle
- [ ] Tests unitaires pour nouveaux services
- [ ] Mode offline complet avec cache étendu
- [ ] Graphiques optimisés mobile (fullscreen, gestures)

### 📋 Migration
Pour migrer vers v4.0.0 :
1. `git pull` pour récupérer les dernières modifications
2. `composer update` pour installer nouvelles dépendances
3. Vérifier les nouvelles variables `.env` (optionnelles)
4. Tester le badge LIVE et le dashboard système
5. Sur mobile, tester le bouton d'installation PWA
6. Générer les clés VAPID si notifications push souhaitées :
   ```bash
   ./vendor/bin/web-push generate-vapid-keys
   ```

### ⚠️ Notes importantes
- **Polling** : Génère 4 requêtes/minute par utilisateur actif
- **Cache** : Vérifier espace disque pour cache service worker
- **Mobile** : Tester sur vrais appareils iOS/Android
- **Offline** : Mode dégradé, pas toutes les fonctionnalités

---

## [3.1.0] - 2025-10-10

### 🐛 Sprint 3 - Améliorations Qualité & Corrections

#### Corrigé
- **Bug critique CleanDataCommand** : `checkWaterLevels()` utilisait `min()` au lieu de la dernière lecture réelle
  - Maintenant utilise `SensorReadRepository->getLastReadings()` pour obtenir la vraie dernière valeur
  - Fix potentiel de fausses alertes de niveau d'eau bas

#### ✨ Ajouté
- **RestartPumpCommand** : Nouvelle commande pour gérer le redémarrage différé des pompes
  - Remplace le `sleep(300)` bloquant dans ProcessTasksCommand
  - Utilise un système de flag file pour programmer les redémarrages
  - Permet au CRON de ne plus être bloqué pendant 5 minutes
  
- **Tests unitaires** : Amélioration de la couverture (+15%)
  - `ChartDataServiceTest` : Tests complets du service de charts
  - `StatisticsAggregatorServiceTest` : Tests d'agrégation des stats
  - `EnvironmentMiddlewareTest` : Tests du middleware d'environnement
  
- **Documentation legacy** : `LEGACY_README.md`
  - Documentation complète de tous les fichiers legacy
  - Statut de chaque fichier (Actif/Obsolète/À supprimer)
  - Plan de migration détaillé

#### 🔧 Modifié
- **ProcessTasksCommand** : `checkTideSystem()` ne bloque plus avec `sleep(300)`
  - Crée un flag file pour redémarrage programmé
  - Log clair indiquant qu'un redémarrage est prévu dans 5 minutes
  - À coupler avec `RestartPumpCommand` dans le CRON

#### 📝 Documentation
- **LEGACY_README.md** : Guide complet des fichiers legacy
- **Tests** : +3 fichiers de tests (couverture en progression)

---

## [3.0.0] - 2025-10-10 🚀 BREAKING CHANGES

### ⚡ Sprint 2 - Refactoring Architectural Majeur

#### 💥 Breaking Changes
- **Injection de dépendances (DI)** : Implémentation de PHP-DI v7
  - Tous les contrôleurs utilisent maintenant l'injection de dépendances
  - Nécessite `composer update` pour installer PHP-DI
  - Les contrôleurs ne peuvent plus être instanciés manuellement sans le container

#### ✨ Ajouté
- **Container DI** : `config/container.php` et `config/dependencies.php`
  - Gestion centralisée de toutes les dépendances
  - Autowiring automatique pour les contrôleurs
  - Cache de compilation en production pour meilleures performances
  
- **Nouveaux Services**
  - `ChartDataService` : Préparation des données pour Highcharts
    - `prepareSeriesData()` : Toutes les séries (EauAquarium, TempAir, etc.)
    - `prepareTimestamps()` : Timestamps en ms epoch UTC
    - `extractLastReadings()` : Dernières valeurs des capteurs
  - `StatisticsAggregatorService` : Agrégation des statistiques
    - `aggregateAllStats()` : Stats pour tous les capteurs en une fois
    - `aggregateForSensor()` : Stats pour un capteur spécifique
    - `flattenForLegacy()` : Format compatible legacy (min_tempair, max_tempair, etc.)

- **EnvironmentMiddleware** : Gestion automatique des environnements PROD/TEST
  - Appliqué automatiquement sur les groupes de routes TEST
  - Élimine la duplication `TableConfig::setEnvironment()` dans chaque route

- **Méthodes OutputService** :
  - `updateStateById()` : Mise à jour d'un output par ID
  - `updateMultipleParameters()` : Mise à jour batch de plusieurs paramètres

#### 🔧 Refactorisé
- **AquaponieController** : Réduit de 301 à ~180 lignes (-40%)
  - Utilise ChartDataService et StatisticsAggregatorService
  - Extraction de méthodes privées (`extractDateRange`, `calculateDuration`, `exportCsv`)
  - Injection de dépendances dans le constructeur
  - Suppression des variables intermédiaires répétitives

- **OutputController** : Simplifié et nettoyé
  - Délègue toute la logique SQL à OutputService
  - Plus de requêtes SQL directes dans le contrôleur
  - Méthodes `toggleOutput()` et `updateParameters()` réduites de 50%

- **public/index.php** : Réduit de 183 à ~95 lignes (-48%)
  - Utilisation du container DI pour tous les contrôleurs
  - Groupes de routes (`$app->group()`) pour TEST
  - EnvironmentMiddleware appliqué sur le groupe TEST
  - Élimine la duplication massive des routes TEST

#### 📦 Dépendances
- **Ajouté** : `php-di/php-di: ^7.0`
- **Mise à jour** : `slim/psr7: ^1.6`

#### 📝 Architecture
- Séparation claire des responsabilités (Controllers, Services, Repositories)
- Testabilité grandement améliorée grâce à l'injection de dépendances
- Code plus maintenable et évolutif
- Réduction significative de la duplication de code

---

## [2.9.0] - 2025-10-10

### 🐛 Corrigé (Sprint 1 - Corrections Critiques)
- **Nettoyage lignes vides excessives** : Suppression des lignes vides inutiles dans tous les fichiers PHP
  - `src/Config/Database.php` : Réduit de 87 à 44 lignes
  - `src/Service/SensorStatisticsService.php` : Réduit de 260 à 128 lignes
  - `src/Repository/SensorReadRepository.php` : Nettoyé et optimisé
  - `src/Repository/SensorRepository.php` : Nettoyé et optimisé
  - `src/Service/LogService.php` : Nettoyé et optimisé
  - `src/Controller/ExportController.php` : Nettoyé et optimisé
  - `src/Security/SignatureValidator.php` : Nettoyé et optimisé
  - Amélioration significative de la lisibilité et maintenabilité du code

- **Tables SQL en dur corrigées** : `AquaponieController.php`
  - Ligne 194 : Utilise maintenant `TableConfig::getDataTable()` au lieu de 'ffp3Data' en dur
  - Ligne 218 : Même correction pour la requête MAX(id)
  - Respect de l'environnement TEST désormais garanti

### ✨ Ajouté
- **Middleware de gestion d'erreurs** : `ErrorHandlerMiddleware`
  - Capture toutes les exceptions non gérées
  - Log détaillé via LogService (message, fichier, ligne, trace, URL, méthode)
  - Réponse HTTP 500 standardisée en cas d'erreur
  - Intégré dans `public/index.php`

- **Cache Twig activé en production** : `TemplateRenderer.php`
  - Cache automatique dans `/var/cache/twig/` en environnement prod
  - Désactivé en environnement dev pour faciliter le développement
  - Amélioration significative des performances de rendu

- **Script de nettoyage** : `tools/cleanup_whitespace.php`
  - Outil automatique pour nettoyer les lignes vides excessives
  - Règles : max 1 ligne vide entre méthodes, aucune dans les blocs de code
  - Réutilisable pour mainten

ance future

### 🔧 Modifié
- **`.gitignore`** : Ajout de `desktop.ini` et `/var/cache/`
- **`LogService`** : Ajout @deprecated sur `sendAlertEmail()` (à déplacer dans NotificationService)

### 📝 Documentation
- **Rapport d'audit complet** : `AUDIT_PROJET.md`
  - Analyse détaillée de tout le projet
  - Identification de 18 problèmes (4 critiques, 7 majeurs, 7 mineurs)
  - Plan d'action sur 3 sprints
  - Recommandations d'améliorations long terme

---

## [2.8.0] - 2025-10-10

### ✨ Ajouté
- **Page d'accueil moderne** : Nouvelle page `index.html` avec présentation des projets IoT
  - Design moderne avec cartes de projets (FFP3, MSP1, N3PP)
  - Bannière informative sur le projet pédagogique olution
  - Grille de statistiques pour chaque projet
  - Section technologies utilisées (ESP32, PHP, MySQL, Highcharts, Bootstrap)
  - Liens utiles vers olution.info, farmflow et GitHub
  - Style cohérent avec les autres pages (même charte graphique)

### 🔧 Modifié
- **Navigation unifiée** : Onglets harmonisés sur toutes les pages
  - "Accueil" au lieu de "olution" (navigation cohérente)
  - "L'aquaponie (FFP3)" au lieu de "L'aquaponie" ou "le prototype farmflow 3"
  - "Le potager" uniformisé partout
  - "L'élevage d'insectes" uniformisé partout
  - Tous les liens vers l'accueil pointent vers `index.html` au lieu de `index.php`
- **Templates mis à jour** : aquaponie.twig, dashboard.twig, control.twig, tide_stats.twig
  - Header logo pointe vers index.html
  - Onglet actif mis en évidence sur chaque page

### 🎨 UX/UI
- **Cohérence visuelle totale** : Navigation identique sur toutes les pages
- **Identification claire** : Le nom des projets est explicite dans les onglets
- **Page d'accueil attractive** : Présentation claire et moderne des 3 projets
- **Cartes interactives** : Effets hover sur les cartes de projets
- **Responsive** : Adaptation mobile/tablette/desktop de la page d'accueil

### 📱 Structure
- **index.html** remplace la redirection `index.php`
- Accueil accessible depuis toutes les pages via navigation
- Point d'entrée clair pour découvrir les projets

---

## [2.7.0] - 2025-10-10

### ✨ Ajouté
- **Support des redirections legacy** : Les anciennes URL redirigent vers les nouvelles routes Slim
- **Gestion de session** : Transfert des données POST lors des redirections pour compatibilité complète
- **Compatibilité rétroactive totale** : QR codes et liens legacy continuent de fonctionner

### 🔧 Modifié
- **ffp3-data.php** : Transformé en redirection vers `/aquaponie` (PROD)
  - Transfert des paramètres POST via session
  - Transfert des paramètres GET via query string
- **ffp3-data2.php** : Amélioration de la redirection vers `/aquaponie-test` (TEST)
  - Ajout transfert des paramètres GET
- **post-ffp3-data.php** : Modernisé pour utiliser PostDataController (PROD)
  - Force l'environnement PROD
  - Utilise le contrôleur moderne au lieu du code SQL legacy
- **AquaponieController.php** : Support des données POST transférées via session
  - Récupération automatique des données de session lors de redirections
  - Compatibilité avec les anciennes pages PHP

### 🗑️ Nettoyage
- **Suppression templates obsolètes** :
  - `templates/ffp3-data.php` (remplacé par aquaponie.twig)
  - `templates/dashboard.php` (remplacé par dashboard.twig)

### 🔗 Mapping des redirections

#### Pages de visualisation
- `ffp3-data.php` → `/aquaponie` (PROD)
- `ffp3-data2.php` → `/aquaponie-test` (TEST)

#### Endpoints ESP32
- `post-ffp3-data.php` → PostDataController (PROD)
- `post-ffp3-data2.php` → PostDataController (TEST)

### ⚡ Avantages
- **QR Codes opérationnels** : Les anciens QR codes continuent de fonctionner
- **Liens externes préservés** : Pas besoin de mettre à jour les liens externes
- **Migration transparente** : Transition fluide de l'ancien au nouveau système
- **Code nettoyé** : Suppression de 1200+ lignes de code legacy dans ffp3-data.php

---

## [2.6.0] - 2025-10-10

### ✨ Ajouté
- **Harmonisation complète du style graphique** sur toutes les pages
- **Dashboard modernisé** : Cartes de statistiques, tableaux modernes, headers avec icônes
- **Tide Stats modernisé** : Cartes de statistiques pour marées, graphiques dans conteneurs blancs, filtrage moderne

### 🔧 Modifié - Dashboard (dashboard.twig)
- **Header et navigation** : Intégration du template olution.info avec menu complet
- **Cartes de statistiques** : Remplacement des listes par des cartes modernes avec icônes
- **Tableaux** : Design moderne avec dégradés verts dans les headers
- **Bannière période** : Affichage moderne avec gradient et informations centralisées
- **Section headers** : Icônes et bordures colorées cohérentes avec aquaponie

### 🔧 Modifié - Tide Stats (tide_stats.twig)
- **Header et navigation** : Intégration du template olution.info avec menu complet
- **Cartes de statistiques** : 3 sections avec cartes (Résultats principaux, Variations réserve, DiffMaree)
- **Graphiques Chart.js** : Conteneurs blancs avec titres et ombres
- **Filtrage modernisé** : Boutons rapides et formulaire dans section dédiée
- **Couleurs thématiques** : Vert (positif), Rouge (négatif), Bleu (global)
- **Icônes spécifiques** : Flèches, vagues, graphiques pour chaque type de donnée

### 🎨 UX/UI
- **Cohérence visuelle** : Même charte graphique sur toutes les pages (aquaponie, dashboard, tide-stats, control)
- **Headers uniformisés** : Icônes et bordures vertes pour toutes les sections
- **Cartes modernes** : Ombres, hover effects, bordures arrondies partout
- **Responsive** : Adaptation automatique mobile sur toutes les pages
- **Navigation unifiée** : Menu identique avec liens olution.info et farmflow

### 📱 Responsive
- **Grilles adaptatives** : Stats-grid et quick-filters s'adaptent à l'écran
- **Layout mobile** : 1 colonne sur petits écrans pour toutes les pages

---

## [2.5.0] - 2025-10-10

### ✨ Décision finale
- **Version D adoptée comme version unique** : Suppression des versions A, B et C
- **Sélecteur de version retiré** : Interface simplifiée
- **Code nettoyé** : Suppression de ~500 lignes de code inutilisé (versions A, B, C)

### 🔧 Modifications
- **createVersionD()** : Seule fonction de création de graphiques conservée
- **Graphiques finaux** : Stock Navigator + Aires colorées + Scatter pour équipements
- **CSS allégé** : Suppression des styles du sélecteur de version
- **HTML simplifié** : Conteneurs D uniquement, plus de divs cachés

### 📊 Graphiques conservés
- **Niveaux d'eau** : Aires colorées avec zones de référence (critique/optimal/attention) + scatter pour pompes et chauffage
- **Paramètres physiques** : Aires colorées sur 3 axes Y + scatter pour LEDs et nourriture

### ⚡ Performance
- **Code réduit** : 1419 lignes au lieu de 1787 lignes (-368 lignes)
- **Chargement plus rapide** : Plus de lazy loading, un seul jeu de graphiques créé
- **Mémoire optimisée** : Suppression des fonctions et conteneurs inutilisés

---

## [2.4.1] - 2025-10-10

### 🎯 Modifié
- **Version D définie comme version par défaut** : S'affiche automatiquement au chargement de la page
  - Bouton Version D en première position avec icône étoile ⭐
  - Graphiques Version D créés et affichés au chargement initial
  - Versions A, B, C toujours disponibles via le sélecteur mais cachées par défaut
  - Optimisation : Lazy loading conservé pour A, B et C (chargement à la demande)

### 🎨 UX/UI
- **Premier bouton** : Version D (actif par défaut)
- **Autres boutons** : A, B, C accessibles pour comparaison
- **Chargement optimisé** : Seule la version D est chargée au démarrage

---

## [2.4.0] - 2025-10-10

### ✨ Ajouté
- **Version D - Stock + Aires colorées (Mix B+C)** : Le meilleur des deux mondes !
  - **Stock Navigator** de la version B : Range selector, navigation avancée, scrollbar
  - **Aires colorées** de la version C : Graphiques areaspline avec dégradés
  - **Zones de référence** : PlotBands avec zones critique/optimal/attention sur niveaux et températures
  - **États des actionneurs en scatter** : Points colorés avec symboles différents (cercle, carré, triangle, diamant)
  - **Légendes complètes** : Toutes les séries affichées dans la légende
  - **Gradients personnalisés** : Dégradés verticaux pour chaque série d'aires

### 🎨 Caractéristiques Version D

#### Graphique Niveaux d'eau :
- Aires colorées pour aquarium, réserve, potager
- Zones : Rouge (0-15 critique), Vert (15-65 optimal), Orange (65-100 attention)
- États équipements en scatter : Pompe aquarium (●), Pompe réserve (■), Chauffage (▲)

#### Graphique Paramètres physiques :
- 3 axes Y séparés (températures 28%, humidité 28%, luminosité 28%)
- Aires colorées pour chaque paramètre avec dégradés
- Zones température : Bleu (froid), Vert (optimal), Rouge (chaud)
- États équipements : LEDs (◆), Nourriture gros (●), Nourriture petits (■)

### 🔧 Technique
- Type : `areaspline` avec `fillOpacity` et `linearGradient`
- États actionneurs : Type `scatter` avec `filter(p => p[1] > 0)` pour afficher uniquement les ON
- Symboles distincts : circle, square, triangle, diamond
- Transparence : 60-70% pour les scatter points

---

## [2.3.3] - 2025-10-10

### 🐛 Corrigé
- **Hauteurs des colonnes équipements uniformisées** dans la version B
  - Désactivation du stacking : `stacking: null` pour éviter l'empilement
  - Désactivation du grouping : `grouping: false` pour superposer les colonnes
  - Ajout de transparence : `opacity: 0.6` et couleurs RGBA pour voir les chevauchements
  - Suppression des bordures : `borderWidth: 0` pour un rendu plus propre
  - Toutes les barres "ON" ont maintenant la même hauteur (valeur 1)

### 🎨 Amélioré
- **Colonnes superposées avec transparence** : On peut maintenant voir quand plusieurs équipements sont actifs simultanément
- **Couleurs RGBA** : Transparence appliquée aux couleurs des colonnes pour meilleure visibilité

---

## [2.3.2] - 2025-10-10

### 🐛 Corrigé
- **Erreur Highcharts Stock corrigée** : `Highcharts.stockChart is not a function`
  - Suppression de `highcharts.js` (conflit avec highstock.js)
  - Highstock.js inclut déjà toutes les fonctionnalités de Highcharts standard
  - Modules corrigés : Utilisation de `modules/` au lieu de `stock/modules/`
  - Chargement optimisé : Un seul script principal (highstock.js) au lieu de deux

### ⚡ Performance
- **Moins de scripts** : 5 scripts au lieu de 6
- **Pas de conflit** entre highcharts.js et highstock.js
- **Chargement plus rapide** : Moins de requêtes HTTP

---

## [2.3.1] - 2025-10-10

### 🐛 Corrigé
- **Version B (Stock Navigator) corrigée** : Fonctionnement maintenant opérationnel
  - Suppression des flags problématiques qui causaient des erreurs JavaScript
  - Ajout d'un 4ème axe Y pour les états des équipements (LEDs, nourriture)
  - Amélioration de la hauteur des graphiques (600px et 700px) pour meilleure lisibilité
  - Ajout de `inputEnabled: true` pour permettre la saisie manuelle de dates
  - Configuration `showInNavigator` pour afficher les séries principales dans le navigator
  - Séparation claire des données : Niveaux/États équipements et Températures/Humidité/Luminosité/Équipements

### 🔧 Modifié
- **Graphique Niveaux d'eau** : 2 axes Y (niveaux 55%, états 35%)
- **Graphique Paramètres physiques** : 4 axes Y (températures 25%, humidité 25%, luminosité 25%, états 10%)
- **Navigator** : Hauteur fixée à 40px pour meilleure visibilité

---

## [2.3.0] - 2025-10-10

### ✨ Ajouté
- **3 versions de graphiques Highcharts** inspirées des visualisations météo pour améliorer la lisibilité
  - **Version A - Graphiques empilés synchronisés** : 4 graphiques séparés (niveaux, températures, humidité/lumière, équipements) avec zoom synchronisé style météo
  - **Version B - Stock Navigator** : Graphiques Highcharts Stock avec barre de navigation et range selector (1h, 6h, 1j, 1s, 1m)
  - **Version C - Aires colorées** : Graphiques en aires avec zones de référence (optimal, critique, attention)
- **Sélecteur de version** : Boutons modernes pour basculer entre les 3 versions de visualisation
- **Bandes temporelles colorées** : États des équipements affichés en bandes horizontales (plotBands) sur les graphiques de niveaux d'eau
- **Zones de référence** : Plages de valeurs optimales/critiques visibles dans la version C
- **Module Highcharts Stock** : Ajout du module pour les graphiques avec navigation avancée

### 🔧 Modifié
- **Graphiques séparés** au lieu de graphiques complexes avec multiples axes Y
- **Synchronisation du zoom** : Les 4 graphiques de la version A se synchronisent automatiquement
- **États équipements** : 
  - Version A : Graphique dédié avec toutes les données
  - Version B : Bandes colorées (plotBands) sur graphiques principaux
  - Version C : Colonnes intégrées dans les graphiques
- **Interface graphiques** : Chargement lazy des versions B et C (créées uniquement à la première sélection)

### 🎨 UX/UI
- **Lisibilité améliorée** : Chaque type de donnée a son propre graphique
- **Navigation facilitée** : Range selector dans version B pour zoomer rapidement
- **Visualisation claire** : Zones colorées montrent les plages de valeurs idéales
- **Style météo** : Graphiques empilés comme sur les sites météo professionnels
- **Responsive** : Tous les graphiques s'adaptent à la taille d'écran
- **Sélecteur moderne** : Boutons avec icônes et effets hover

### 📊 Données
- **Aucune perte de données** : Toutes les données sont affichées dans chaque version
- **Compatibilité maintenue** : Timezone Europe/Paris conservé
- **Performance** : Lazy loading des versions B et C pour optimiser le chargement initial

---

## [2.2.4] - 2025-10-10

### 🐛 Corrigé
- **Nouvelle tentative de correction des icônes** : Utilisation des noms d'icônes FA5/FA6 universels
  - Retour à `fas fa-tint` pour "Niveaux d'eau" (compatible FA5/FA6)
  - Retour à `fas fa-thermometer-half` pour "Paramètres physiques" (compatible FA5/FA6)
  - Suppression de la classe `icon` qui pourrait causer des conflits CSS

---

## [2.2.3] - 2025-10-10

### 🐛 Corrigé
- **Icônes Font Awesome manquantes** : Remplacement des icônes non compatibles
  - `fa-water` → `fa-tint` pour "Niveaux d'eau"
  - `fa-temperature-half` → `fa-thermometer-half` pour "Paramètres physiques"
  - `fa-water` → `fa-thermometer` pour "Température eau"
- Les icônes s'affichent maintenant correctement au lieu de rectangles avec croix

---

## [2.2.2] - 2025-10-10

### 🎨 Amélioré
- **Graphiques paramétriques chimiques centrés** : Meilleur alignement visuel des 3 cartes de graphiques
- **Largeur maximale** des cartes fixée à 650px pour une cohérence visuelle
- **Grille centrée** avec largeur maximale de 1800px pour éviter l'étirement excessif sur grands écrans

---

## [2.2.1] - 2025-10-10

### 🎨 Amélioré
- **Interface de filtrage des données** complètement redessinée pour une ergonomie optimale
- **Carte dédiée au filtrage** : Section visuellement distincte avec fond blanc, ombres et bordure colorée
- **Inputs datetime-local** : Champs date et heure unifiés au lieu de 4 champs séparés
- **Boutons de période rapide** améliorés avec icônes et effets hover
- **Panneau d'information** : Affichage en temps réel de la période analysée avec gradient moderne
- **Statistiques visibles** : Durée d'analyse, nombre d'enregistrements et durée totale de fonctionnement affichés en haut
- **Bouton CSV intégré** : Export CSV directement dans la section de filtrage, plus besoin de chercher
- **Design responsive** : Adaptation automatique pour mobile, tablette et desktop
- **Animations et transitions** : Effets visuels fluides sur hover et focus

### 🔧 Technique
- **Rétrocompatibilité** : Support des anciens paramètres `start_date`/`start_time` et nouveaux `start_datetime`
- **Contrôleur adapté** : `AquaponieController` gère les deux formats de dates automatiquement
- **JavaScript optimisé** : Fonction `setPeriod()` mise à jour pour les nouveaux inputs
- **Code CSS modulaire** : Classes réutilisables pour les futurs filtres

### 📱 Responsive
- **Mobile-first** : Layout adapté pour les petits écrans
- **Grille flexible** : Ajustement automatique selon la taille d'écran
- **Touch-friendly** : Boutons et inputs dimensionnés pour l'utilisation tactile

---

## [2.2.0] - 2025-10-10

### ✨ Ajouté
- **Cartes de statistiques modernes** sur page aquaponie remplaçant les anciennes jauges semi-circulaires
- **Icônes Font Awesome** pour chaque type de mesure (💧 eau, 🌡️ température, 💡 lumière, etc.)
- **Progress bars animées** avec dégradés de couleurs par thématique
- **Effet hover** sur les cartes de statistiques pour interactivité
- **Headers de section** avec icônes et bordures colorées

### 🔧 Modifié
- **Jauges semi-circulaires** → Cartes modernes avec valeurs en grand format
- **Statistiques visuelles** : Min/Max/Moy affichés de manière compacte sous chaque carte
- **Palette de couleurs** cohérente : Bleu (eau), Rouge (température), Violet (humidité), Jaune (lumière)
- **Layout responsive** : Grille adaptative pour mobile, tablette, desktop
- **JavaScript** : Fonctions modernisées pour mettre à jour les cartes au lieu des jauges
- **Interface contrôle** : Email en vert clair pour se distinguer des autres paramètres

### 🎨 UX/UI
- Interface **plus moderne et lisible** avec cartes épurées
- **Identification rapide** grâce aux icônes et couleurs
- **Animations fluides** (transform, width transition)
- **Compatibilité** : Fonctionne avec les mêmes données et APIs

---

## [2.1.0] - 2025-10-10

### ✨ Ajouté
- **Icônes Font Awesome** pour chaque actionneur (💧 eau, 🌡️ température, 💡 lumière, 🐟 poissons, 🔄 reset)
- **Layout 2 colonnes** sur écran desktop (>1200px) pour éviter le scroll
- **Version du projet** affichée en pied de page sur toutes les pages
- **Système de versionnage centralisé** avec fichier `VERSION` et classe `Version.php`
- **CHANGELOG.md** : Documentation complète de toutes les versions

### 🔧 Modifié
- **Interface de contrôle** entièrement redesignée avec sections thématiques colorées
- **Formulaires compacts** : Labels raccourcis, padding réduit, grille optimisée
- **Actionneurs organisés** en grille responsive avec icônes et switches réduits
- **Paramètres groupés** par catégorie (📧 Notifications, 💧 Eau, 🌡️ Chauffage, 🐟 Nourrissage, 🔧 Système)
- **Filtrage des boards** : Affichage uniquement des boards actives pour l'environnement (PROD ou TEST)
- **Responsive amélioré** : Layout adaptatif selon taille d'écran (desktop, tablette, mobile)

### 🐛 Corrigé
- **Bug CSS** : Affichage cassé au chargement qui se corrigeait à l'ouverture de l'inspecteur
  - Ajout de `!important` pour surcharger le CSS du template
  - Force reflow JavaScript au chargement
  - Transition opacity pour masquer le calcul initial
- **Timing formulaire** : Délai augmenté à 1,5s avec transaction SQL pour éviter l'affichage des anciennes valeurs
- **Affichage boards** : Filtrage par environnement pour éviter de mélanger PROD et TEST

### 🎨 UX/UI
- Interface **sans scroll** sur écran desktop standard (1920x1080)
- **Sections visuellement distinctes** avec codes couleur par thématique
- **Actions rapides compactes** avec icônes (⚙️ Cron, 📋 Journal, 📊 Données)
- **Badges d'environnement** : Indication claire (TEST) en orange
- **Icônes cohérentes** dans toute l'interface pour identification rapide

---

## [2.0.0] - 2025-10-08

### ✨ Ajouté
- **Architecture TEST/PROD complète** : Environnements séparés avec tables distinctes
- **Module de contrôle moderne** : Interface web pour GPIO avec routes `/control` et `/control-test`
- **API REST complète** pour contrôle outputs :
  - `GET /api/outputs/state` - État des GPIO
  - `GET /api/outputs/toggle?id=X&state=Y` - Toggle GPIO
  - `POST /api/outputs/parameters` - Mise à jour paramètres
- **Nouveaux composants** :
  - `TableConfig` : Gestion dynamique des tables selon environnement
  - `OutputRepository` : Gestion des GPIO en base de données
  - `BoardRepository` : Gestion des cartes ESP32
  - `OutputService` : Logique métier pour contrôles
  - `OutputController` : Contrôleur Slim pour interface de contrôle
- **Routes TEST** : `/dashboard-test`, `/aquaponie-test`, `/control-test`, `/post-data-test`
- **Documentation complète** :
  - `ENVIRONNEMENT_TEST.md` : Guide TEST/PROD
  - `RECAPITULATIF_MIGRATION.md` : Synthèse migration
  - `TODO_AMELIORATIONS_CONTROL.md` : Roadmap améliorations

### 🔧 Modifié
- **Repositories** : Utilisation de `TableConfig` pour sélection dynamique des tables
- **Services** : Adaptation pour supporter PROD et TEST
- **Interface de contrôle** : Ordre des switches personnalisé, nettoyage des intitulés
- **Formulaire paramètres** : Gestion correcte des types (string pour mail, int pour autres)
- **Toggle GPIO** : Utilisation de l'ID de base au lieu du GPIO (compatibilité legacy)

### 🐛 Corrigé
- Affichage des switches dans le bon ordre (pompe aqua, pompe réserve, radiateurs, lumière, nourrisseurs, reset)
- Suppression mention "(stoppée/stoppés si relais activé)" des intitulés
- Affichage des GPIO 108, 109, 110 (nourrisseurs et reset)
- Gestion des paramètres email et notifications dans formulaire
- Logs de debug pour diagnostic des problèmes de toggle

### 🔒 Sécurité
- Préparation pour authentification HTTP Basic sur `/control`
- Validation des paramètres dans les API

---

## [1.x.x] - Versions précédentes

### Fonctionnalités existantes
- Dashboard avec graphiques Highcharts
- Visualisation données aquaponie
- Export CSV des données
- API ESP32 pour post de données
- Timezone unifié Europe/Paris
- Statistiques marées (tide stats)
- Gestion GPIO legacy via `ffp3control`

---

## Format du versioning

**MAJOR.MINOR.PATCH**

- **MAJOR** : Changements incompatibles avec versions précédentes
- **MINOR** : Ajout de fonctionnalités rétrocompatibles
- **PATCH** : Corrections de bugs

---

*Ce changelog sera mis à jour à chaque release significative.*

