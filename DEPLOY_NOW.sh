#!/bin/bash
#
# Script de déploiement FFP3 v4.0.0 sur serveur
# À exécuter sur le serveur via SSH
#
# Usage:
#   ssh oliviera@toaster
#   cd /home4/oliviera/iot.olution.info/ffp3
#   bash DEPLOY_NOW.sh
#

echo "=========================================="
echo "🚀 DÉPLOIEMENT FFP3 v4.0.0"
echo "=========================================="
echo ""

# Vérifier qu'on est dans le bon dossier
if [ ! -f "composer.json" ]; then
    echo "❌ ERREUR: composer.json non trouvé"
    echo "Êtes-vous dans /home4/oliviera/iot.olution.info/ffp3 ?"
    exit 1
fi

echo "📍 Dossier: $(pwd)"
echo ""

# Étape 1: Pull depuis GitHub
echo "📥 [1/5] Pull depuis GitHub..."
git fetch origin
git pull origin main

if [ $? -ne 0 ]; then
    echo "❌ Erreur lors du git pull"
    echo ""
    echo "Essayez de résoudre avec:"
    echo "  git reset --hard origin/main"
    exit 1
fi

echo "✅ Code mis à jour"
echo ""

# Étape 2: Supprimer vendor/ corrompu
echo "🗑️  [2/5] Suppression vendor/ existant..."
if [ -d "vendor" ]; then
    rm -rf vendor/
    echo "✅ vendor/ supprimé"
else
    echo "⚠️  vendor/ n'existe pas (OK)"
fi
echo ""

# Étape 3: Installer les dépendances
echo "📦 [3/5] Installation des dépendances Composer..."
echo "Cela peut prendre 1-2 minutes..."
composer update --no-dev --optimize-autoloader

if [ $? -ne 0 ]; then
    echo "❌ Erreur lors de composer update"
    echo ""
    echo "Essayez:"
    echo "  composer clear-cache"
    echo "  composer update --no-dev"
    exit 1
fi

echo "✅ Dépendances installées"
echo ""

# Étape 4: Vérifications
echo "🔍 [4/5] Vérifications..."

# PHP-DI
if [ -d "vendor/php-di" ]; then
    echo "  ✅ php-di installé"
else
    echo "  ❌ php-di MANQUANT !"
    exit 1
fi

# web-push
if [ -d "vendor/minishlink" ]; then
    echo "  ✅ web-push installé"
else
    echo "  ❌ web-push MANQUANT !"
fi

# bacon-qr-code
if [ -d "vendor/bacon" ]; then
    echo "  ✅ bacon-qr-code installé"
else
    echo "  ❌ bacon-qr-code MANQUANT !"
fi

# Test autoload
php -r "require 'vendor/autoload.php'; echo '';" 2>&1
if [ $? -eq 0 ]; then
    echo "  ✅ Autoload fonctionne"
else
    echo "  ❌ Autoload en ERREUR !"
    exit 1
fi

echo ""

# Étape 5: Vérifier version
echo "📌 [5/5] Version déployée:"
cat VERSION
echo ""

# Permissions
chmod -R 755 public/ 2>/dev/null || true
chmod -R 775 var/cache/ 2>/dev/null || true

echo ""
echo "=========================================="
echo "🎉 DÉPLOIEMENT v4.0.0 RÉUSSI !"
echo "=========================================="
echo ""
echo "🧪 TESTEZ MAINTENANT:"
echo ""
echo "  1. Ouvrir navigateur:"
echo "     https://iot.olution.info/ffp3/ffp3datas/"
echo ""
echo "  2. Vérifier:"
echo "     ✅ Pas d'erreur 500"
echo "     ✅ Badge LIVE visible en haut à droite"
echo "     ✅ Dashboard 'État du système' affiche métriques"
echo ""
echo "  3. Tester API:"
echo "     curl https://iot.olution.info/ffp3/ffp3datas/api/outputs/state"
echo "     (doit retourner JSON avec états GPIO)"
echo ""
echo "  4. Console navigateur (F12):"
echo "     Chercher logs [RealtimeUpdater]"
echo "     Badge devrait passer à 'LIVE' (vert) après 15s"
echo ""
echo "📚 Documentation:"
echo "   - ESP32_API_REFERENCE.md (endpoints ESP32)"
echo "   - QUICKSTART_V4.md (démarrage rapide)"
echo "   - IMPLEMENTATION_REALTIME_PWA.md (guide technique)"
echo ""
echo "✨ Prochaines étapes:"
echo "   - Générer icônes PWA (voir public/assets/icons/README.md)"
echo "   - Tester installation PWA sur mobile"
echo ""
echo "=========================================="

