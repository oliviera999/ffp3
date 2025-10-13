# Gestion des caches de production

## 📋 Vue d'ensemble

Le projet FFP3 utilise deux types de cache en production pour améliorer les performances :

1. **Cache Twig** : Templates compilés (`var/cache/twig/`)
2. **Cache DI Container** : Injection de dépendances compilée (`var/cache/di/`)

Ces caches améliorent considérablement les performances, mais ils doivent être vidés après chaque déploiement pour que les modifications du code soient prises en compte.

## 🔍 Problème résolu

### Symptôme
- Les modifications de code déployées via `git pull` ne sont pas visibles en production
- La version affichée en bas de page est à jour, mais les templates/contrôleurs utilisent l'ancienne version
- Les pages TEST fonctionnent correctement (car elles utilisent `ENV=test` sans cache)

### Cause
Les caches compilés en production ne sont pas automatiquement invalidés après un `git pull`. Le serveur web continue d'utiliser les versions en cache même si le code source a changé.

## ✅ Solution implémentée

### 1. Script de vidage automatique

**Fichier** : `bin/clear-cache.php`

Supprime et recrée proprement les répertoires de cache.

```bash
# Exécution manuelle
php bin/clear-cache.php
```

**Sortie attendue** :
```
🧹 Vidage des caches en cours...

🗑️  Vidage de twig/...
   ✅ 42 fichier(s) supprimé(s)
   📁 Dossier recréé
🗑️  Vidage de di/...
   ✅ 8 fichier(s) supprimé(s)
   📁 Dossier recréé

✅ Cache vidé avec succès ! (50 fichier(s) au total)
ℹ️  Les caches seront régénérés automatiquement à la prochaine requête.
```

### 2. Hook Git automatique

**Fichier** : `.git/hooks/post-merge`

Hook Git exécuté automatiquement après chaque `git pull` ou `git merge`.

**Comment ça marche** :
1. Vous faites `git pull` sur le serveur
2. Git fusionne les modifications
3. Le hook `post-merge` s'exécute automatiquement
4. Les caches sont vidés via `bin/clear-cache.php`
5. Les modifications sont immédiatement visibles

**Installation** :
```bash
# Le hook est déjà dans le dépôt Git
# Sur le serveur, le rendre exécutable :
chmod +x .git/hooks/post-merge
```

### 3. Script de déploiement intégré

**Fichier** : `bin/deploy.sh`

Script de déploiement complet qui :
- Fait le `git pull`
- Vide automatiquement les caches
- Installe/met à jour les dépendances Composer
- Vérifie l'intégrité de l'installation
- Affiche les URLs de test

**Usage** :
```bash
# Sur le serveur de production
ssh oliviera@toaster
cd /home4/oliviera/iot.olution.info/ffp3
bash bin/deploy.sh
```

## 📚 Procédures

### Déploiement complet (recommandé)

```bash
# Méthode 1 : Avec le script de déploiement
bash bin/deploy.sh

# Méthode 2 : Manuel avec vidage automatique des caches
git pull origin main
# Les caches sont vidés automatiquement par le hook post-merge
```

### Vidage manuel des caches

Si vous avez besoin de vider les caches sans déployer :

```bash
# Méthode recommandée
php bin/clear-cache.php

# Méthode manuelle (si le script ne fonctionne pas)
rm -rf var/cache/twig/*
rm -rf var/cache/di/*
```

### Vérification après déploiement

1. **Vérifier la version** :
   ```bash
   cat VERSION
   ```

2. **Tester les pages PROD** :
   - https://iot.olution.info/ffp3/aquaponie
   - https://iot.olution.info/ffp3/dashboard
   - https://iot.olution.info/ffp3/control

3. **Vérifier dans le navigateur** :
   - Ouvrir F12 (Console)
   - Vérifier qu'il n'y a pas d'erreurs 404
   - Vérifier que la version en bas de page correspond au fichier `VERSION`
   - Vérifier que les modifications récentes sont bien visibles

4. **Tester les pages TEST** (toujours sans cache) :
   - https://iot.olution.info/ffp3/aquaponie-test
   - https://iot.olution.info/ffp3/dashboard-test
   - https://iot.olution.info/ffp3/control-test

### Désactiver temporairement le cache

Pour désactiver le cache en production (débogage uniquement) :

1. Éditer `.env` :
   ```env
   ENV=test
   ```

2. ⚠️ **IMPORTANT** : Remettre `ENV=prod` après le débogage !

## 🔧 Troubleshooting

### Les modifications ne sont toujours pas visibles

1. **Vérifier que le hook est exécutable** :
   ```bash
   chmod +x .git/hooks/post-merge
   ```

