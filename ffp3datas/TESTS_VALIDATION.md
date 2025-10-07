# Tests de Validation - Migration Complète

## 🎯 Objectif

Valider que tous les systèmes (PROD et TEST) fonctionnent correctement après la migration.

## ✅ Tests à effectuer

### 1. Tests Interface Web PROD

- [ ] **Accès** : https://iot.olution.info/ffp3/ffp3datas/public/control
- [ ] Vérifier l'affichage des outputs principaux (7 switches)
- [ ] Vérifier l'affichage de la configuration système (GPIO 100-116)
- [ ] Vérifier le statut du board (actif/inactif, dernière requête)
- [ ] **Action** : Toggle un switch → doit changer l'état
- [ ] **Action** : Modifier un paramètre de config → doit sauvegarder
- [ ] Vérifier les messages de succès/erreur
- [ ] Tester sur mobile (responsive)

### 2. Tests Interface Web TEST

- [ ] **Accès** : https://iot.olution.info/ffp3/ffp3datas/public/control-test
- [ ] Vérifier isolation des données (utilise ffp3Outputs2)
- [ ] **Action** : Toggle un switch TEST
- [ ] Vérifier que PROD n'est pas impacté
- [ ] **Action** : Modifier config TEST
- [ ] Vérifier séparation PROD/TEST

### 3. Tests API ESP32 PROD

#### Test 1 : Récupération des états
```bash
curl https://iot.olution.info/ffp3/ffp3datas/public/api/outputs/states/1
```

**Résultat attendu** :
```json
{"2":"1","3":"0","4":"1","5":"0","6":"1","7":"0","8":"1"}
```

#### Test 2 : Mise à jour d'un output
```bash
curl -X POST https://iot.olution.info/ffp3/ffp3datas/public/api/outputs/5/state \
  -H "Content-Type: application/json" \
  -d '{"state": 1}'
```

**Résultat attendu** :
```json
{"success":true,"message":"Output state updated successfully"}
```

### 4. Tests API ESP32 TEST

#### Test 1 : Récupération des états TEST
```bash
curl https://iot.olution.info/ffp3/ffp3datas/public/api/outputs-test/states/1
```

#### Test 2 : Mise à jour output TEST
```bash
curl -X POST https://iot.olution.info/ffp3/ffp3datas/public/api/outputs-test/5/state \
  -H "Content-Type: application/json" \
  -d '{"state": 1}'
```

### 5. Tests Compatibilité ESP32 (Proxy Legacy)

#### Test avec anciennes URLs
```bash
# Devrait rediriger vers nouvelle API
curl https://iot.olution.info/ffp3/ffp3datas/public/esp32-compat.php?action=outputs_state&board=1
```

**Résultat attendu** : Même format qu'avant (compatible)

### 6. Tests Redirections Legacy

#### Test interface legacy → moderne
```bash
# Ancien : /ffp3/ffp3control/securecontrol/ffp3-outputs.php
# Devrait rediriger vers : /ffp3/ffp3datas/public/control
curl -I https://iot.olution.info/ffp3/ffp3control/securecontrol/ffp3-outputs.php
```

**Résultat attendu** : HTTP 301 ou 302 redirect

### 7. Tests Sécurité

- [ ] Tester injection SQL (devrait être bloquée)
  ```bash
  curl -X POST https://iot.olution.info/ffp3/ffp3datas/public/api/outputs/1/state \
    -H "Content-Type: application/json" \
    -d '{"state": "1; DROP TABLE ffp3Outputs;"}'
  ```
  **Attendu** : Validation error, pas d'exécution SQL

- [ ] Tester valeurs négatives (devrait être bloquée)
  ```bash
  curl -X POST https://iot.olution.info/ffp3/ffp3datas/public/api/outputs/1/state \
    -H "Content-Type: application/json" \
    -d '{"state": -1}'
  ```
  **Attendu** : Validation error

- [ ] Tester ID invalide (devrait retourner 404)
  ```bash
  curl -X POST https://iot.olution.info/ffp3/ffp3datas/public/api/outputs/99999/state \
    -H "Content-Type: application/json" \
    -d '{"state": 1}'
  ```
  **Attendu** : HTTP 404

### 8. Tests Logs

- [ ] Vérifier que les actions sont loguées
  ```bash
  tail -f ffp3control/actions.log
  tail -f ffp3control/actions-test.log
  ```

- [ ] Effectuer une action et vérifier le log
- [ ] Vérifier format du log (timestamp, IP, action, data)

### 9. Tests Base de Données

#### Vérifier séparation PROD/TEST

**Requête SQL PROD** :
```sql
SELECT COUNT(*) FROM ffp3Outputs;
```

**Requête SQL TEST** :
```sql
SELECT COUNT(*) FROM ffp3Outputs2;
```

**Attendu** : Nombres différents, isolation complète

### 10. Tests Performance

- [ ] Mesurer temps de réponse API
  ```bash
  time curl https://iot.olution.info/ffp3/ffp3datas/public/api/outputs/states/1
  ```
  **Attendu** : < 500ms

- [ ] Tester 10 requêtes simultanées
  ```bash
  for i in {1..10}; do
    curl https://iot.olution.info/ffp3/ffp3datas/public/api/outputs/states/1 &
  done
  wait
  ```
  **Attendu** : Toutes réussissent

### 11. Tests Board Tracking

- [ ] ESP32 fait une requête
- [ ] Vérifier mise à jour `last_request` dans table `Boards`
- [ ] Vérifier statut "actif" sur interface web
- [ ] Attendre 15 minutes
- [ ] Vérifier statut passe à "inactif"

## 📊 Résultats Attendus

| Test | PROD | TEST | Notes |
|------|------|------|-------|
| Interface web | ✅ | ✅ | Responsive, messages clairs |
| API états GPIO | ✅ | ✅ | Format JSON correct |
| API update output | ✅ | ✅ | Validation fonctionnelle |
| API config système | ✅ | ✅ | Transaction atomique |
| Proxy compatibilité | ✅ | N/A | Redirige correctement |
| Sécurité SQL | ✅ | ✅ | Aucune injection possible |
| Logs | ✅ | ✅ | Toutes actions tracées |
| Isolation DB | ✅ | ✅ | Aucune interférence |
| Performance | ✅ | ✅ | < 500ms par requête |

## 🐛 Problèmes Connus à Tester

1. **Timezone** : Vérifier que tous les timestamps sont en Europe/Paris
2. **Charset** : Vérifier UTF-8 partout (accents, émojis)
3. **Sessions** : Vérifier que HTTP Basic Auth fonctionne toujours
4. **CORS** : Si appels depuis autres domaines (prévoir si besoin)

## 📝 Notes de Test

Date de test : _________________
Testeur : _________________

### Observations :
- 
- 
- 

### Problèmes identifiés :
- 
- 
- 

### Actions correctives :
- 
- 
- 

## ✅ Validation Finale

- [ ] Tous les tests PROD passent
- [ ] Tous les tests TEST passent
- [ ] Isolation PROD/TEST confirmée
- [ ] Sécurité validée
- [ ] Performance acceptable
- [ ] ESP32 peut migrer sans problème

**Signature validation** : _________________
**Date** : _________________

---

## 🚀 Prochaines Étapes Après Validation

1. **Notification ESP32** : Informer de la migration disponible
2. **Période transition** : 3 mois avec proxy compatibilité
3. **Migration ESP32** : Mise à jour firmware
4. **Désactivation proxy** : Après migration complète
5. **Suppression legacy** : Archivage code ancien

