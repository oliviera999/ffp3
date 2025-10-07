# Analyse du module ffp3control

## Vue d'ensemble

Le module `ffp3control` est une **interface web de contrôle à distance** pour gérer les actionneurs du système aquaponique FarmFlow3. Il permet de :
- Activer/désactiver les pompes et autres équipements
- Configurer les paramètres du système (seuils, horaires d'alimentation, etc.)
- Suivre l'état de connexion des boards ESP32

## Architecture actuelle

### Structure des fichiers

```
ffp3control/
├── index.php                    # Redirection 301 vers securecontrol
├── ffp3-database.php            # Fonctions DB pour PROD (ffp3Outputs)
├── ffp3-database2.php           # Fonctions DB pour TEST (ffp3Outputs2)
├── ffp3-outputs-action.php      # API REST pour PROD
├── ffp3-outputs-action2.php     # API REST pour TEST
├── ffp3-style.css               # Styles pour les switches
└── securecontrol/
    ├── index.php                # Redirection
    ├── ffp3-outputs.php         # Interface PROD
    ├── ffp3-outputs2.php        # Interface TEST
    └── test/, test2/            # Dossiers de test (vides ou legacy)
```

### Type d'architecture

**Architecture legacy procedural PHP** :
- Code procédural (pas orienté objet)
- Connexions MySQL natives (mysqli)
- Pas de framework
- Aucune séparation des responsabilités
- Code dupliqué entre versions PROD et TEST

## Fonctionnalités

### 1. Gestion des outputs (GPIO)

**Tables utilisées** :
- `ffp3Outputs` (PROD)
- `ffp3Outputs2` (TEST)

**Structure** :
```sql
id, name, board, gpio, state
```

**GPIO spéciaux (configuration système)** :
| GPIO | Paramètre | Description |
|------|-----------|-------------|
| 100 | mail | Adresse email pour notifications |
| 101 | mailNotif | Activation notifications (checked/false) |
| 102 | aqThr | Limite niveau aquarium |
| 103 | taThr | Limite niveau réserve |
| 104 | chauff | Seuil température chauffage |
| 105 | bouffeMat | Heure alimentation matin |
| 106 | bouffeMid | Heure alimentation midi |
| 107 | bouffeSoir | Heure alimentation soir |
| 111 | tempsGros | Durée nourrissage gros poissons |
| 112 | tempsPetits | Durée nourrissage petits poissons |
| 113 | tempsRemplissageSec | Temps remplissage aquarium (sec) |
| 114 | limFlood | Limite débordement |
| 115 | WakeUp | Forçage éveil |
| 116 | FreqWakeUp | Fréquence forçage éveil |

### 2. API REST

**Endpoints GET** :
- `?action=outputs_state&board=X` : Récupère états GPIO pour board X (utilisé par ESP32)
- `?action=output_update&id=X&state=Y` : Change état d'un output
- `?action=output_delete&id=X` : Supprime un output

**Endpoint POST** :
- `action=output_create` : Met à jour tous les paramètres système

### 3. Interface utilisateur

**Pages** :
- `securecontrol/ffp3-outputs.php` : Interface PROD
- `securecontrol/ffp3-outputs2.php` : Interface TEST

**Composants** :
- Switches interactifs (toggle ON/OFF pour GPIO 1-7)
- Formulaire de configuration système (tous les paramètres)
- Affichage dernière requête board
- JavaScript AJAX pour interactions temps réel

### 4. Gestion des boards

**Table** : `Boards`
```sql
board, last_request
```

Permet de tracker la dernière connexion de chaque board ESP32.

## Problèmes identifiés

### 🔴 Sécurité CRITIQUE

1. **Identifiants en clair dans le code** :
   ```php
   $username = "oliviera_iot";
   $password = "Iot#Olution1";
   ```

2. **Aucune authentification** :
   - Pas de login requis pour accéder à l'interface
   - Seule "sécurité" : dossier `securecontrol/` (sécurité par obscurité)

3. **Injections SQL** :
   - Variables non échappées dans requêtes SQL
   - Concaténation directe de `$_POST` et `$_GET`
   ```php
   $sql = "UPDATE ffp3Outputs SET state='" . $state . "' WHERE id='". $id .  "'";
   ```

4. **CSRF** :
   - Aucun token CSRF
   - Actions critiques via GET (modification d'états)

### 🟠 Problèmes d'architecture

1. **Duplication massive** :
   - `ffp3-database.php` vs `ffp3-database2.php` : code 100% identique sauf noms tables
   - Même duplication pour actions et interfaces

2. **Pas de gestion d'erreurs** :
   ```php
   $conn->close(); // Après un return !
   ```

3. **Timezone hardcodé** :
   ```php
   $row_reading_time = date("Y-m-d H:i:s", strtotime("$row_reading_time - 1 hours"));
   ```

4. **Aucune validation** :
   - Pas de validation des valeurs numériques
   - `test_input()` insuffisant (htmlspecialchars ne protège pas SQL)

5. **Code mixing** :
   - HTML, PHP, JavaScript mélangés
   - Logique business dans les templates

### 🟡 Problèmes de maintenabilité

1. **Pas de système de routing**
2. **Pas de logs**
3. **Erreurs masquées** (silencieuses)
4. **Aucun test unitaire**
5. **Documentation inexistante**

## Dépendances avec ffp3datas

**Lien direct** : Les ESP32 utilisent les valeurs de `ffp3Outputs` pour :
- Récupérer les seuils de déclenchement (pompes, chauffage)
- Connaître les horaires d'alimentation
- Obtenir les états souhaités des GPIO

**Synchronisation** : L'ESP32 interroge régulièrement :
```
GET /ffp3/ffp3control/ffp3-outputs-action.php?action=outputs_state&board=1
```

**Utilisé par** : `PumpService` dans `ffp3datas` (pour vérifier cohérence états).

## Recommandations pour la migration

### Phase 1 : Sécurisation d'urgence (PRIORITAIRE)

1. **Déplacer identifiants dans .env**
2. **Ajouter authentification** (même simple)
3. **Utiliser prepared statements** partout
4. **Ajouter tokens CSRF**
5. **Passer actions critiques en POST**

### Phase 2 : Intégration dans l'architecture moderne

**Option A : Intégrer dans ffp3datas** (RECOMMANDÉ)

Créer des routes Slim dans `ffp3datas/public/index.php` :
```php
// Routes contrôle GPIO
$app->get('/control/outputs', [OutputController::class, 'showInterface']);
$app->get('/api/outputs/{board}', [OutputController::class, 'getStates']);
$app->post('/api/outputs/{id}', [OutputController::class, 'updateState']);
```

**Avantages** :
- Centralisation dans un seul projet moderne
- Réutilisation de `PumpService` existant
- Authentification unifiée
- Gestion erreurs cohérente

**Option B : Module séparé** (si besoin d'isolation)

Créer un projet Slim séparé avec même structure que ffp3datas.

### Phase 3 : Nouvelles fonctionnalités

1. **API RESTful complète** (JSON responses)
2. **WebSockets** pour mises à jour temps réel
3. **Historique des actions** (audit log)
4. **Permissions par utilisateur**
5. **Interface responsive moderne**

## Estimation de charge

| Tâche | Complexité | Durée estimée |
|-------|-----------|---------------|
| Sécurisation urgente | Moyenne | 2-3h |
| Création OutputController | Moyenne | 3-4h |
| Migration vers Slim routes | Moyenne | 4-5h |
| Interface moderne (Twig) | Faible | 2-3h |
| Tests | Moyenne | 3-4h |
| Documentation | Faible | 1-2h |
| **TOTAL** | | **15-21h** |

## Notes importantes

⚠️ **ATTENTION** : Ce module contrôle des actionneurs physiques (pompes, chauffage). Toute erreur peut avoir des conséquences graves (noyade poissons, surchauffe, etc.).

🔒 **SÉCURITÉ** : La sécurisation doit être la PRIORITÉ ABSOLUE avant toute autre modification.

📱 **ESP32** : Maintenir compatibilité avec endpoints existants pendant migration (utiliser redirections/proxies).