2. **Vider les caches manuellement** :
   ```bash
   php bin/clear-cache.php
   ```

3. **Vérifier les permissions** :
   ```bash
   chmod -R 775 var/cache/
   ```

4. **Vider le cache du navigateur** :
   - Chrome/Firefox : Ctrl+Shift+R (rechargement forcé)
   - Ou ouvrir en navigation privée

5. **Vérifier la configuration Apache/Nginx** :
   - Le serveur web doit bien pointer vers `/public/index.php`
   - Vérifier les règles de réécriture (`.htaccess` ou config Nginx)

### Le hook post-merge ne s'exécute pas

1. **Vérifier que le hook existe** :
   ```bash
   ls -la .git/hooks/post-merge
   ```

2. **Vérifier qu'il est exécutable** :
   ```bash
   chmod +x .git/hooks/post-merge
   ```

3. **Tester manuellement** :
   ```bash
   .git/hooks/post-merge
   ```

### Erreurs de permissions

Si vous obtenez des erreurs de permissions lors du vidage des caches :

```bash
# Donner les bonnes permissions au dossier cache
sudo chown -R www-data:www-data var/cache/
chmod -R 775 var/cache/

# Ou si vous utilisez votre propre utilisateur
sudo chown -R $USER:www-data var/cache/
chmod -R 775 var/cache/
```

## 🎯 Bonnes pratiques

### Workflow de développement recommandé

1. **Développer sur les routes TEST** :
   ```
   /aquaponie-test
   /dashboard-test
   /control-test
   ```
   _(Ces routes n'utilisent jamais le cache, idéal pour le dev)_

2. **Tester en local** avant de déployer

3. **Incrémenter la VERSION** :
   ```bash
   # Fichier VERSION
   4.5.33  # PATCH : bug fixes
   4.6.0   # MINOR : nouvelles fonctionnalités
   5.0.0   # MAJOR : breaking changes
   ```

4. **Mettre à jour CHANGELOG.md**

5. **Commit et push** :
   ```bash
   git add .
   git commit -m "Fix: résolution problème X"
   git push origin main
   ```

6. **Déployer sur le serveur** :
   ```bash
   ssh oliviera@toaster
   cd /home4/oliviera/iot.olution.info/ffp3
   bash bin/deploy.sh
   ```

7. **Tester les pages PROD**

### Surveillance des caches

Les caches sont régénérés automatiquement à la première requête après le vidage. Pas d'intervention nécessaire.

**Taille normale des caches** :
- `var/cache/twig/` : ~100-500 Ko
- `var/cache/di/` : ~50-200 Ko

**Si les caches deviennent trop volumineux** (>10 Mo), c'est anormal. Vider manuellement.

## 📊 Architecture technique

### Configuration du cache Twig

```php
// src/Service/TemplateRenderer.php
$cacheConfig = false;
if (($_ENV['ENV'] ?? 'prod') === 'prod') {
    $cacheDir = dirname(__DIR__, 2) . '/var/cache/twig';
    $cacheConfig = $cacheDir;
}

self::$twig = new Environment($loader, [
    'cache' => $cacheConfig,  // false en test, chemin en prod
    'autoescape' => 'html',
]);
```

### Configuration du cache DI Container

```php
// config/container.php
$containerBuilder = new ContainerBuilder();

if (($_ENV['ENV'] ?? 'prod') === 'prod') {
    $containerBuilder->enableCompilation(__DIR__ . '/../var/cache/di');
    $containerBuilder->writeProxiesToFile(true, __DIR__ . '/../var/cache/di/proxies');
}
```

### Hook Git post-merge

```bash
#!/bin/sh
# .git/hooks/post-merge

PROJECT_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"

if [ -f "$PROJECT_ROOT/bin/clear-cache.php" ]; then
    php "$PROJECT_ROOT/bin/clear-cache.php"
fi
```

## 📖 Références

- **Documentation Twig** : https://twig.symfony.com/doc/3.x/api.html#environment-options
- **Documentation PHP-DI** : https://php-di.org/doc/performances.html
- **Documentation Git hooks** : https://git-scm.com/book/en/v2/Customizing-Git-Git-Hooks

## 🔗 Fichiers liés

- `bin/clear-cache.php` : Script de vidage des caches
- `bin/deploy.sh` : Script de déploiement avec vidage automatique
- `.git/hooks/post-merge` : Hook Git automatique
- `src/Service/TemplateRenderer.php` : Configuration du cache Twig
- `config/container.php` : Configuration du cache DI Container
- `src/Config/TableConfig.php` : Gestion des environnements PROD/TEST

---

**Document créé le** : 2025-10-13  
**Dernière mise à jour** : 2025-10-13  
**Version du projet** : 4.5.33

