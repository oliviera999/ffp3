# Projet Aquaponie – Documentation complète

Cette documentation décrit le rôle fonctionnel et technique de chaque fichier se trouvant dans le dépôt. Elle est organisée par répertoire en suivant l’arborescence du projet.

---
## Racine du dépôt

| Fichier | Description détaillée |
|---------|----------------------|
| **composer.json** | Déclare les dépendances PHP du projet (Monolog, vlucas/phpdotenv, etc.) et l’autoload PSR-4 (`App\\`). Sert de source principale à `composer install/update`. |
| **composer.lock** | Instantané versionné des dépendances effectives. Garantit la reproductibilité des installations. |
| **env.dist** | Exemple de fichier `.env` listant toutes les variables d’environnement attendues (DB_HOST, API_KEY, etc.). À copier/renommer en `.env` pour une installation locale. |
| **.htaccess** | Règles Apache : redirection vers `public/`, protection de certains fichiers, désactivation de l’indexation. |
| **index.php** | Redirection HTTP 301 vers l’URL publique officielle de suivi (`…/ffp3-data.php`). Conserve la compatibilité avec l’adresse racine historique. |
| **ffp3-config.php** | Ancien script procédural (≈900 lignes) contenant : chargement du `.env`, connexion MySQL, utilitaires GPIO, fonctions statistiques, export CSV, maintenance. Conservé pour la compatibilité mais la logique est portée dans `src/`. |
| **ffp3-data.php** | Page Web monolithique (≈1 100 lignes) générant le tableau de bord historique (jauges, tableaux, graphiques). Remplacée par `AquaponieController` mais laissée pour compatibilité. |
| **legacy_bridge.php** | Pont de compatibilité qui redirige les appels legacy vers les nouvelles classes (`SensorReadRepository`, `SensorStatisticsService`). |
| **post-ffp3-data.php** | Point d’API legacy (POST) appelé par l’ESP32. Vérifie la clé API, insère dans `ffp3Data` et met à jour `ffp3Outputs`. |
| **cronpompe.php** | Script CRON hérité (≈230 lignes) : nettoie les valeurs aberrantes, pilote les pompes, écrit dans `cronlog.txt`, envoie des alertes. |
| **run-cron.php** | Lanceur CLI moderne. Exécute `App\Command\ProcessTasksCommand` et renvoie un code Unix. |
| **cronlog.txt** / **error_log** | Fichiers de journalisation legacy et Apache. |
| **.cursorignore**, **.gitattributes**, **test** | Fichiers utilitaires (IDE, Git, placeholder). |

---
## Répertoire `public/`

| Fichier | Rôle |
|---------|------|
| **index.php** | Mini front-controller. Charge l’autoloader, les variables d’environnement puis route vers les contrôleurs (`Dashboard`, `Export`, `PostData`, `Aquaponie`). |
| **export-data.php** | Export CSV autonome : valide `start`/`end`, utilise `SensorReadRepository`, streame le fichier, journalise. |
| **post-data.php** | API moderne pour l’ESP32 : vérifie la clé, instancie `SensorData`, insère via `SensorRepository`, logge. |
| **.htaccess** | Force `index.php` comme front-controller, désactive le listing, compression GZip. |

---
## Répertoire `src/Config`

| Fichier | Description |
|---------|-------------|
| **Env.php** | Helper statique pour charger `.env` une seule fois sans écraser l’environnement. |
| **Database.php** | Singleton PDO : charge DB_* depuis `.env`, crée la connexion MySQL UTF-8. |

---
## Répertoire `src/Domain`

| Fichier | Description |
|---------|-------------|
| **SensorData.php** | DTO représentant une ligne complète de la table `ffp3Data` (25 champs). |

---
## Répertoire `src/Repository`

| Fichier | Fonction |
|---------|----------|
| **SensorRepository.php** | Écriture : insère un `SensorData` dans `ffp3Data`. |
| **SensorReadRepository.php** | Lecture / export : fetch entre dates, dernières lectures, export CSV. |

---
## Répertoire `src/Service`

| Fichier | Responsabilité |
|---------|----------------|
| **LogService.php** | Encapsule Monolog, format `[date] [LEVEL] message`, écrit par défaut `cronlog.txt`. |
| **NotificationService.php** | Envoi d’e-mails, méthodes `notifyMareesProblem`, `notifyFloodRisk`, etc. |
| **PumpService.php** | Abstraction GPIO : lit/écrit `ffp3Outputs`, méthodes `stopPompeAqua`, `runPompeTank`, etc. |
| **SensorDataService.php** | Nettoyage des données (valeurs aberrantes) selon seuils définis dans `.env`. |
| **SensorStatisticsService.php** | Agrégats SQL (`MIN`, `MAX`, `AVG`, `STDDEV`) et écart-type sur N dernières mesures. |
| **SystemHealthService.php** | Supervision du système : online/offline, niveau réservoir, notifications. |

---
## Répertoire `src/Command`

| Fichier | Actions |
|---------|---------|
| **CleanDataCommand.php** | Version objet du cron : vérifie pompes, nettoie données, contrôle niveaux, logge. |
| **ProcessTasksCommand.php** | Orchestrateur CRON principal : nettoie, détecte risques (inondation, marées), vérifie santé, notifie. |

---
## Répertoire `src/Controller`

| Fichier | Route / Vue |
|---------|-------------|
| **DashboardController.php** | `/dashboard` : page synthétique, statistiques, template `dashboard.php`. |
| **AquaponieController.php** | `/aquaponie` : réécriture objet de la page historique `ffp3-data.php`. |
| **ExportController.php** | `/export-data` : génération CSV streaming. |
| **PostDataController.php** | `/post-data` : réception POST, insertion base, réponse HTTP. |

---
## Répertoire `templates/`

| Template | Contenu |
|----------|---------|
| **dashboard.php** | Vue minimaliste (tableaux + graphiques). |
| **ffp3-data.php** | Gabarit complet (jauges CSS, Highcharts, formulaires). |

---
## Répertoire `tests/`

Suites PHPUnit couvrant :
* `Repository/` – tests des requêtes de lecture.
* `Service/` – tests de log, pompes, nettoyage, statistiques, santé système.

---
## Répertoire `vendor/`

Dépendances tierces installées par Composer (Monolog, phpdotenv…). Aucun code applicatif.

---
## Vue d’ensemble architecturale

1. **Legacy vs Modern**  
   – Pile legacy : scripts procéduraux (`ffp3-config.php`, `ffp3-data.php`, etc.).  
   – Pile moderne : code PSR-4 sous `src/`, front-controller `public/`, commandes objets.

2. **Flux de données**  
   1. L’ESP32 envoie ses mesures (`/post-data`).  
   2. Les repositories écrivent `ffp3Data` & `ffp3Outputs`.  
   3. Les contrôleurs web lisent et affichent le tableau de bord.  
   4. Un CRON (`ProcessTasksCommand`) nettoie et surveille périodiquement.

3. **Configuration**  
   – Toutes les constantes (DB, API_KEY, seuils) sont centralisées dans `.env` et chargées via `Env::load()` ou `Database::getConnection()`.

---
### Licence

Ce projet est livré tel quel. Les dépendances tierces conservent leur propre licence (voir répertoire `vendor/`).