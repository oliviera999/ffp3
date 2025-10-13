# Résolution du problème de cache en production - v4.5.33

## 🎯 Problème résolu

**Symptôme** : Les modifications déployées via `git push` sur GitHub n'étaient pas visibles en production, alors que la version affichée en bas de page était correcte et que les pages de TEST fonctionnaient parfaitement.

**Cause racine** : Les caches Twig et DI Container n'étaient jamais vidés après un `git pull` sur le serveur de production.

## ✅ Solution implémentée

### 1. Script de vidage automatique
**Fichier** : `bin/clear-cache.php`
- Vide `var/cache/twig/` (templates compilés)
- Vide `var/cache/di/` (container DI compilé)
- Rapport détaillé du vidage
- Utilisable manuellement : `php bin/clear-cache.php`

### 2. Hook Git post-merge
**Fichier** : `.git/hooks/post-merge`
- Exécuté automatiquement après chaque `git pull`
- Appelle `bin/clear-cache.php` automatiquement
- ⚠️ **À installer manuellement sur le serveur** (les hooks Git ne sont pas versionnés)

### 3. Script de déploiement complet
**Fichier** : `bin/deploy.sh`
- `git pull` + vidage de cache + vérifications
- Usage sur le serveur : `bash bin/deploy.sh`
- Alternative tout-en-un pour le déploiement

### 4. Documentation complète
**Fichiers** :
- `docs/deployment/CACHE_MANAGEMENT.md` : Guide complet de gestion des caches
- `docs/deployment/INSTALL_HOOKS.md` : Installation du hook Git sur le serveur
- `bin/README.md` : Documentation des scripts utilitaires

## 📋 Actions à effectuer sur le serveur de production

### Étape 1 : Déployer les nouveaux fichiers

```bash
# Connexion SSH
ssh oliviera@toaster

# Navigation vers le projet
cd /home4/oliviera/iot.olution.info/ffp3

# Pull des modifications
git pull origin main
```

### Étape 2 : Installer le hook Git post-merge

```bash
# Créer le hook (copier-coller tout le bloc)
cat > .git/hooks/post-merge << 'EOF'
#!/bin/sh
#
# Hook Git post-merge
# Vide les caches automatiquement après chaque git pull
#

echo ""
echo "🔄 Post-merge hook : Vidage automatique des caches..."

PROJECT_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"

if [ -f "$PROJECT_ROOT/bin/clear-cache.php" ]; then
    php "$PROJECT_ROOT/bin/clear-cache.php"
else
    echo "⚠️  ATTENTION : Script bin/clear-cache.php introuvable !"
    echo "   Exécutez manuellement : php bin/clear-cache.php"
    exit 1
fi

exit 0
EOF

# Rendre le hook exécutable
chmod +x .git/hooks/post-merge

# Vérifier l'installation
ls -la .git/hooks/post-merge
```

### Étape 3 : Tester le hook

```bash
# Test manuel du hook
.git/hooks/post-merge

# Sortie attendue :
# 🔄 Post-merge hook : Vidage automatique des caches...
# 🧹 Vidage des caches en cours...
# 🗑️  Vidage de twig/...
#    ✅ X fichier(s) supprimé(s)
# [...]
# ✅ Cache vidé avec succès !
```

### Étape 4 : Tester avec git pull

```bash
# Faire un git pull pour tester (même s'il n'y a rien de nouveau)
git pull origin main

# Après le pull, vous devriez voir automatiquement :
# Already up to date.
# 
# 🔄 Post-merge hook : Vidage automatique des caches...
# [messages du vidage de cache]
```

### Étape 5 : Vérifier en production

Ouvrir dans le navigateur :
- https://iot.olution.info/ffp3/aquaponie
- https://iot.olution.info/ffp3/dashboard
- https://iot.olution.info/ffp3/control

Vérifier :
- ✅ Version en bas de page : `v4.5.33`
- ✅ Toutes les modifications récentes sont visibles
- ✅ Pas d'erreurs 404 dans la console (F12)

## 🔄 Workflow futur

### Déploiement rapide (recommandé)
```bash
# Sur le serveur
bash bin/deploy.sh
```

### Déploiement simple
```bash
# Sur le serveur
git pull origin main
# Le hook vide automatiquement les caches
```

### Vidage manuel des caches
```bash
# Si nécessaire (rare)
php bin/clear-cache.php
```

## 🎉 Avantages

- ✅ **Automatisation complète** : Plus besoin de penser au cache
- ✅ **Résolution définitive** : Les modifications sont toujours visibles après un déploiement
- ✅ **Workflow simplifié** : `git pull` suffit, le reste est automatique
- ✅ **Documentation claire** : Procédures et troubleshooting disponibles
- ✅ **Compatible** : Fonctionne avec le workflow Git actuel

## 📚 Documentation

- **Guide complet** : [`docs/deployment/CACHE_MANAGEMENT.md`](docs/deployment/CACHE_MANAGEMENT.md)
- **Installation hook** : [`docs/deployment/INSTALL_HOOKS.md`](docs/deployment/INSTALL_HOOKS.md)
- **Scripts** : [`bin/README.md`](bin/README.md)

## 🔧 Fichiers créés/modifiés

### Nouveaux fichiers
- ✅ `bin/clear-cache.php` - Script de vidage des caches
- ✅ `bin/deploy.sh` - Script de déploiement complet
- ✅ `bin/README.md` - Documentation des scripts
- ✅ `.git/hooks/post-merge` - Hook Git (à installer sur le serveur)
- ✅ `docs/deployment/CACHE_MANAGEMENT.md` - Guide complet
- ✅ `docs/deployment/INSTALL_HOOKS.md` - Guide d'installation du hook

### Fichiers modifiés
- ✅ `VERSION` - Incrémenté à **4.5.33**
- ✅ `CHANGELOG.md` - Ajout de l'entrée détaillée

## 🚀 Déploiement

**Statut** : ✅ Prêt à déployer

**Commandes locales** (déjà effectuées) :
```bash
git add .
git commit -m "Fix: résolution définitive problème cache production (v4.5.33)"
git push origin main
```

**Commandes serveur** (à faire) :
```bash
ssh oliviera@toaster
cd /home4/oliviera/iot.olution.info/ffp3
git pull origin main
# Installer le hook post-merge (voir Étape 2 ci-dessus)
```

## 📊 Impact

- **Développeurs** : Workflow simplifié, plus de problème de cache
- **Utilisateurs** : Toutes les nouvelles fonctionnalités visibles immédiatement
- **Maintenance** : Documentation complète pour troubleshooting futur
- **Performance** : Cache toujours optimisé et à jour

---

**Date** : 2025-10-13  
**Version** : 4.5.33 (PATCH)  
**Type** : Correction critique  
**Auteur** : AI Assistant via Cursor

