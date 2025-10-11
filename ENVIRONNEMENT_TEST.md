# Documentation Environnement TEST/PROD

**Date de création** : 08/10/2025  
**Version** : 1.0

---

## 🎯 Vue d'ensemble

Le système FFP3 Datas dispose maintenant de **deux environnements complètement séparés** :

- **PRODUCTION (PROD)** : Système en fonctionnement réel, données importantes
- **TEST** : Environnement de développement et tests, données de validation

Les deux environnements partagent **le même code** mais utilisent **des tables de base de données distinctes**.

---

## 🗄️ Tables de Base de Données

### Environnement PRODUCTION
- `ffp3Data` : Données des capteurs
- `ffp3Outputs` : États des GPIO/relais
- `Boards` : **Partagée** entre PROD et TEST

### Environnement TEST
- `ffp3Data2` : Données des capteurs de test
- `ffp3Outputs2` : États des GPIO/relais de test
- `Boards` : **Partagée** entre PROD et TEST

---

## ⚙️ Configuration

### Fichier .env

La variable `ENV` dans le fichier `.env` détermine l'environnement **par défaut** :

```env
# Environment: prod or test
# prod: utilise ffp3Data, ffp3Outputs
# test: utilise ffp3Data2, ffp3Outputs2
ENV=prod
```

**Note** : Cette variable définit l'environnement par défaut, mais les routes peuvent la surcharger.

---

## 🌐 URLs et Routes

### 📊 Visualisation des Données

| Fonctionnalité | PRODUCTION | TEST |
|---------------|------------|------|
| **Dashboard** | `/dashboard` | `/dashboard-test` |
| **Aquaponie** | `/aquaponie` | `/aquaponie-test` |
| **Statistiques marées** | `/tide-stats` | `/tide-stats-test` |
| **Export CSV** | `/export-data` | `/export-data-test` |

### 🎛️ Contrôle GPIO/Outputs

| Fonctionnalité | PRODUCTION | TEST |
|---------------|------------|------|
| **Interface de contrôle** | `/control` | `/control-test` |

### 📥 API pour ESP32

| Fonctionnalité | PRODUCTION | TEST |
|---------------|------------|------|
| **Publication données** | `POST /post-data` | `POST /post-data-test` |
| **État outputs** | `GET /api/outputs/state` | `GET /api/outputs-test/state` |
| **Toggle GPIO** | `GET /api/outputs/toggle?gpio=X&state=Y` | `GET /api/outputs-test/toggle?gpio=X&state=Y` |
| **Paramètres** | `POST /api/outputs/parameters` | `POST /api/outputs-test/parameters` |

### 🔗 URLs Complètes

**PRODUCTION** :
```
https://iot.olution.info/ffp3/ffp3datas/dashboard
https://iot.olution.info/ffp3/ffp3datas/aquaponie
https://iot.olution.info/ffp3/ffp3datas/control
https://iot.olution.info/ffp3/ffp3datas/post-data
```

**TEST** :
```
https://iot.olution.info/ffp3/ffp3datas/dashboard-test
https://iot.olution.info/ffp3/ffp3datas/aquaponie-test
https://iot.olution.info/ffp3/ffp3datas/control-test
https://iot.olution.info/ffp3/ffp3datas/post-data-test
```

---

## 🤖 Configuration ESP32

### Environnement PRODUCTION

Dans votre code Arduino/ESP32, utilisez :

```cpp
const char* serverName = "https://iot.olution.info/ffp3/ffp3datas/post-data";
```

### Environnement TEST

Pour tester sans impacter la production :

```cpp
const char* serverName = "https://iot.olution.info/ffp3/ffp3datas/post-data-test";
```

### Récupération des états GPIO

**PRODUCTION** :
```cpp
const char* outputsUrl = "https://iot.olution.info/ffp3/ffp3datas/api/outputs/state";
```

**TEST** :
```cpp
const char* outputsUrl = "https://iot.olution.info/ffp3/ffp3datas/api/outputs-test/state";
```

---

## 🔧 Fichiers Legacy (Compatibilité)

Les anciens fichiers PHP redirigent automatiquement vers les nouvelles routes :

### Redirection POST données
```
ffp3datas/post-ffp3-data2.php → /post-data-test (moderne)
```

### Redirection visualisation
```
ffp3datas/ffp3-data2.php → /aquaponie-test (moderne)
```

**Note** : Ces fichiers sont conservés pour compatibilité avec d'anciens ESP32 non mis à jour.

---

## 🏗️ Architecture Technique

### Classe TableConfig

La classe `App\Config\TableConfig` gère dynamiquement les noms de tables :

```php
use App\Config\TableConfig;

// Forcer l'environnement TEST
TableConfig::setEnvironment('test');

// Récupérer le nom de la table selon l'environnement
$dataTable = TableConfig::getDataTable();     // 'ffp3Data2' en test
$outputsTable = TableConfig::getOutputsTable(); // 'ffp3Outputs2' en test
```

### Méthodes disponibles

```php
TableConfig::setEnvironment('test' | 'prod');   // Forcer un environnement
TableConfig::getEnvironment();                  // Récupérer l'environnement actuel
TableConfig::getDataTable();                    // Nom table données capteurs
TableConfig::getOutputsTable();                 // Nom table GPIO/outputs
TableConfig::isTest();                          // Booléen : true si TEST
```

