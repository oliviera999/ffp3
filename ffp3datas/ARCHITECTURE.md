# Architecture du Projet FFP3

## 📐 Vue d'ensemble

Le projet FFP3 (FarmFlow Prototype 3) est un système IoT pour la gestion d'une installation aquaponique, comprenant :
- **Collecte de données** : Capteurs (température, humidité, niveaux d'eau, etc.)
- **Contrôle d'actionneurs** : Pompes, chauffage, alimentation automatique
- **Visualisation** : Dashboards, graphiques, statistiques
- **Gestion multi-environnement** : PROD et TEST complètement isolés

## 🏗️ Architecture Technique

### Stack Technologique

- **Backend** : PHP 7.4+ avec Slim Framework 4
- **Frontend** : Twig + jQuery + Highcharts/Chart.js
- **Base de données** : MySQL/MariaDB
- **Serveur web** : Apache avec mod_rewrite
- **ESP32** : Microcontrôleurs pour capteurs et actionneurs

### Pattern Architectural

**Architecture MVC moderne avec couches**:
```
┌─────────────────┐
│   Presentation  │  Templates Twig, JavaScript
├─────────────────┤
│   Controller    │  Slim Routes, HTTP Handlers
├─────────────────┤
│    Service      │  Logique métier, validation
├─────────────────┤
│   Repository    │  Accès données, requêtes SQL
├─────────────────┤
│   Database      │  MySQL (ffp3Data, ffp3Outputs, Boards)
└─────────────────┘
```

## 📁 Structure du Projet

### `ffp3datas/` - Application Principale Moderne

```
ffp3datas/
├── src/
│   ├── Config/
│   │   ├── Database.php          # Connexion PDO singleton
│   │   ├── Env.php                # Chargement .env
│   │   └── TableConfig.php        # Bascule PROD/TEST
│   ├── Repository/
│   │   ├── SensorReadRepository.php    # Lectures capteurs
│   │   ├── SensorRepository.php        # Insertion données
│   │   ├── OutputRepository.php        # GPIO/relais
│   │   └── BoardRepository.php         # ESP32 boards
│   ├── Service/
│   │   ├── SensorDataService.php       # Traitement données
│   │   ├── SensorStatisticsService.php # Calculs stats
│   │   ├── OutputService.php           # Logique contrôles
│   │   ├── PumpService.php             # Gestion pompes
│   │   ├── TideAnalysisService.php     # Analyse marées
│   │   ├── LogService.php              # Logs applicatifs
│   │   ├── NotificationService.php     # Alertes email
│   │   └── SystemHealthService.php     # Monitoring système
│   ├── Controller/
│   │   ├── DashboardController.php     # Page dashboard
│   │   ├── AquaponieController.php     # Page principale
│   │   ├── OutputController.php        # Contrôle GPIO
│   │   ├── TideStatsController.php     # Stats marées
│   │   ├── PostDataController.php      # Réception ESP32
│   │   └── ExportController.php        # Export CSV
│   ├── Domain/
│   │   └── SensorData.php              # Entité métier
│   └── Security/
│       └── SignatureValidator.php      # Validation API Key
├── templates/
│   ├── dashboard.twig         # Dashboard principal
│   ├── aquaponie.twig         # Visualisation complète
│   ├── tide_stats.twig        # Statistiques marées
│   └── control.twig           # Interface de contrôle
├── public/
│   ├── index.php              # Point d'entrée (routes Slim)
│   ├── post-data.php          # Alias ESP32
│   └── esp32-compat.php       # Proxy compatibilité
├── config/
│   └── (fichiers de configuration si nécessaire)
├── .env                       # Configuration (versionné !)
├── env.dist                   # Template configuration
├── composer.json              # Dépendances PHP
└── README.md                  # Documentation principale
```

### `ffp3control/` - Module Contrôle (Legacy Sécurisé)

```
ffp3control/
├── config/
│   └── Database.php           # Connexion DB
├── securecontrol/
│   ├── .htaccess              # HTTP Basic Auth
│   ├── ffp3-outputs.php       # REDIRECTION → /control
│   └── ffp3-outputs2.php      # REDIRECTION → /control-test
├── ffp3-database.php          # Fonctions DB PROD (sécurisées)
├── ffp3-database2.php         # Fonctions DB TEST (sécurisées)
├── ffp3-outputs-action.php    # API REST PROD (sécurisée)
├── ffp3-outputs-action2.php   # API REST TEST (sécurisée)
├── .env                       # Configuration (versionné)
├── autoload.php               # Chargement classes
└── README.md                  # Documentation module
```

## 🗄️ Schéma Base de Données

### Tables Principales

#### `ffp3Data` / `ffp3Data2` (Données Capteurs)
```sql
CREATE TABLE ffp3Data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    TempAir FLOAT,
    Humidite FLOAT,
    TempEau FLOAT,
    EauPotager INT,
    EauAquarium INT,
    EauReserve INT,
    diffMaree INT,
    Luminosite INT,
    etatPompeAqua BOOLEAN,
    etatPompeTank BOOLEAN,
    etatHeat BOOLEAN,
    etatUV BOOLEAN,
    bouffePetits BOOLEAN,
    bouffeGros BOOLEAN,
    reading_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reading_time (reading_time)
);
```

#### `ffp3Outputs` / `ffp3Outputs2` (GPIO/Relais)
```sql
CREATE TABLE ffp3Outputs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    board VARCHAR(50),
    gpio INT,
    state VARCHAR(100),
    UNIQUE KEY unique_gpio (gpio),
    INDEX idx_board (board)
);
```

**GPIO Spéciaux (Configuration Système)** :
- 100-116 : Paramètres système (email, seuils, horaires, durées)

#### `Boards` (ESP32 Boards)
```sql
CREATE TABLE Boards (
    board VARCHAR(50) PRIMARY KEY,
    last_request TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## 🔄 Flux de Données

### 1. Collecte Données (ESP32 → Serveur)

```
┌────────┐
│ ESP32  │
│Capteurs│
└────┬───┘
     │ POST /ffp3/ffp3datas/public/post-data
     │ Payload: JSON avec toutes les mesures
     │ Headers: X-API-KEY: signature HMAC-SHA256
     ▼
┌─────────────────┐
│PostDataController│
└────┬────────────┘
     │ validate signature
     │ SensorDataService::processSensorData()
     ▼
┌──────────────┐
│SensorRepository│
└────┬─────────┘
     │ INSERT INTO ffp3Data
     ▼
┌──────────┐
│ Database │
└──────────┘
```

### 2. Contrôle Actionneurs (ESP32 ← Serveur)

```
┌────────┐
│ ESP32  │
│Actuators│
└────┬───┘
     │ GET /api/outputs/states/1
     │ (polling toutes les 10-30 secondes)
     ▼
┌─────────────────┐
│OutputController │
└────┬────────────┘
     │ OutputService::getStatesForBoard()
     ▼
┌──────────────────┐
│OutputRepository  │
└────┬─────────────┘
     │ SELECT gpio, state FROM ffp3Outputs WHERE board=1
     ▼
┌──────────┐
│ Database │
└──────────┘
```

### 3. Visualisation Web

```
┌────────┐
│ User   │
│Browser │
└────┬───┘
     │ GET /ffp3/ffp3datas/public/aquaponie
     ▼
┌──────────────────┐
│AquaponieController│
└────┬─────────────┘
     │ SensorDataService + StatisticsService
     ▼
┌────────────────┐
│ Twig Template  │
│ + Highcharts   │
└────────────────┘
```

## 🔐 Sécurité

### Couches de Sécurité

1. **HTTP Basic Auth** : `.htaccess` sur interface de contrôle
2. **API Key HMAC** : Validation signature pour ESP32
3. **Prepared Statements** : Toutes les requêtes SQL (0 injection possible)
4. **Validation Entrées** : Filter_var, is_numeric, etc.
5. **Codes HTTP** : 400, 401, 403, 404, 500 appropriés
6. **Logs Audit** : Toutes actions critiques tracées

### Validation API Key (ESP32)

```php
// Côté ESP32 (Arduino)
String signature = hmac_sha256(payload, SECRET_KEY);
headers['X-API-KEY'] = signature;

// Côté Serveur (PHP)
$receivedSignature = $_SERVER['HTTP_X_API_KEY'];
$computedSignature = hash_hmac('sha256', $rawPayload, SECRET_KEY);
if (!hash_equals($computedSignature, $receivedSignature)) {
    http_response_code(401);
    exit('Unauthorized');
}
```

## 🌍 Environnements PROD vs TEST

### Configuration Dynamique

Le système utilise `TableConfig` pour basculer entre environnements :

```php
// Dans les routes Slim
TableConfig::setEnvironment('prod');  // Utilise ffp3Data, ffp3Outputs
TableConfig::setEnvironment('test');  // Utilise ffp3Data2, ffp3Outputs2

// Dans les repositories
$table = TableConfig::getDataTable();  // Retourne 'ffp3Data' ou 'ffp3Data2'
```

### Isolation Complète

| Aspect | PROD | TEST |
|--------|------|------|
| **Table données** | `ffp3Data` | `ffp3Data2` |
| **Table outputs** | `ffp3Outputs` | `ffp3Outputs2` |
| **Table boards** | `Boards` (partagée) | `Boards` (partagée) |
| **Routes web** | `/control`, `/aquaponie` | `/control-test`, `/aquaponie-test` |
| **Routes API** | `/api/outputs/*` | `/api/outputs-test/*` |

## 🕐 Gestion Timezone

**Timezone unifié** : `Europe/Paris` partout

- **PHP** : `date_default_timezone_set('Europe/Paris')` dans `Env::load()`
- **JavaScript (Highcharts)** : Configuration globale avec moment-timezone.js
- **Base de données** : TIMESTAMP automatiques en UTC, conversion au display

## 📊 Métriques & Monitoring

### Santé Système

- **SystemHealthService** : Vérifie boards actifs, données récentes, espace disque
- **Logs** : `cronlog.txt`, `actions.log`, `actions-test.log`
- **Alertes** : NotificationService envoie emails si anomalies

### Performance

- **Caching** : Pas encore implémenté (future: Redis/APCu)
- **Indexes DB** : Sur `reading_time`, `board`, `gpio`
- **Optimisations** : Prepared statements réutilisables, transactions

## 🔄 Cycle de Vie d'une Requête ESP32

1. **ESP32** collecte données capteurs (loop Arduino)
2. **ESP32** construit JSON payload
3. **ESP32** calcule signature HMAC
4. **ESP32** POST vers `/post-data`
5. **Serveur** valide signature
6. **Serveur** parse et valide données
7. **Serveur** INSERT dans ffp3Data
8. **Serveur** déclenche logique métier (alertes, cron pompes)
9. **Serveur** répond 200 OK
10. **ESP32** attend 10-60s selon config
11. **ESP32** GET `/api/outputs/states/1` pour récupérer consignes
12. **ESP32** applique états GPIO
13. Repeat

## 🚀 Déploiement

### Prérequis Serveur

- PHP 7.4+ avec extensions : PDO, MySQLi, curl, json
- MySQL 5.7+ ou MariaDB 10.3+
- Apache avec mod_rewrite activé
- Composer (pour installation dépendances)

### Installation

```bash
# 1. Clone repository
git clone https://github.com/oliviera999/ffp3.git
cd ffp3/ffp3datas

# 2. Installer dépendances
composer install

# 3. Configurer .env
cp env.dist .env
nano .env  # Éditer DB credentials, API_KEY, etc.

# 4. Importer structure DB
mysql -u user -p database < schema.sql

# 5. Configurer Apache Virtual Host ou Alias
# Voir documentation Apache

# 6. Tester
curl http://votre-serveur.com/ffp3/ffp3datas/public/dashboard
```

## 📝 Logs & Debug

### Fichiers de Logs

- `ffp3datas/cronlog.txt` : Logs cron pompes automatiques
- `ffp3control/actions.log` : Actions utilisateurs sur contrôles PROD
- `ffp3control/actions-test.log` : Actions utilisateurs sur contrôles TEST
- `error_log` : Erreurs PHP (Apache)

### Mode Debug

Dans `.env` :
```
APP_DEBUG=true
```

Active :
- Stack traces complètes
- Erreurs SQL détaillées
- Logs verbeux

⚠️ **JAMAIS en production !**

## 🔮 Évolutions Futures

### Court Terme
- [ ] Tests unitaires complets (PHPUnit)
- [ ] CI/CD avec GitHub Actions
- [ ] Métriques Prometheus/Grafana

### Moyen Terme
- [ ] WebSockets pour push temps réel
- [ ] PWA mobile responsive
- [ ] Multi-utilisateurs avec rôles

### Long Terme
- [ ] Machine Learning pour prédictions
- [ ] Multi-sites (gestion plusieurs aquariums)
- [ ] API publique documentée (OpenAPI)

---

**Version** : 2.0.0
**Date** : Décembre 2024
**Mainteneur** : Olivier Arnould

