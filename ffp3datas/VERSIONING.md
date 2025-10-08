# Système de Versioning FFP3

## 📋 Vue d'ensemble

Le projet FFP3 utilise un système de versioning centralisé basé sur [Semantic Versioning](https://semver.org/).

## 🔢 Format de Version

Le format de version suit le pattern **MAJOR.MINOR.PATCH** :

- **MAJOR** : Changements incompatibles avec les versions précédentes
- **MINOR** : Ajout de fonctionnalités rétro-compatibles
- **PATCH** : Corrections de bugs rétro-compatibles

### Version actuelle

La version est stockée dans le fichier `VERSION` à la racine de `ffp3datas/`.

## 🏗️ Architecture

### Fichiers

```
ffp3datas/
├── VERSION                           # Fichier de version (ex: 1.0.0)
└── src/
    └── Config/
        └── Version.php               # Classe de gestion de version
```

### Classe Version

La classe `App\Config\Version` fournit plusieurs méthodes statiques :

```php
use App\Config\Version;

// Récupérer la version (ex: "1.0.0")
$version = Version::get();

// Récupérer la version formatée (ex: "v1.0.0")
$version = Version::getFormatted();

// Récupérer la date de build
$buildDate = Version::getBuildDate();

// Récupérer la version complète
$fullVersion = Version::getFull(); // "v1.0.0 (2025-10-08 14:30)"

// Récupérer toutes les infos (pour API)
$info = Version::getInfo();
// ['version' => '1.0.0', 'build_date' => '2025-10-08 14:30', 'environment' => 'prod']
```

## 🎨 Affichage dans l'interface

La version s'affiche automatiquement dans le footer de toutes les pages :

### Templates concernés

- `dashboard.twig` - Dashboard des capteurs
- `aquaponie.twig` - Interface aquaponie
- `control.twig` - Interface de contrôle
- `tide_stats.twig` - Statistiques marées

### Format d'affichage

```
FFP3 [Page] v1.0.0 | Environnement: PROD | Build: 2025-10-08 14:30
```

## 🔌 API Version

### Endpoint

```
GET /ffp3/ffp3datas/public/api/version
```

### Réponse

```json
{
  "success": true,
  "version": "1.0.0",
  "version_formatted": "v1.0.0",
  "build_date": "2025-10-08 14:30",
  "environment": "prod"
}
```

## 📝 Comment mettre à jour la version

### Méthode 1 : Manuel

1. Éditer le fichier `ffp3datas/VERSION`
2. Modifier le numéro de version selon Semantic Versioning
3. Committer et pousser les changements

```bash
echo "1.1.0" > ffp3datas/VERSION
git add ffp3datas/VERSION
git commit -m "Bump version to 1.1.0"
git push
```

### Méthode 2 : Via script (futur)

Un script de bump automatique pourra être créé :

```bash
# Bump PATCH (1.0.0 -> 1.0.1)
./scripts/bump-version.sh patch

# Bump MINOR (1.0.0 -> 1.1.0)
./scripts/bump-version.sh minor

# Bump MAJOR (1.0.0 -> 2.0.0)
./scripts/bump-version.sh major
```

## 🌍 Environnements

La version affiche également l'environnement actuel :

- **PROD** : Base de données `ffp3Data`, `ffp3Outputs`
- **TEST** : Base de données `ffp3Data2`, `ffp3Outputs2`

L'environnement est automatiquement détecté via `TableConfig::getEnvironment()`.

## 🔍 Date de Build

La date de build correspond à la date de dernière modification du fichier `VERSION`.

Lors d'un commit, la date est automatiquement mise à jour par Git.

## ✅ Bonnes pratiques

1. **Toujours** mettre à jour VERSION lors d'un déploiement
2. **Respecter** le Semantic Versioning
3. **Documenter** les changements dans les messages de commit
4. **Tagger** les releases dans Git :

```bash
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0
```

## 📚 Exemples de Versioning

| Changement | Exemple | Version |
|------------|---------|---------|
| Correction bug affichage | Fix: Correction jauge eau | 1.0.0 → 1.0.1 |
| Nouveau graphique | Add: Graphique statistiques hebdo | 1.0.0 → 1.1.0 |
| Migration architecture | Breaking: Migration vers Slim 4 | 1.0.0 → 2.0.0 |

## 🎯 Historique des versions

### v1.0.0 (2025-10-08)
- ✅ Migration complète vers architecture moderne
- ✅ Interface de contrôle Twig
- ✅ API REST complète
- ✅ Système de versioning intégré
