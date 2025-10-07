# Plan de Migration Complète FFP3 - Architecture Moderne & Sécurisée

## 🎯 Objectif global

Migrer l'ensemble du projet FFP3 vers une architecture moderne, sécurisée et maintenable en intégrant :
- `ffp3datas` (déjà partiellement modernisé)
- `ffp3control` (legacy à migrer)
- Environnements PROD/TEST unifiés
- Sécurité renforcée
- Code DRY (Don't Repeat Yourself)

## 📊 État actuel

✅ **Complété** :
- Architecture moderne ffp3datas (Slim 4, Services, Repositories)
- Timezone unifié (Europe/Paris)
- Routes TEST créées
- Configuration ENV dynamique (TableConfig)

🔶 **Partiellement sécurisé** :
- HTTP Basic Auth via .htaccess sur securecontrol
- Mais identifiants DB en clair + requêtes SQL non sécurisées

🔴 **À migrer** :
- Module ffp3control (code legacy)
- Intégration complète PROD/TEST pour contrôles
- Interface de contrôle moderne

---

## 🗺️ PLAN DE MIGRATION (7 PHASES)

### **PHASE 1 : Préparation & Sécurisation Base** ⏱️ 1-2h

**Objectif** : Sécuriser les fondations sans casser l'existant

#### Étape 1.1 : Configuration centralisée ffp3control
- [ ] Créer `ffp3control/.env` (avec DB credentials)
- [ ] Créer `ffp3control/env.dist` (template)
- [ ] Ajouter `.env` au Git (comme ffp3datas)
- [ ] Documenter dans README

#### Étape 1.2 : Classe Database pour ffp3control
- [ ] Créer `ffp3control/config/Database.php` (similaire à ffp3datas)
- [ ] Méthode `getConnection()` avec gestion erreurs
- [ ] Support ENV pour tables (ffp3Outputs vs ffp3Outputs2)

#### Étape 1.3 : Tests de non-régression
- [ ] Vérifier que l'interface actuelle fonctionne toujours
- [ ] Tester update d'un output
- [ ] Tester requête ESP32

**✓ Point de validation** : Interface actuelle fonctionne sans changement visible

---

### **PHASE 2 : Sécurisation SQL** ⏱️ 2-3h

**Objectif** : Éliminer les risques d'injection SQL

#### Étape 2.1 : Sécuriser ffp3-database.php (PROD)
- [ ] Remplacer toutes les requêtes par prepared statements
- [ ] Utiliser Database::getConnection() au lieu de créer connections
- [ ] Ajouter gestion d'erreurs avec try/catch
- [ ] Valider les types de données (int, string, etc.)
- [ ] Tester chaque fonction une par une

**Fonctions à sécuriser** (11 fonctions) :
- [ ] `createOutput()` - Multi-query avec paramètres
- [ ] `deleteOutput($id)` - Prepared statement
- [ ] `updateOutput($id, $state)` - Prepared statement
- [ ] `getAllOutputs()` - SELECT simple
- [ ] `getPartOutputs()` - SELECT avec LIMIT
- [ ] `getAllOutputStates($board)` - SELECT avec WHERE
- [ ] `getOutputBoardById($id)` - SELECT avec WHERE
- [ ] `updateLastBoardTime($board)` - UPDATE avec NOW()
- [ ] `getAllBoards($board)` - SELECT avec WHERE
- [ ] `getBoard($board)` - SELECT avec WHERE (doublon?)
- [ ] `createBoard($board)` - INSERT avec paramètre
- [ ] `deleteBoard($board)` - DELETE avec paramètre

#### Étape 2.2 : Sécuriser ffp3-database2.php (TEST)
- [ ] Répéter le même processus (ou refactoriser pour éviter duplication)
- [ ] Tester toutes les fonctions

#### Étape 2.3 : Sécuriser ffp3-outputs-action.php
- [ ] Valider et échapper toutes les entrées GET/POST
- [ ] Ajouter vérification types (is_numeric, filter_var, etc.)
- [ ] Améliorer fonction `test_input()` (insuffisante actuellement)
- [ ] Ajouter logs des actions critiques

#### Étape 2.4 : Sécuriser ffp3-outputs-action2.php
- [ ] Répéter le processus

**✓ Point de validation** : Audit de sécurité passé, toutes les fonctions utilisent prepared statements

---

### **PHASE 3 : Création Architecture Moderne pour Contrôles** ⏱️ 4-5h

**Objectif** : Créer les classes modernes qui remplaceront le code legacy

#### Étape 3.1 : Créer Repository
**Fichier** : `ffp3datas/src/Repository/OutputRepository.php`

**Méthodes** :
- [ ] `getAllOutputs(): array` - Liste tous les outputs
- [ ] `getPartialOutputs(int $limit = 7): array` - Premiers N outputs
- [ ] `getOutputsByBoard(string $board): array` - Outputs d'un board
- [ ] `getOutputById(int $id): ?array` - Un output spécifique
- [ ] `getOutputStates(string $board): array` - États pour ESP32 (gpio => state)
- [ ] `updateOutputState(int $id, int $state): bool` - Changer état
- [ ] `updateMultipleOutputs(array $updates): bool` - Mise à jour batch
- [ ] `deleteOutput(int $id): bool` - Supprimer output
- [ ] Utiliser `TableConfig::getOutputsTable()` pour PROD/TEST

#### Étape 3.2 : Créer Repository Boards
**Fichier** : `ffp3datas/src/Repository/BoardRepository.php`

**Méthodes** :
- [ ] `getBoardByName(string $board): ?array`
- [ ] `getAllBoards(): array`
- [ ] `updateLastRequest(string $board): bool`
- [ ] `createBoard(string $board): bool`
- [ ] `deleteBoard(string $board): bool`

#### Étape 3.3 : Créer Service
**Fichier** : `ffp3datas/src/Service/OutputService.php`

**Méthodes** :
- [ ] `getOutputsForInterface(): array` - Données pour affichage
- [ ] `getSystemConfiguration(): array` - GPIO 100-116 (config)
- [ ] `updateSystemConfiguration(array $config): bool` - Maj config
- [ ] `toggleOutput(int $id): bool` - Inverser état
- [ ] `getStatesForBoard(string $board): array` - Format JSON pour ESP32
- [ ] `validateConfiguration(array $config): array` - Validation données
- [ ] Logique métier (vérifications, calculs)
- [ ] Logs des actions (via LogService)

#### Étape 3.4 : Créer Controller
**Fichier** : `ffp3datas/src/Controller/OutputController.php`

**Méthodes** :
- [ ] `showInterface()` - Afficher interface de contrôle (Twig)
- [ ] `getStatesApi(Request $req, Response $res)` - API pour ESP32
- [ ] `updateOutputApi(Request $req, Response $res)` - API update
- [ ] `updateConfigApi(Request $req, Response $res)` - API config système
- [ ] Gestion erreurs HTTP (400, 404, 500)
- [ ] Réponses JSON structurées

#### Étape 3.5 : Créer Template
**Fichier** : `ffp3datas/templates/control.twig`

**Contenu** :
- [ ] Design moderne et responsive
- [ ] Section switches (outputs 1-7)
- [ ] Section configuration système (GPIO 100-116)
- [ ] Affichage dernière connexion board
- [ ] JavaScript AJAX moderne (fetch API)
- [ ] Confirmations pour actions critiques
- [ ] Indicateurs visuels (loading, success, error)
- [ ] Compatible mobile

**✓ Point de validation** : Nouvelles classes créées et testées unitairement

---

### **PHASE 4 : Intégration Routes Modernes** ⏱️ 2-3h

**Objectif** : Créer les routes Slim qui utilisent les nouvelles classes

#### Étape 4.1 : Routes Production
**Fichier** : `ffp3datas/public/index.php`

```php
// Interface de contrôle PROD
$app->get('/control', function (Request $request, Response $response) {
    TableConfig::setEnvironment('prod');
    (new OutputController())->showInterface();
    return $response;
});

// API États pour ESP32 PROD
$app->get('/api/outputs/states/{board}', function (Request $request, Response $response, array $args) {
    TableConfig::setEnvironment('prod');
    return (new OutputController())->getStatesApi($request, $response, $args);
});

// API Update output PROD
$app->post('/api/outputs/{id}/state', function (Request $request, Response $response, array $args) {
    TableConfig::setEnvironment('prod');
    return (new OutputController())->updateOutputApi($request, $response, $args);
});

// API Update config système PROD
$app->post('/api/system/config', function (Request $request, Response $response) {
    TableConfig::setEnvironment('prod');
    return (new OutputController())->updateConfigApi($request, $response);
});
```

- [ ] Implémenter routes PROD
- [ ] Tester chaque route avec Postman/curl

#### Étape 4.2 : Routes Test
- [ ] Créer versions `-test` de toutes les routes
- [ ] Utiliser `TableConfig::setEnvironment('test')`

#### Étape 4.3 : Routes Legacy (compatibilité ESP32)
**Fichier** : `ffp3datas/public/esp32-compat.php` (nouveau)

```php
// Proxy pour anciennes URLs ESP32
// GET /ffp3/ffp3control/ffp3-outputs-action.php?action=outputs_state&board=1
// Redirige vers /api/outputs/states/1
```

- [ ] Créer proxies pour compatibilité
- [ ] Tester avec configuration ESP32 actuelle
- [ ] Documenter migration ESP32

**✓ Point de validation** : Routes modernes fonctionnelles, ESP32 compatible

---

### **PHASE 5 : Tests Parallèles PROD/TEST** ⏱️ 2h

**Objectif** : Valider que les deux environnements fonctionnent en parallèle

#### Étape 5.1 : Tests interface PROD
- [ ] Accéder à `/ffp3datas/public/control`
- [ ] Vérifier affichage outputs (lecture ffp3Outputs)
- [ ] Toggle un output
- [ ] Modifier configuration système
- [ ] Vérifier logs dans console

#### Étape 5.2 : Tests interface TEST
- [ ] Accéder à `/ffp3datas/public/control-test`
- [ ] Vérifier isolation (lecture ffp3Outputs2)
- [ ] Toggle un output TEST
- [ ] Vérifier que PROD non impacté

#### Étape 5.3 : Tests API ESP32
- [ ] Requête GET vers API PROD
- [ ] Requête GET vers API TEST
- [ ] Vérifier format JSON
- [ ] Vérifier board tracking (Boards table)

#### Étape 5.4 : Tests de charge basiques
- [ ] 10 requêtes simultanées
- [ ] Vérifier temps de réponse
- [ ] Vérifier logs d'erreurs

**✓ Point de validation** : PROD et TEST fonctionnent en parallèle sans interférence

---

### **PHASE 6 : Migration Progressive** ⏱️ 2-3h

**Objectif** : Basculer progressivement du legacy vers le moderne

#### Étape 6.1 : Redirections legacy vers moderne
**Modifier** : `ffp3control/securecontrol/ffp3-outputs.php`

Ajouter en haut :
```php
<?php
// Migration vers nouvelle interface moderne
header('Location: /ffp3/ffp3datas/public/control');
exit;
?>
```

- [ ] Créer redirection
- [ ] Tester que anciens liens fonctionnent
- [ ] Documenter le changement

#### Étape 6.2 : Période d'observation (optionnel)
- [ ] Garder les deux systèmes en parallèle 1-2 semaines
- [ ] Logger toutes les requêtes
- [ ] Identifier éventuels problèmes

#### Étape 6.3 : Migration ESP32
**Documentation à créer** : `ESP32_MIGRATION.md`

- [ ] Instructions pour changer URL dans code ESP32
- [ ] Ancienne : `ffp3control/ffp3-outputs-action.php?action=outputs_state&board=1`
- [ ] Nouvelle : `ffp3datas/public/api/outputs/states/1`
- [ ] Tester avec un board TEST avant PROD

#### Étape 6.4 : Désactivation progressive legacy
- [ ] Renommer fichiers legacy en `.old`
- [ ] Garder uniquement proxies de redirection
- [ ] Vérifier qu'aucune erreur 404

**✓ Point de validation** : Legacy redirige vers moderne, tout fonctionne

---

### **PHASE 7 : Finalisation & Documentation** ⏱️ 2-3h

**Objectif** : Nettoyer, documenter, optimiser

#### Étape 7.1 : Nettoyage code
- [ ] Supprimer fichiers legacy (garder backup)
- [ ] Supprimer code mort
- [ ] Unifier duplication PROD/TEST restante
- [ ] Formater code (PSR-12)

#### Étape 7.2 : Amélioration sécurité
- [ ] Ajouter rate limiting sur API
- [ ] Ajouter validation plus stricte
- [ ] Améliorer logs (actions utilisateurs)
- [ ] Créer table audit_log pour traçabilité

#### Étape 7.3 : Optimisations
- [ ] Ajouter cache pour états outputs (Redis/APCu si dispo)
- [ ] Optimiser requêtes SQL (EXPLAIN)
- [ ] Minifier assets JS/CSS

#### Étape 7.4 : Documentation complète
- [ ] `ffp3datas/README.md` - Vue d'ensemble projet
- [ ] `ffp3datas/ARCHITECTURE.md` - Architecture technique
- [ ] `ffp3datas/API.md` - Documentation API
- [ ] `ffp3datas/DEPLOYMENT.md` - Guide déploiement
- [ ] `ffp3datas/MAINTENANCE.md` - Guide maintenance
- [ ] Commentaires code (PHPDoc)

#### Étape 7.5 : Tests automatisés (bonus)
- [ ] Tests unitaires Repositories
- [ ] Tests unitaires Services
- [ ] Tests d'intégration API
- [ ] Tests E2E interface (Selenium/Cypress)

#### Étape 7.6 : Commit final
- [ ] Revue complète des changements
- [ ] Commit avec message détaillé
- [ ] Tag version (v2.0.0)
- [ ] Push vers GitHub

**✓ Point de validation** : Projet moderne, sécurisé, documenté, prêt pour maintenance long terme

---

## 📋 Résumé Timeline

| Phase | Description | Durée | Risque |
|-------|-------------|-------|--------|
| 1 | Préparation & Config | 1-2h | Très faible |
| 2 | Sécurisation SQL | 2-3h | Faible |
| 3 | Architecture Moderne | 4-5h | Moyen |
| 4 | Intégration Routes | 2-3h | Moyen |
| 5 | Tests PROD/TEST | 2h | Faible |
| 6 | Migration Progressive | 2-3h | Moyen |
| 7 | Finalisation | 2-3h | Très faible |
| **TOTAL** | | **15-21h** | |

---

## 🎯 Approche recommandée

### Option A : Migration complète (15-21h continues)
- Faire toutes les phases d'affilée
- Nécessite disponibilité continue
- Risque plus élevé mais plus rapide

### Option B : Migration incrémentale (préféré)
- Faire 1-2 phases par session
- Commit après chaque phase validée
- Possibilité de revenir en arrière
- **RECOMMANDÉ** pour ce projet

---

## ⚠️ Points d'attention

1. **Backup BDD avant Phase 2** (sécurisation SQL)
2. **Ne jamais travailler directement sur PROD** - toujours tester sur TEST d'abord
3. **Garder legacy actif** jusqu'à validation complète du moderne
4. **Documenter chaque modification** pour traçabilité
5. **Tester ESP32** après chaque changement d'API

---

## 📱 Configuration ESP32 à prévoir

### Anciennes URLs (legacy)
```cpp
http://iot.olution.info/ffp3/ffp3control/ffp3-outputs-action.php?action=outputs_state&board=1
```

### Nouvelles URLs (moderne)
```cpp
// PROD
http://iot.olution.info/ffp3/ffp3datas/public/api/outputs/states/1

// TEST
http://iot.olution.info/ffp3/ffp3datas/public/api/outputs-test/states/1
```

### Format réponse
**Avant** :
```json
{"2":"1","3":"0","4":"1","5":"0","6":"1","7":"0","8":"1"}
```

**Après** (identique pour compatibilité) :
```json
{"2":"1","3":"0","4":"1","5":"0","6":"1","7":"0","8":"1"}
```

---

## 🚀 Prêt à commencer ?

**Phase 1 - Étape 1.1** est prête à être lancée !

Commençons par créer la configuration centralisée pour ffp3control.

