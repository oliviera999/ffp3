# 🔧 Correction HTTP 500 ESP32 - Version 4.5.13

## 📋 Résumé

**Date** : 13 octobre 2025  
**Version** : 4.5.13  
**Type** : Correction de bug critique  
**Impact** : Communication ESP32 ↔️ Serveur

---

## 🐛 Problème Identifié

### Symptômes observés

L'ESP32 envoyait correctement les données de capteurs au serveur mais recevait systématiquement une erreur HTTP 500, alors que :

✅ Les données étaient bien insérées en base de données  
❌ Le serveur renvoyait HTTP 500 au lieu de 200  
❌ L'ESP32 effectuait 3 tentatives infructueuses (retry)  
❌ Messages de réponse contradictoires (concaténés)  

### Logs ESP32 avant correction

```
[11:27:27] [Network] POST http://iot.olution.info/ffp3/post-data-test
[11:27:29] [Network] sendFullUpdate FAILED (attempt 1/3)
[11:27:31] [Network] sendFullUpdate FAILED (attempt 2/3)
[11:27:33] [Network] sendFullUpdate FAILED (attempt 3/3)
❌ HTTP 500 - Message: "Données enregistrées avec succès<erreur>"
```

### Cause racine

Le contrôleur `PostDataController` utilisait l'ancienne approche PHP (`echo`, `header()`, `http_response_code()`) **incompatible avec l'architecture Slim 4 / PSR-7**.

#### Code problématique (avant)

```php
public function handle(): void
{
    header('Content-Type: text/plain; charset=utf-8');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo 'Méthode non autorisée';
        return;
    }
    
    // ... validation API key ...
    
    try {
        // Insertion en BDD
        $repo->insert($data);
        echo 'Données enregistrées avec succès';  // ❌ Problème
    } catch (Throwable $e) {
        http_response_code(500);
        echo 'Erreur serveur';
    }
}
```

**Problème** : Le mélange de `echo` avec l'architecture Slim provoque :
1. Le message "Données enregistrées avec succès" est écrit dans le buffer
2. Slim essaie de créer une Response PSR-7 automatiquement
3. Une erreur se produit dans le middleware ou lors de la fermeture
4. Le buffer contient les deux messages concaténés
5. Le serveur renvoie HTTP 500 au lieu de 200

---

## ✅ Solution Implémentée

### Migration complète vers PSR-7

Le contrôleur a été migré pour utiliser correctement les objets PSR-7 `Request` et `Response`, comme le font déjà les autres contrôleurs (`HeartbeatController`, etc.).

#### Code corrigé (après)

```php
public function handle(Request $request, Response $response): Response
{
    // Vérifier méthode POST
    if ($request->getMethod() !== 'POST') {
        $response->getBody()->write('Méthode non autorisée');
        return $response->withStatus(405)
                        ->withHeader('Content-Type', 'text/plain; charset=utf-8');
    }
    
    $params = $request->getParsedBody();
    
    // ... validation API key avec $params ...
    
    try {
        // Insertion en BDD
        $repo->insert($data);
        
        $response->getBody()->write('Données enregistrées avec succès');
        return $response->withStatus(200)  // ✅ HTTP 200 explicite
                        ->withHeader('Content-Type', 'text/plain; charset=utf-8');
        
    } catch (Throwable $e) {
        $response->getBody()->write('Erreur serveur');
        return $response->withStatus(500)
                        ->withHeader('Content-Type', 'text/plain; charset=utf-8');
    }
}
```

### Changements principaux

| Avant (❌) | Après (✅) |
|-----------|----------|
| `handle(): void` | `handle(Request $request, Response $response): Response` |
| `$_POST['key']` | `$request->getParsedBody()['key']` |
| `echo 'message'` | `$response->getBody()->write('message')` |
| `http_response_code(200)` | `$response->withStatus(200)` |
| `header('Content-Type: ...')` | `$response->withHeader('Content-Type', '...')` |
| Pas de valeur de retour | `return $response` |

---

## 🎯 Résultats Attendus

### Après déploiement

✅ L'ESP32 reçoit maintenant **HTTP 200** lors d'une insertion réussie  
✅ Fin des erreurs de retry inutiles  
✅ Message de réponse propre et unique  
✅ Cohérence avec l'architecture PSR-7  
✅ Pas de risque de duplication de données  

### Logs ESP32 après correction (attendu)

```
[11:30:15] [Network] POST http://iot.olution.info/ffp3/post-data-test
[11:30:16] [Network] HTTP 200 OK
✅ Données enregistrées avec succès
```

---

## 🧪 Tests à Effectuer

### 1. Test endpoint PROD

