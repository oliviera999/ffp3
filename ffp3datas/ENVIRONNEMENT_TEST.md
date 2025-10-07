# Environnement TEST - Guide d'utilisation

## Vue d'ensemble

Le système ffp3datas supporte maintenant deux environnements distincts :

- **PRODUCTION** : Utilise les tables `ffp3Data` et `ffp3Outputs`
- **TEST** : Utilise les tables `ffp3Data2` et `ffp3Outputs2`

Les deux environnements partagent la même architecture moderne (Slim 4, Twig, Services) mais travaillent sur des tables différentes, permettant de tester sans impacter la production.

## Configuration

### Fichier .env

La variable `ENV` détermine l'environnement par défaut :

```bash
# Environment: prod or test
ENV=prod
```

Pour basculer en mode TEST par défaut, modifiez :

```bash
ENV=test
```

**Note** : Il est recommandé de laisser `ENV=prod` par défaut et d'utiliser les routes TEST spécifiques.

## Routes disponibles

### Routes PRODUCTION (ffp3Data, ffp3Outputs)

| Route | Méthode | Description |
|-------|---------|-------------|
| `/aquaponie` | GET, POST | Page principale des données |
| `/dashboard` | GET | Tableau de bord |
| `/tide-stats` | GET, POST | Statistiques des marées |
| `/post-data` | POST | Endpoint API pour ESP32 |
| `/export-data` | GET | Export CSV |

### Routes TEST (ffp3Data2, ffp3Outputs2)

| Route | Méthode | Description |
|-------|---------|-------------|
| `/aquaponie-test` | GET, POST | Page principale TEST |
| `/dashboard-test` | GET | Tableau de bord TEST |
| `/tide-stats-test` | GET, POST | Statistiques marées TEST |
| `/post-data-test` | POST | Endpoint API TEST |
| `/export-data-test` | GET | Export CSV TEST |

### Fichiers legacy

Pour compatibilité avec les ESP32 existants :

- `post-ffp3-data2.php` → Redirige vers `/post-data-test` (mode TEST)
- `ffp3-data2.php` → Redirige vers `/aquaponie-test` (mode TEST)

## Configuration ESP32

### ESP32 de PRODUCTION

```cpp
const char* serverName = "http://votre-serveur.com/ffp3datas/public/post-data";
// OU (legacy)
const char* serverName = "http://votre-serveur.com/ffp3datas/post-ffp3-data.php";
```

### ESP32 de TEST

```cpp
const char* serverName = "http://votre-serveur.com/ffp3datas/public/post-data-test";
// OU (legacy)
const char* serverName = "http://votre-serveur.com/ffp3datas/post-ffp3-data2.php";
```

## Architecture technique

### Classe TableConfig

La classe `App\Config\TableConfig` gère dynamiquement les noms de tables :

```php
use App\Config\TableConfig;

// Détermine l'environnement actuel
$env = TableConfig::getEnvironment(); // 'prod' ou 'test'

// Obtient le nom de la table de données
$table = TableConfig::getDataTable(); // 'ffp3Data' ou 'ffp3Data2'

// Obtient le nom de la table des outputs
$outputs = TableConfig::getOutputsTable(); // 'ffp3Outputs' ou 'ffp3Outputs2'

// Force un environnement spécifique
TableConfig::setEnvironment('test');
```

### Composants mis à jour

Tous ces composants utilisent maintenant `TableConfig` :

- ✅ `SensorReadRepository` : Lecture des données capteurs
- ✅ `SensorRepository` : Insertion des données capteurs
- ✅ `SensorStatisticsService` : Calculs statistiques
- ✅ `PumpService` : Contrôle des pompes (GPIO)
- ✅ Tous les contrôleurs (via les repositories)

## Tests et validation

### Tester l'environnement TEST

1. **Accéder à la page de visualisation TEST** :
   ```
   http://votre-serveur.com/ffp3datas/public/aquaponie-test
   ```

2. **Poster des données de test via curl** :
   ```bash
   curl -X POST http://votre-serveur.com/ffp3datas/public/post-data-test \
     -d "api_key=fdGTMoptd5CD2ert3" \
     -d "sensor=test_sensor" \
     -d "TempAir=22.5" \
     -d "Humidite=65" \
     # ... autres paramètres
   ```

3. **Vérifier que les données vont dans ffp3Data2** :
   ```sql
   SELECT COUNT(*) FROM ffp3Data2;  -- Doit augmenter
   SELECT COUNT(*) FROM ffp3Data;   -- Ne doit PAS changer
   ```

### Vérifier la séparation PROD/TEST

```sql
-- Dernière donnée PROD
SELECT * FROM ffp3Data ORDER BY reading_time DESC LIMIT 1;

-- Dernière donnée TEST  
SELECT * FROM ffp3Data2 ORDER BY reading_time DESC LIMIT 1;
```

Les deux doivent être indépendantes.

## Workflow de développement recommandé

### Phase 1 : Développement sur TEST

1. Configurer ESP32 de test pour poster sur `post-ffp3-data2.php`
2. Développer et tester les nouvelles fonctionnalités sur `/aquaponie-test`
3. Valider que le fuseau horaire est cohérent
4. S'assurer que la PROD n'est pas impactée

### Phase 2 : Validation

1. Tester tous les endpoints TEST
2. Vérifier les graphiques et statistiques
3. Valider l'export CSV
4. Tester le contrôle des pompes (si applicable)

### Phase 3 : Migration vers PROD

Une fois validé sur TEST :

1. Les modifications sont déjà dans le code partagé
2. Il suffit d'utiliser les routes PROD normales
3. Optionnel : migrer les données de test utiles vers PROD

## Maintenance

### Nettoyer les données de TEST

```sql
-- Supprimer les données de test anciennes
DELETE FROM ffp3Data2 WHERE reading_time < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Vider complètement l'environnement TEST
TRUNCATE TABLE ffp3Data2;
TRUNCATE TABLE ffp3Outputs2;
```

### Copier des données PROD vers TEST

```sql
-- Copier les 1000 dernières mesures PROD vers TEST
INSERT INTO ffp3Data2 SELECT * FROM ffp3Data ORDER BY reading_time DESC LIMIT 1000;

-- Copier la configuration des outputs
INSERT INTO ffp3Outputs2 SELECT * FROM ffp3Outputs;
```

## Dépannage

### Les données vont dans la mauvaise table

Vérifier :
1. La variable `ENV` dans `.env`
2. Quelle route est appelée (`/aquaponie` vs `/aquaponie-test`)
3. Les logs d'erreur PHP

### Le timezone n'est pas cohérent

Le timezone est unifié via `APP_TIMEZONE=Europe/Paris` dans `.env` et s'applique aux deux environnements.

### ESP32 ne peut pas poster sur TEST

Vérifier :
1. L'URL configurée dans le code ESP32
2. Que `post-ffp3-data2.php` existe et est accessible
3. Les permissions du fichier
4. Les logs Apache/Nginx

## Évolutions futures

- Ajouter un switch visuel dans l'interface pour basculer PROD/TEST
- Automatiser la copie PROD → TEST pour les tests de régression
- Créer un script de synchronisation des schémas de tables
- Ajouter des indicateurs visuels (bandeau rouge) en mode TEST

## Support

Pour toute question sur l'environnement TEST, consulter :
- Ce document : `ENVIRONNEMENT_TEST.md`
- Configuration timezone : `TIMEZONE_UNIFICATION.md`
- README principal : `README.md`

