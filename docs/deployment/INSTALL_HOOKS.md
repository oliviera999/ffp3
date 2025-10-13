# Installation des hooks Git sur le serveur de production

## 📋 Contexte

Les hooks Git (fichiers dans `.git/hooks/`) ne sont **pas versionnés** dans le dépôt Git par défaut pour des raisons de sécurité. Ils doivent donc être installés manuellement sur chaque environnement (production, développement, etc.).

## 🎯 Hook à installer

### `post-merge`

Hook exécuté automatiquement après chaque `git pull` ou `git merge` pour vider les caches de production.

## 🚀 Installation sur le serveur de production

### Méthode 1 : Création manuelle (recommandé)

Connectez-vous au serveur et créez le hook :

```bash
# Connexion SSH au serveur
ssh oliviera@toaster

# Navigation vers le projet
cd /home4/oliviera/iot.olution.info/ffp3

# Création du fichier hook
cat > .git/hooks/post-merge << 'EOF'
#!/bin/sh
#
# Hook Git post-merge
# 
# Exécuté automatiquement après chaque 'git pull' réussi.
# Vide les caches de production pour que les modifications soient visibles.
#

echo ""
echo "🔄 Post-merge hook : Vidage automatique des caches..."

# Détecter le répertoire racine du projet
PROJECT_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"

# Exécuter le script de vidage de cache
if [ -f "$PROJECT_ROOT/bin/clear-cache.php" ]; then
    php "$PROJECT_ROOT/bin/clear-cache.php"
else
    echo "⚠️  ATTENTION : Script bin/clear-cache.php introuvable !"
    echo "   Les caches n'ont pas été vidés automatiquement."
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

### Méthode 2 : Copie depuis le dossier local

Si vous avez déjà le hook dans votre dépôt local :

```bash
# Sur votre machine locale
scp .git/hooks/post-merge oliviera@toaster:/home4/oliviera/iot.olution.info/ffp3/.git/hooks/

# Puis sur le serveur
ssh oliviera@toaster
cd /home4/oliviera/iot.olution.info/ffp3
chmod +x .git/hooks/post-merge
```

## ✅ Vérification de l'installation

### Test du hook

```bash
# Tester manuellement le hook
.git/hooks/post-merge

# Sortie attendue :
# 🔄 Post-merge hook : Vidage automatique des caches...
# 🧹 Vidage des caches en cours...
# [...]
# ✅ Cache vidé avec succès !
```

### Test avec git pull

```bash
# Faire un git pull pour tester
git pull origin main

# Après le pull, vous devriez voir :
# [messages de git pull]
# 
# 🔄 Post-merge hook : Vidage automatique des caches...
# [messages du vidage de cache]
```

## 🔧 Résolution de problèmes

### Le hook ne s'exécute pas

**Vérifier que le hook existe** :
```bash
ls -la .git/hooks/post-merge
```

**Vérifier qu'il est exécutable** :
```bash
chmod +x .git/hooks/post-merge
```

**Vérifier les permissions** :
```bash
# Le hook doit appartenir à votre utilisateur
ls -la .git/hooks/post-merge
# Exemple de sortie : -rwxr-xr-x 1 oliviera oliviera 623 Oct 13 14:30 post-merge
```

### Le hook s'exécute mais échoue

**Vérifier que le script de vidage existe** :
```bash
ls -la bin/clear-cache.php
```

**Tester le script manuellement** :
```bash
php bin/clear-cache.php
```

**Vérifier les chemins** :
```bash
# Le hook doit pouvoir trouver le script
# Afficher le chemin absolu du projet
pwd
```

### Erreurs de permissions

Si vous obtenez des erreurs de permissions lors du vidage des caches :

```bash
# Donner les bonnes permissions au dossier cache
chmod -R 775 var/cache/
chown -R oliviera:oliviera var/cache/

# Ou si le serveur web doit écrire dans le cache
chown -R oliviera:www-data var/cache/
chmod -R 775 var/cache/
```

## 📝 Notes importantes

### Hooks non versionnés

Les hooks Git ne sont **jamais** versionnés dans le dépôt pour des raisons de sécurité (ils peuvent exécuter du code arbitraire). Chaque développeur/serveur doit les installer manuellement.

### Mise à jour du hook

Si le contenu du hook change, vous devez :

1. Mettre à jour le fichier manuellement sur le serveur
2. Ou recréer le hook avec la méthode 1 ci-dessus

### Alternative sans hook

Si vous ne pouvez pas installer le hook, utilisez le script de déploiement complet :

```bash
# Au lieu de git pull, utilisez
bash bin/deploy.sh
```

Ce script intègre le vidage de cache.

## 🔗 Fichiers liés

- `bin/clear-cache.php` : Script de vidage des caches (versionné)
- `bin/deploy.sh` : Script de déploiement complet (versionné)
- `docs/deployment/CACHE_MANAGEMENT.md` : Documentation de la gestion des caches
- `.git/hooks/post-merge` : Hook Git (non versionné, à installer manuellement)

## 📚 Références

- [Git Hooks Documentation](https://git-scm.com/book/en/v2/Customizing-Git-Git-Hooks)
- [post-merge hook](https://git-scm.com/docs/githooks#_post_merge)

---

**Document créé le** : 2025-10-13  
**Dernière mise à jour** : 2025-10-13  
**Version du projet** : 4.5.33

