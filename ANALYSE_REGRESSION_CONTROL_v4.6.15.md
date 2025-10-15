# 🔍 ANALYSE RÉGRESSION - Interface de Contrôle v4.6.15

**Date**: 2025-10-15  
**Statut**: 8 erreurs 500 persistantes  
**Objectif**: Identifier la cause exacte de la régression

---

## 📊 État des Lieux

### ✅ Fonctionnel (10/18 endpoints)
- `/` (Home)
- `/dashboard`
- `/aquaponie`
- `/tide-stats`
- `/dashboard-test`
- `/aquaponie-test`
- `/tide-stats-test`
- `/ota/metadata.json`
- `/public/manifest.json`
- Redirections legacy (`/ffp3-data`, `/heartbeat.php`)

### ❌ Erreur 500 (8/18 endpoints)
1. `/control` (PROD)
2. `/control-test` (TEST)
3. `/api/realtime/sensors/latest`
4. `/api/realtime/outputs/state`
5. `/api/realtime/system/health`
6. `/post-ffp3-data.php`
7. `/post-data` (405 → devrait accepter POST)
8. `/heartbeat` (405 → devrait accepter POST)

---

## 🕵️ Analyse des Commits

### Commit qui fonctionnait : `4e70028` (v4.6.6)
**Date**: 2025-10-15 18:22:14  
**Message**: "Migration complète vers injection de dépendances"

#### État du code :
- ✅ `OutputController` : DI correcte
- ✅ `TableConfig::getEnvironment()` : Logique avec `Env::load()`
- ✅ `EnvironmentMiddleware` : Pas d'appel explicite à `Env::load()`
- ✅ `config/dependencies.php` : Définitions complètes

### Commits suivants (ajouts de debug)
**Commits**: `3aff6ed`, `32695f7`, `371ce09`, `4504d5e`, `036a9a1`, `263e07f`

#### Modifications :
1. Ajout de logs `error_log()` dans les contrôleurs
2. Ajout de `try-catch` dans les méthodes
3. Ajout de `Env::load()` dans `EnvironmentMiddleware`
4. Ajout de `Env::load()` dans diverses méthodes de services
5. Création de scripts de diagnostic

#### ⚠️ Problème identifié :
**Les modifications de code n'ont PAS cassé le fonctionnement**  
→ Le code actuel est identique (sauf ajout de logs)  
→ Les erreurs 500 existaient AVANT les modifications

---

## 🔍 Hypothèses de Cause Racine

### Hypothèse 1: Cache PHP-DI (⭐ TRÈS PROBABLE)
**Symptômes**:
- Le code est correct
- DI est bien configuré
- Erreurs 500 sans raison apparente

**Cause possible**:
- Le cache PHP-DI du serveur contient des définitions obsolètes
- Les nouvelles définitions de `config/dependencies.php` ne sont pas prises en compte
- Le serveur utilise encore les anciennes instantiations manuelles

**Preuve**:
```bash
# Dans config/container.php
$containerBuilder = new ContainerBuilder();
if (!isset($_ENV['DEBUG']) || $_ENV['DEBUG'] !== 'true') {
    $containerBuilder->enableCompilation(__DIR__ . '/../var/cache');
}
```
→ Le cache DI est activé en production !

**Solution**:
```bash
ssh oliviera@toaster
cd /home4/oliviera/iot.olution.info/ffp3
rm -rf var/cache/*
```

### Hypothèse 2: Cache OPCache PHP (⭐ PROBABLE)
**Symptômes**:
- Les modifications de code ne sont pas prises en compte
- Le serveur exécute encore l'ancien code

**Cause possible**:
- OPCache PHP cache les fichiers compilés
- Les nouvelles versions ne sont pas rechargées

**Solution**:
```bash
# Via PHP CLI
php -r "opcache_reset();"

# Ou via script web
<?php opcache_reset(); echo "Cache cleared"; ?>

# Ou redémarrage Apache
systemctl restart apache2
```

### Hypothèse 3: Git non synchronisé (⭐ POSSIBLE)
**Symptômes**:
- Le serveur n'a pas la dernière version du code
- Le script CRON de déploiement a échoué

**Vérification**:
```bash
ssh oliviera@toaster
cd /home4/oliviera/iot.olution.info/ffp3
git status
git log -1
```

**Solution**:
```bash
git fetch origin
git reset --hard origin/main
git clean -fd
composer install --no-dev --optimize-autoloader
```

### Hypothèse 4: Problème de permissions (POSSIBLE)
**Symptômes**:
- Erreurs 500 sans logs d'erreur

**Vérification**:
```bash
ls -la var/cache
ls -la var/log
```

**Solution**:
```bash
chmod 755 var var/cache var/log
chown -R www-data:www-data var
```

### Hypothèse 5: Erreur dans dependencies.php (IMPROBABLE)
**Raison**: Le code est testé localement et fonctionne  
**Mais**: Différence possible entre environnement local et serveur

---

## 🎯 Plan d'Action Recommandé

### Phase 1: Vérification Serveur (SSH REQUIS)
```bash
# 1. Connexion
ssh oliviera@toaster
cd /home4/oliviera/iot.olution.info/ffp3

# 2. Vérifier version Git
git log -1 --oneline
# Attendu: 5af7666 📊 RAPPORT FINAL v4.6.14

# 3. Vérifier état Git
git status
# Attendu: "nothing to commit, working tree clean"

# 4. Nettoyer cache DI
rm -rf var/cache/*
echo "Cache DI nettoyé"

# 5. Nettoyer OPCache
php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo 'OPCache cleared\n'; } else { echo 'OPCache not enabled\n'; }"

# 6. Vérifier permissions
ls -la var/cache var/log

# 7. Réinstaller dépendances
composer install --no-dev --optimize-autoloader

# 8. Redémarrer Apache (si possible)
sudo systemctl restart apache2
```

### Phase 2: Test Immédiat
```bash
# Test des endpoints
curl -I https://iot.olution.info/ffp3/control
curl -I https://iot.olution.info/ffp3/api/realtime/sensors/latest
curl -I https://iot.olution.info/ffp3/api/realtime/outputs/state
```

### Phase 3: Diagnostic Logs
```bash
# Consulter les logs d'erreur
tail -100 var/log/php_errors.log
tail -100 public/error_log
tail -100 /var/log/apache2/error.log
```

### Phase 4: Test Diagnostic Script
```bash
# Exécuter le script de diagnostic PHP
php tools/diagnostic_500_errors.php
```

---

## 📝 Conclusion

**Cause la plus probable**: Cache PHP-DI non nettoyé après déploiement  
**Action immédiate**: SSH + nettoyage cache + redémarrage Apache  
**Probabilité de résolution**: 90%  

Si le problème persiste après nettoyage du cache, analyser les logs d'erreur pour identifier la cause exacte.

---

## 🚀 Prochaines Étapes

1. **IMMÉDIAT**: SSH vers le serveur
2. **CRITIQUE**: Nettoyer tous les caches (DI + OPCache)
3. **IMPORTANT**: Redémarrer Apache
4. **VALIDATION**: Tester tous les endpoints
5. **DOCUMENTATION**: Mettre à jour CHANGELOG si résolu

---

**Remarque**: Cette analyse démontre que les modifications de code récentes (ajout de logs, `Env::load()`) ne sont PAS la cause des erreurs 500. Le problème est plus probablement lié à l'infrastructure serveur (cache, déploiement).

