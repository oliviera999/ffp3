# Changelog FFP3 Datas

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère à [Semantic Versioning](https://semver.org/lang/fr/).

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