---

## 🔄 Workflow de Développement

### 1. Développer en TEST

```
1. Modifier le code
2. Tester sur /aquaponie-test, /control-test
3. Vérifier que les données vont dans ffp3Data2
4. Valider les fonctionnalités
```

### 2. Déployer en PROD

```
1. Si tout fonctionne en TEST
2. Les routes PROD utilisent automatiquement le même code
3. Aucune modification nécessaire (tables gérées dynamiquement)
4. Tester rapidement en PROD pour confirmer
```

### 3. ESP32 de Test

```
1. Configurer un ESP32 dédié aux tests
2. Le faire pointer vers /post-data-test
3. Développer et tester sans risque
4. Une fois validé, mettre à jour l'ESP32 de production
```

---

## 🧪 Tests de Validation

### Vérifier la séparation des environnements

**Test 1** : Vérifier les tables utilisées

```sql
-- Insérer une donnée via /post-data-test
-- Vérifier qu'elle arrive dans ffp3Data2 et PAS dans ffp3Data

SELECT COUNT(*) FROM ffp3Data WHERE reading_time > NOW() - INTERVAL 5 MINUTE;
-- Doit être 0

SELECT COUNT(*) FROM ffp3Data2 WHERE reading_time > NOW() - INTERVAL 5 MINUTE;
-- Doit être 1 (votre test)
```

**Test 2** : Vérifier les graphiques

```
1. Ouvrir /aquaponie (PROD)
2. Noter les dernières valeurs affichées
3. Ouvrir /aquaponie-test (TEST)
4. Les valeurs doivent être différentes
```

**Test 3** : Vérifier le contrôle GPIO

```
1. Toggle un GPIO sur /control-test
2. Vérifier ffp3Outputs2 (pas ffp3Outputs)
3. L'état doit changer uniquement dans ffp3Outputs2
```

---

## ⚠️ Précautions Importantes

### ❌ Ce qu'il NE FAUT PAS faire

1. **Ne pas hardcoder les noms de tables** : Toujours utiliser `TableConfig`
2. **Ne pas mélanger les environnements** : Un ESP32 = un environnement
3. **Ne pas tester en PROD** : Développer d'abord en TEST
4. **Ne pas oublier de basculer** : Vérifier qu'on est dans le bon environnement

### ✅ Bonnes Pratiques

1. **Développer en TEST** puis déployer en PROD
2. **Documenter les changements** dans CHANGELOG
3. **Tester après chaque modification** importante
4. **Sauvegarder les données PROD** régulièrement
5. **Utiliser des ESP32 séparés** pour PROD et TEST

---

## 🔐 Sécurité et Accès

### Authentification

Les interfaces de contrôle (`/control` et `/control-test`) peuvent être protégées par :

- **HTTP Basic Authentication** (`.htaccess`)
- **Authentification applicative** (future évolution)

**Note** : Actuellement, la sécurité est à configurer au niveau Apache/Nginx.

---

## 📊 Monitoring et Logs

### Logs de l'application

Les logs sont enregistrés dans :
```
ffp3datas/cronlog.txt
```

Pour filtrer par environnement, rechercher :
```bash
grep "TEST" cronlog.txt   # Actions en environnement TEST
grep "PROD" cronlog.txt   # Actions en environnement PROD
```

---

## 🆘 Dépannage

### Problème : Les données n'apparaissent pas dans TEST

**Solution** :
1. Vérifier que l'ESP32 poste bien vers `/post-data-test`
2. Vérifier que `ffp3Data2` existe dans la base de données
3. Consulter les logs pour voir si les données sont reçues

### Problème : Les graphiques TEST affichent des données PROD

**Solution** :
1. Vider le cache du navigateur
2. Vérifier l'URL (doit finir par `-test`)
3. Vérifier que `TableConfig::setEnvironment('test')` est bien appelé

### Problème : Toggle GPIO ne fonctionne pas en TEST

**Solution** :
1. Vérifier que `ffp3Outputs2` existe
2. Vérifier que les GPIO ont des entrées dans `ffp3Outputs2`
3. Consulter les logs API

---

## 📚 Références

- **Configuration** : `ffp3datas/.env`
- **TableConfig** : `ffp3datas/src/Config/TableConfig.php`
- **Routes** : `ffp3datas/public/index.php`
- **Documentation timezone** : `ffp3datas/RESUME_MODIFICATIONS.md`
- **Améliorations** : `ffp3datas/TODO_AMELIORATIONS_CONTROL.md`

---

## 🎓 Pour Aller Plus Loin

### Créer un troisième environnement (DEV)

Si besoin d'un environnement de développement local :

1. Créer `ffp3Data3` et `ffp3Outputs3`
2. Ajouter `'dev'` dans `TableConfig`
3. Ajouter routes `-dev` dans `index.php`

### Automatiser les tests

Créer des scripts PHPUnit pour :
- Tester la séparation PROD/TEST
- Valider les API
- Vérifier l'intégrité des données

---

*Document créé le 08/10/2025 - À mettre à jour lors des évolutions*
