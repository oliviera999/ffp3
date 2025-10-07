# Plan des prochaines étapes - Projet FFP3

## État actuel

✅ **Complété** :
- Unification timezone Europe/Paris (PHP + Highcharts)
- Architecture moderne ffp3datas (Slim 4 + services)
- Système ENV pour basculer PROD/TEST (ffp3datas)
- Routes TEST créées (/aquaponie-test, /dashboard-test, etc.)
- Configuration centralisée (.env versionné)

🎯 **En cours** :
- Documentation complète du projet

## Prochaines étapes recommandées

### 🔴 ÉTAPE 1 : Sécurisation URGENTE de ffp3control (PRIORITÉ MAX)

**Pourquoi maintenant** : Actuellement, **AUCUNE SÉCURITÉ** sur le contrôle des pompes/actionneurs !

**Actions** :
1. Créer `.env` pour ffp3control avec identifiants DB
2. Remplacer toutes les requêtes SQL par prepared statements
3. Ajouter authentification basique (même simple)
4. Changer actions critiques de GET vers POST
5. Ajouter tokens CSRF

**Fichiers à modifier** :
- `ffp3control/ffp3-database.php`
- `ffp3control/ffp3-database2.php`
- `ffp3control/ffp3-outputs-action.php`
- `ffp3control/ffp3-outputs-action2.php`
- `ffp3control/securecontrol/ffp3-outputs.php`
- `ffp3control/securecontrol/ffp3-outputs2.php`

**Durée estimée** : 2-3 heures

---

### 🟠 ÉTAPE 2 : Tests de l'environnement TEST

**Objectif** : Valider que le système TEST fonctionne correctement

**Actions** :
1. Configurer un ESP32 pour pointer vers `post-ffp3-data2.php`
2. Vérifier que les données arrivent dans `ffp3Data2`
3. Tester toutes les routes TEST :
   - `/ffp3datas/public/aquaponie-test`
   - `/ffp3datas/public/dashboard-test`
   - `/ffp3datas/public/tide-stats-test`
   - `/ffp3datas/public/export-data-test`
4. Vérifier que PROD n'est pas impacté
5. Tester le basculement ENV via `.env`

**Fichiers de test** :
- Configuration ESP32 (à documenter)
- Vérifications BDD (requêtes SQL)

**Durée estimée** : 1-2 heures

---

### 🟢 ÉTAPE 3 : Migration ffp3control vers architecture moderne

**Objectif** : Intégrer ffp3control dans ffp3datas avec architecture Slim

**Option recommandée** : Intégration dans ffp3datas

#### 3.1 : Créer les classes modernes

**Nouveaux fichiers** :
```
ffp3datas/src/
├── Controller/
│   └── OutputController.php      # Gestion GPIO/outputs
├── Service/
│   └── OutputService.php         # Logique métier outputs
└── Repository/
    └── OutputRepository.php       # Accès DB outputs
```

**Fonctionnalités** :
- CRUD outputs
- Récupération états pour ESP32
- Mise à jour paramètres système
- Gestion boards

#### 3.2 : Créer les routes API

**Dans** : `ffp3datas/public/index.php`

```php
// Routes de contrôle (PROD)
$app->get('/control', [OutputController::class, 'showInterface']);
$app->get('/api/outputs/states/{board}', [OutputController::class, 'getStates']);
$app->post('/api/outputs/{id}', [OutputController::class, 'updateOutput']);
$app->post('/api/system/config', [OutputController::class, 'updateConfig']);

// Routes de contrôle (TEST)
$app->get('/control-test', [OutputController::class, 'showInterfaceTest']);
$app->get('/api/outputs-test/states/{board}', [OutputController::class, 'getStatesTest']);
// etc.
```

#### 3.3 : Créer interface Twig moderne

**Nouveaux templates** :
```
ffp3datas/templates/
└── control.twig                   # Interface de contrôle moderne
```

**Caractéristiques** :
- Design responsive
- AJAX avec gestion d'erreurs
- Confirmation actions critiques
- Affichage temps réel états

#### 3.4 : Maintenir compatibilité ESP32

**Créer proxies legacy** :
```php
// ffp3control/ffp3-outputs-action.php
<?php
// Proxy vers nouvelle API
$board = $_GET['board'] ?? 1;
header('Location: /ffp3datas/public/api/outputs/states/' . $board);
exit;
```

**Durée estimée** : 8-10 heures

---