```bash
curl -X POST http://iot.olution.info/ffp3/post-data \
  -d "api_key=VOTRE_CLE_API" \
  -d "sensor=test" \
  -d "version=4.5.13" \
  -d "TempAir=25.5" \
  -d "Humidite=60.0" \
  -d "TempEau=20.0" \
  -d "EauPotager=50.0" \
  -d "EauAquarium=75.0" \
  -d "EauReserve=100.0" \
  -d "diffMaree=0.5" \
  -d "Luminosite=800" \
  -d "etatPompeAqua=0" \
  -d "etatPompeTank=0" \
  -d "etatHeat=0" \
  -d "etatUV=0" \
  -d "bouffeMatin=0" \
  -d "bouffeMidi=0" \
  -d "bouffePetits=0" \
  -d "bouffeGros=0" \
  -d "aqThreshold=50" \
  -d "tankThreshold=30" \
  -d "chauffageThreshold=18" \
  -d "mail=" \
  -d "mailNotif=" \
  -d "resetMode=0" \
  -d "bouffeSoir=0" \
  -v
```

**Résultat attendu** :
- Status HTTP : `200 OK`
- Body : `Données enregistrées avec succès`
- Headers : `Content-Type: text/plain; charset=utf-8`

### 2. Test endpoint TEST

Même commande avec l'URL : `http://iot.olution.info/ffp3/post-data-test`

### 3. Vérifier les logs

```bash
# Logs applicatifs
tail -f var/logs/app.log

# Logs cron
tail -f cronlog.txt
```

Chercher les lignes :
```
[info] Données capteurs insérées {"sensor":"ESP32_xxxxx","version":"x.x.x"}
```

### 4. Vérifier en base de données

```sql
-- Dernières entrées dans la table TEST
SELECT * FROM ffp3Data2 ORDER BY id DESC LIMIT 5;

-- Dernières entrées dans la table PROD
SELECT * FROM ffp3Data ORDER BY id DESC LIMIT 5;
```

---

## 📊 Impact sur le Système

### Avant la correction

```
ESP32 → POST /post-data-test
  ↓
Serveur : INSERT en BDD ✅
  ↓
Serveur : echo "Données enregistrées"
  ↓
Slim : Tentative de créer Response PSR-7
  ↓
Erreur dans le middleware ❌
  ↓
Buffer contient : "Données enregistrées<erreur>"
  ↓
HTTP 500 renvoyé ❌
  ↓
ESP32 : Retry (3 tentatives)
  ↓
Risque de duplication ⚠️
```

### Après la correction

```
ESP32 → POST /post-data-test
  ↓
Serveur : INSERT en BDD ✅
  ↓
Serveur : $response->getBody()->write("Données enregistrées")
  ↓
Serveur : return $response->withStatus(200) ✅
  ↓
HTTP 200 renvoyé ✅
  ↓
ESP32 : Succès, pas de retry
  ↓
Données uniques ✅
```

---

## 🔍 Vérification de la Cohérence

Cette correction aligne `PostDataController` avec les autres contrôleurs du projet :

| Contrôleur | Avant | Après |
|------------|-------|-------|
| `HeartbeatController` | ✅ PSR-7 | ✅ PSR-7 |
| `RealtimeApiController` | ✅ PSR-7 | ✅ PSR-7 |
| `PostDataController` | ❌ Legacy PHP | ✅ PSR-7 |
| `OutputController` | ✅ PSR-7 | ✅ PSR-7 |

---

## 📝 Notes Techniques

### Compatibilité

- ✅ Compatible avec ESP32 existant (pas de changement côté firmware)
- ✅ Compatible avec l'API actuelle (mêmes endpoints)
- ✅ Compatible avec la validation HMAC existante
- ✅ Compatible avec les deux environnements (PROD/TEST)

### Déploiement

1. Tester en environnement TEST d'abord : `/post-data-test`
2. Vérifier les logs ESP32 et serveur
3. Si OK, tester en PROD : `/post-data`
4. Surveiller les logs pendant 24h

### Rollback

En cas de problème, revenir à la version 4.5.12 :

```bash
git checkout v4.5.12 src/Controller/PostDataController.php
```

---

## 📚 Références

- **Slim Framework PSR-7** : https://www.slimframework.com/docs/v4/concepts/value-objects.html
- **PSR-7 HTTP Message** : https://www.php-fig.org/psr/psr-7/
- **HeartbeatController** : Exemple de référence d'implémentation PSR-7 correcte

---

## ✅ Checklist de Déploiement

- [x] Code corrigé dans `src/Controller/PostDataController.php`
- [x] Version incrémentée : `4.5.12` → `4.5.13`
- [x] CHANGELOG.md mis à jour
- [x] Documentation technique créée
- [ ] Tests manuels endpoint TEST effectués
- [ ] Tests manuels endpoint PROD effectués
- [ ] Vérification logs serveur
- [ ] Vérification logs ESP32
- [ ] Vérification données en BDD
- [ ] Surveillance 24h post-déploiement

---

**Contact** : En cas de question ou problème, se référer à ce document et aux logs.

