# FFP3 Control - Module de contrôle à distance

Module de gestion des actionneurs (pompes, chauffage, alimentation automatique) pour le système FarmFlow3.

## 🎯 Fonctionnalités

- **Contrôle GPIO** : Activation/désactivation des sorties (pompes, relais, etc.)
- **Configuration système** : Paramétrage des seuils, horaires, durées
- **API REST** : Endpoints pour ESP32 et interface web
- **Environnements PROD/TEST** : Bascule entre `ffp3Outputs` et `ffp3Outputs2`

## 📁 Structure

```
ffp3control/
├── .env                        # Configuration (versionné !)
├── env.dist                    # Template configuration
├── autoload.php                # Autoloader (utilise vendor de ffp3datas)
├── composer.json               # Dépendances (pour référence)
├── config/
│   └── Database.php            # Connexion DB centralisée
├── ffp3-database.php           # Fonctions DB PROD (legacy → à migrer)
├── ffp3-database2.php          # Fonctions DB TEST (legacy → à migrer)
├── ffp3-outputs-action.php     # API REST PROD (legacy → à migrer)
├── ffp3-outputs-action2.php    # API REST TEST (legacy → à migrer)
└── securecontrol/
    ├── .htaccess               # HTTP Basic Auth
    ├── ffp3-outputs.php        # Interface PROD (legacy → à migrer)
    └── ffp3-outputs2.php       # Interface TEST (legacy → à migrer)
```

## 🔧 Configuration

### Variables d'environnement (.env)

```env
DB_HOST=localhost
DB_NAME=oliviera_iot
DB_USER=oliviera_iot
DB_PASS="Iot#Olution1"

# Environment: prod or test
ENV=prod
```

### Tables utilisées

| Environnement | Table Outputs | Table Boards |
|---------------|---------------|--------------|
| **PROD** | `ffp3Outputs` | `Boards` |
| **TEST** | `ffp3Outputs2` | `Boards` |

## 🔒 Sécurité

### Actuelle
- ✅ HTTP Basic Auth via `.htaccess` sur `securecontrol/`
- ✅ Configuration centralisée dans `.env`
- ⚠️ Requêtes SQL legacy (en cours de migration)

### En cours de migration vers
- 🔄 Prepared statements partout
- 🔄 Architecture moderne (Repositories, Services, Controllers)
- 🔄 Intégration dans ffp3datas (Slim 4)

## 📊 GPIO de configuration système

| GPIO | Paramètre | Description |
|------|-----------|-------------|
| 100 | mail | Adresse email notifications |
| 101 | mailNotif | Activation notifications (checked/false) |
| 102 | aqThr | Limite niveau aquarium |
| 103 | taThr | Limite niveau réserve |
| 104 | chauff | Seuil température chauffage |
| 105 | bouffeMat | Heure alimentation matin |
| 106 | bouffeMid | Heure alimentation midi |
| 107 | bouffeSoir | Heure alimentation soir |
| 111 | tempsGros | Durée nourrissage gros poissons (sec) |
| 112 | tempsPetits | Durée nourrissage petits poissons (sec) |
| 113 | tempsRemplissageSec | Temps remplissage aquarium (sec) |
| 114 | limFlood | Limite débordement |
| 115 | WakeUp | Forçage éveil |
| 116 | FreqWakeUp | Fréquence forçage éveil |

## 🔌 API Endpoints (Legacy - en cours de migration)

### GET - Récupérer états GPIO pour board
```
GET /ffp3/ffp3control/ffp3-outputs-action.php?action=outputs_state&board=1
```

**Réponse** :
```json
{"2":"1","3":"0","4":"1","5":"0","6":"1","7":"0","8":"1"}
```

### GET - Modifier un output
```
GET /ffp3/ffp3control/ffp3-outputs-action.php?action=output_update&id=5&state=1
```

### POST - Mettre à jour configuration système
```
POST /ffp3/ffp3control/ffp3-outputs-action.php
Content-Type: application/x-www-form-urlencoded

action=output_create&mail=test@example.com&aqThr=15&...
```

## 🚀 Migration en cours

Ce module est en cours de migration vers l'architecture moderne de ffp3datas :

1. ✅ Configuration centralisée (.env, Database.php)
2. 🔄 Sécurisation SQL (prepared statements)
3. 🔄 Création OutputRepository, OutputService, OutputController
4. 🔄 Intégration routes Slim dans ffp3datas
5. 🔄 Interface moderne Twig

Voir `MIGRATION_COMPLETE.md` à la racine du projet pour le plan détaillé.

## ⚠️ Notes importantes

- Le fichier `.env` est **versionné** dans ce projet (documenté dans `.keep-env`)
- Les identifiants DB sont partagés avec ffp3datas
- La table `Boards` est commune PROD/TEST (pas de suffixe)
- **Attention** : Ce module contrôle des actionneurs physiques → toute erreur peut avoir des conséquences graves !

## 📖 Utilisation

### Charger la configuration
```php
require_once __DIR__ . '/autoload.php';

use FFP3Control\Config\Database;

// Obtenir connexion DB
$pdo = Database::getConnection();

// Basculer en TEST
Database::setEnvironment('test');

// Obtenir nom de table selon environnement
$table = Database::getOutputsTable(); // 'ffp3Outputs' ou 'ffp3Outputs2'
```

### Prochaines étapes

Ce code legacy sera progressivement remplacé par l'architecture moderne intégrée dans `ffp3datas/`.