### 🟡 ÉTAPE 4 : Améliorer l'observabilité

**Objectif** : Ajouter logs et monitoring

**Actions** :
1. Ajouter logs détaillés dans tous les services
2. Créer table `action_logs` pour audit trail
3. Ajouter page de monitoring système :
   - État connexions boards
   - Dernières actions utilisateurs
   - Erreurs récentes
4. Implémenter alertes automatiques

**Nouveaux fichiers** :
```
ffp3datas/src/
├── Service/
│   ├── AuditLogService.php
│   └── MonitoringService.php
└── Controller/
    └── MonitoringController.php
```

**Durée estimée** : 4-5 heures

---

### 🔵 ÉTAPE 5 : Finaliser la migration complète

**Objectif** : Supprimer tout le code legacy

**Actions** :
1. Vérifier que tous les endpoints legacy ont équivalent moderne
2. Documenter tous les changements
3. Créer guide de migration pour utilisateurs
4. Supprimer fichiers legacy :
   - `ffp3datas/ffp3-data.php` → Redirection ou suppression
   - `ffp3datas/ffp3-data2.php` → Redirection ou suppression
   - Anciens fichiers ffp3control (garder proxies)
5. Nettoyer `.gitignore` et structure projet

**Documentation à créer** :
- Guide utilisateur interface de contrôle
- Guide administrateur (déploiement)
- Guide développeur (architecture)
- Changelog complet

**Durée estimée** : 3-4 heures

---

### 🟣 ÉTAPE 6 : Fonctionnalités avancées (optionnel)

**À considérer après migration** :

1. **WebSockets temps réel** :
   - Mises à jour automatiques états
   - Notifications instantanées

2. **Application mobile** :
   - PWA ou app native
   - Notifications push

3. **Graphiques avancés** :
   - Prédictions ML
   - Détection anomalies
   - Recommandations automatiques

4. **Intégration domotique** :
   - API Home Assistant
   - MQTT
   - Alexa/Google Home

5. **Multi-systèmes** :
   - Gestion plusieurs aquariums
   - Comparaison performances
   - Tableaux de bord centralisés

---

## Résumé des priorités

| Étape | Priorité | Durée | Risque |
|-------|----------|-------|--------|
| 1. Sécurisation ffp3control | 🔴 URGENT | 2-3h | CRITIQUE |
| 2. Tests environnement TEST | 🟠 Haute | 1-2h | Moyen |
| 3. Migration ffp3control | 🟢 Normale | 8-10h | Faible |
| 4. Observabilité | 🟡 Basse | 4-5h | Faible |
| 5. Nettoyage legacy | 🔵 Basse | 3-4h | Très faible |
| 6. Fonctionnalités avancées | 🟣 Optionnel | Variable | Variable |

**Durée totale estimée (1-5)** : 18-24 heures

---

## Recommandation immédiate

**COMMENCER PAR** : 🔴 **ÉTAPE 1 - Sécurisation ffp3control**

**Raison** : Actuellement, n'importe qui connaissant l'URL peut :
- Éteindre les pompes
- Modifier les seuils
- Provoquer des dysfonctionnements graves

C'est un risque **critique** pour le système et les poissons !

---

## Questions pour affiner le plan

1. **Accès actuel** : Combien de personnes accèdent à l'interface de contrôle ?
2. **Fréquence** : À quelle fréquence modifiez-vous les paramètres ?
3. **ESP32** : Combien de boards ESP32 sont actifs ? (PROD + TEST)
4. **Priorités** : Préférez-vous :
   - a) Sécuriser d'abord (recommandé)
   - b) Finir migration ffp3datas puis passer à ffp3control
   - c) Autre approche ?
5. **Timeline** : Y a-t-il une deadline particulière ?

---

## URLs à retenir

### Production actuelle
- Données : `https://iot.olution.info/ffp3/ffp3datas/public/aquaponie`
- Contrôle : `https://iot.olution.info/ffp3/ffp3control/securecontrol/ffp3-outputs.php`

### Test (nouvelles routes)
- Données : `https://iot.olution.info/ffp3/ffp3datas/public/aquaponie-test`
- Stats marées : `https://iot.olution.info/ffp3/ffp3datas/public/tide-stats-test`

### Futures routes (après migration)
- Contrôle moderne : `/ffp3/ffp3datas/public/control`
- API outputs : `/ffp3/ffp3datas/public/api/outputs/states/{board}`

