# 🚀 Instructions de Déploiement Serveur - FFP3 v4.0.0

## ⚠️ Problème Actuel

```
Fatal error: Class "DI\ContainerBuilder" not found
```

**Cause** : Le `vendor/` sur le serveur est incomplet/corrompu.

---

## ✅ Solution (5 minutes)

### **Option 1 : Script Automatique** (RECOMMANDÉ)

```bash
# SSH vers le serveur
ssh oliviera@toaster

# Aller dans le dossier
cd /home4/oliviera/iot.olution.info/ffp3/ffp3datas

# Rendre le script exécutable
chmod +x deploy-v4.sh

# Exécuter le script de déploiement
bash deploy-v4.sh
```

Le script va automatiquement :
1. Pull depuis GitHub
2. Supprimer vendor/ corrompu
3. Installer toutes les dépendances
4. Vérifier que tout fonctionne
5. Afficher un rapport de succès

---

### **Option 2 : Manuel** (Étape par étape)

Si vous préférez exécuter manuellement :

```bash
# 1. SSH vers le serveur
ssh oliviera@toaster

# 2. Aller dans le dossier
cd /home4/oliviera/iot.olution.info/ffp3/ffp3datas

# 3. Pull dernières modifications (incluant fix .gitignore)
git pull origin main

# 4. Supprimer vendor/ actuel
rm -rf vendor/

# 5. Installer proprement les dépendances
composer update --no-dev --optimize-autoloader

# 6. Vérifier que PHP-DI est installé
ls -la vendor/php-di/

# 7. Tester l'autoload
php -r "require 'vendor/autoload.php'; echo 'Autoload OK\n';"

# 8. Vérifier les permissions
chmod -R 755 public/
chmod -R 775 var/cache/

# 9. Tester le site
curl -I https://iot.olution.info/ffp3/ffp3datas/
```

---

## 🔍 Vérifications Post-Déploiement

### 1. **Vérifier les dépendances installées**

```bash
ls vendor/ | grep -E "php-di|web-push|bacon"
```

**Résultat attendu** :
```
bacon/
minishlink/
php-di/
```

### 2. **Vérifier la version**

```bash
cat VERSION
```

**Résultat attendu** : `4.0.0`

### 3. **Tester l'API temps réel**

```bash
curl https://iot.olution.info/ffp3/ffp3datas/api/realtime/system/health
```

**Résultat attendu** : JSON avec `{"online":true,...}`

### 4. **Vérifier dans le navigateur**

Ouvrir : https://iot.olution.info/ffp3/ffp3datas/

**Vérifier** :
- ✅ Badge "LIVE" s'affiche en haut à droite
- ✅ Dashboard "État du système" visible
- ✅ Aucune erreur PHP 500
- ✅ Console : logs `[RealtimeUpdater]`

---

## 📊 Dépendances Qui Seront Installées

| Package | Version | Taille | Usage |
|---------|---------|--------|-------|
| php-di/php-di | ^7.0 | ~1 MB | Container DI (requis) |
| minishlink/web-push | ^8.0 | ~500 KB | Notifications push |
| bacon/bacon-qr-code | ^2.0 | ~300 KB | Génération QR codes |
| + dépendances transitives | - | ~2 MB | Support |

**Total** : ~3-5 MB

---

## 🐛 Résolution de Problèmes

### **Erreur : "composer: command not found"**

```bash
# Vérifier si composer existe
which composer

# Si absent, installer composer
curl -sS https://getcomposer.org/installer | php
php composer.phar update --no-dev --optimize-autoloader
```

### **Erreur : "Memory limit exceeded"**

```bash
# Augmenter la limite mémoire PHP
php -d memory_limit=512M /usr/local/bin/composer update --no-dev
```

### **Permissions denied**

```bash
# Vérifier les permissions du dossier
ls -la

# Si nécessaire, ajuster
chmod -R 755 .
```

### **Toujours l'erreur DI\ContainerBuilder**

```bash
# Vérifier que le container.php est correct
head -15 config/container.php

# Devrait contenir : use DI\ContainerBuilder;

# Forcer la régénération de l'autoload
composer dump-autoload --optimize
```

---

## 📋 Checklist de Déploiement

- [ ] SSH vers le serveur OK
- [ ] `git pull origin main` → "Déjà à jour"
- [ ] `rm -rf vendor/` → Supprimé
- [ ] `composer update` → Terminé sans erreur
- [ ] `ls vendor/php-di/` → Dossier existe
- [ ] `php -r "require 'vendor/autoload.php';"` → Pas d'erreur
- [ ] Ouvrir site dans navigateur → Fonctionne
- [ ] Badge LIVE visible → Oui
- [ ] Dashboard système → Affiche les métriques

---

## 🎯 Résumé

**Problème** : vendor/ commité dans Git cause conflit avec composer.lock
**Solution** : 
1. Pull le fix .gitignore
2. Supprimer vendor/
3. `composer update` pour recréer proprement
**Durée** : 1-2 minutes
**Impact** : v4.0.0 fonctionnera parfaitement

---

## ⚡ Commande Ultra-Rapide (Une Ligne)

Si vous voulez aller très vite :

```bash
cd /home4/oliviera/iot.olution.info/ffp3/ffp3datas && git pull && rm -rf vendor/ && composer update --no-dev --optimize-autoloader && echo "✅ DÉPLOIEMENT TERMINÉ"
```

---

**Prêt à exécuter sur le serveur ?** 🚀

