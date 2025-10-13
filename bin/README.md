# Scripts utilitaires FFP3

Ce dossier contient les scripts utilitaires pour le projet FFP3.

## 📜 Scripts disponibles

### `clear-cache.php`
Script de vidage des caches de production (Twig et DI Container).

**Usage** :
```bash
php bin/clear-cache.php
```

**Fonction** :
- Vide `var/cache/twig/` (templates Twig compilés)
- Vide `var/cache/di/` (container DI compilé)
- Recrée les dossiers avec les bonnes permissions
- Affiche un rapport détaillé du vidage

**Quand l'utiliser** :
- Après avoir modifié des templates Twig
- Après avoir modifié des contrôleurs ou services
- Lorsque les modifications ne sont pas visibles en production
- Pour forcer la recompilation des caches

**Note** : Ce script est appelé automatiquement par le hook Git `post-merge` après chaque `git pull`.

---

### `deploy.sh`
Script complet de déploiement en production avec vidage de cache intégré.

**Usage** :
```bash
# Sur le serveur de production
ssh oliviera@toaster
cd /home4/oliviera/iot.olution.info/ffp3
bash bin/deploy.sh
```

**Fonction** :
1. Fait un `git pull` depuis GitHub
2. Vide automatiquement les caches
3. Installe/met à jour les dépendances Composer
4. Crée les liens symboliques pour les assets
5. Vérifie l'intégrité de l'installation
6. Ajuste les permissions
7. Affiche les URLs de test

**Avantages** :
- Processus de déploiement complet et automatisé
- Garantit que les caches sont vidés
- Vérifie que tout fonctionne correctement
- Fournit un rapport détaillé

---

## 🔄 Workflow de déploiement

### Méthode 1 : Script complet (recommandé)
```bash
bash bin/deploy.sh
```

### Méthode 2 : Git pull simple
```bash
git pull origin main
# Le hook post-merge videra automatiquement les caches
```

### Méthode 3 : Vidage manuel
```bash
php bin/clear-cache.php
```

## 📚 Documentation

Pour plus d'informations sur la gestion des caches, consultez :
- [`docs/deployment/CACHE_MANAGEMENT.md`](../docs/deployment/CACHE_MANAGEMENT.md)

## 🔗 Fichiers liés

- `.git/hooks/post-merge` : Hook Git qui appelle automatiquement `clear-cache.php`
- `src/Service/TemplateRenderer.php` : Configuration du cache Twig
- `config/container.php` : Configuration du cache DI Container

---

**Dernière mise à jour** : 2025-10-13 (v4.5.33)

