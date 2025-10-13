#!/bin/bash
#
# Script de déploiement FFP3 avec vidage automatique des caches
# À exécuter sur le serveur de production via SSH
#
# Usage:
#   ssh oliviera@toaster
#   cd /home4/oliviera/iot.olution.info/ffp3
#   bash bin/deploy.sh
#

set -e  # Arrêter en cas d'erreur

echo "=========================================="
echo "🚀 DÉPLOIEMENT FFP3 avec vidage de cache"
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
echo "📥 [1/7] Pull depuis GitHub..."
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

# Étape 2: Vider les caches (NOUVEAU)
echo "🧹 [2/7] Vidage des caches Twig et DI..."
if [ -f "bin/clear-cache.php" ]; then
    php bin/clear-cache.php
    if [ $? -eq 0 ]; then
        echo "✅ Caches vidés"
    else
        echo "⚠️  Erreur lors du vidage des caches (non bloquant)"
    fi
else
    echo "⚠️  Script bin/clear-cache.php introuvable"
    echo "   Vidage manuel des caches..."
    rm -rf var/cache/twig/* var/cache/di/* 2>/dev/null || true
    echo "✅ Caches vidés manuellement"
fi
echo ""

# Étape 3: Supprimer vendor/ si nécessaire
echo "🗑️  [3/7] Vérification vendor/..."
if [ -d "vendor" ]; then
    echo "ℹ️  vendor/ existe déjà"
else
    echo "⚠️  vendor/ n'existe pas"
fi
echo ""

# Étape 4: Installer/mettre à jour les dépendances
echo "📦 [4/7] Installation des dépendances Composer..."
echo "Cela peut prendre 1-2 minutes..."
composer install --no-dev --optimize-autoloader

if [ $? -ne 0 ]; then
    echo "❌ Erreur lors de composer install"
    echo ""
    echo "Essayez:"
    echo "  composer clear-cache"
    echo "  composer install --no-dev"
    exit 1
fi

echo "✅ Dépendances installées"
echo ""

# Étape 5: Créer les liens symboliques pour les assets
echo "🔗 [5/7] Création des liens symboliques..."

# Supprimer d'abord s'ils existent déjà
rm -f assets manifest.json service-worker.js 2>/dev/null

# Créer les liens symboliques
ln -s public/assets assets 2>/dev/null || true
ln -s public/manifest.json manifest.json 2>/dev/null || true
ln -s public/service-worker.js service-worker.js 2>/dev/null || true

if [ -L "assets" ] && [ -L "manifest.json" ]; then
    echo "✅ Liens symboliques créés:"
    echo "   assets -> public/assets"
    echo "   manifest.json -> public/manifest.json"
    echo "   service-worker.js -> public/service-worker.js"
else
    echo "⚠️  Erreur lors de la création des liens symboliques"
    echo "   (peut nécessiter des permissions spéciales, non bloquant)"
fi
echo ""

# Étape 6: Vérifications
echo "🔍 [6/7] Vérifications..."

# PHP-DI
if [ -d "vendor/php-di" ]; then
    echo "  ✅ php-di installé"
else
    echo "  ❌ php-di MANQUANT !"
    exit 1
fi

# Twig
if [ -d "vendor/twig" ]; then
    echo "  ✅ twig installé"
else
    echo "  ❌ twig MANQUANT !"
    exit 1
fi

# Slim
if [ -d "vendor/slim" ]; then
    echo "  ✅ slim installé"
else
    echo "  ❌ slim MANQUANT !"
    exit 1
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

# Étape 7: Ajustement des permissions
echo "🔧 [7/7] Ajustement des permissions..."
chmod -R 755 public/ 2>/dev/null || true
chmod -R 775 var/cache/ 2>/dev/null || true
chmod +x bin/clear-cache.php 2>/dev/null || true
chmod +x bin/deploy.sh 2>/dev/null || true
echo "✅ Permissions ajustées"
echo ""

# Version déployée
echo "📌 Version déployée:"
if [ -f "VERSION" ]; then
    cat VERSION
else
    echo "  ⚠️  Fichier VERSION introuvable"
fi
echo ""

echo "=========================================="
echo "🎉 DÉPLOIEMENT RÉUSSI !"
echo "=========================================="
echo ""
echo "🧪 TESTEZ MAINTENANT:"
echo ""
echo "  1. Pages PRODUCTION:"
echo "     https://iot.olution.info/ffp3/aquaponie"
echo "     https://iot.olution.info/ffp3/dashboard"
echo "     https://iot.olution.info/ffp3/control"
echo ""
echo "  2. Pages TEST:"
echo "     https://iot.olution.info/ffp3/aquaponie-test"
echo "     https://iot.olution.info/ffp3/dashboard-test"
echo "     https://iot.olution.info/ffp3/control-test"
echo ""
echo "  3. Vérifications (F12 Console):"
echo "     ✅ Pas d'erreur 404 pour CSS/JS"
echo "     ✅ Badge LIVE devient vert après 15s"
echo "     ✅ Version en bas de page correspond à VERSION"
echo "     ✅ Modifications récentes sont visibles"
echo ""
echo "💡 Pour vider manuellement les caches:"
echo "   php bin/clear-cache.php"
echo ""
echo "=========================================="

