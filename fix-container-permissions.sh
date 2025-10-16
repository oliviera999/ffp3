#!/bin/bash

# Script de correction spécifique des permissions du container
# À exécuter sur le serveur iot.olution.info

echo "🔧 CORRECTION DES PERMISSIONS DU CONTAINER"
echo "=========================================="
echo "Date: $(date)"
echo ""

# Aller dans le répertoire du projet
cd /home4/oliviera/iot.olution.info/ffp3/

echo "📋 1. Vérification de l'état actuel..."
if [ -f "config/container.php" ]; then
    echo "✅ config/container.php existe"
    ls -la config/container.php
else
    echo "❌ config/container.php manquant - restauration nécessaire"
fi

echo ""
echo "🔧 2. Correction des permissions spécifiques..."

# Corriger les permissions des fichiers critiques
echo "   Correction des permissions des fichiers config..."
chmod 644 config/container.php 2>/dev/null || echo "   ⚠️ Impossible de modifier config/container.php"
chmod 644 config/dependencies.php 2>/dev/null || echo "   ⚠️ Impossible de modifier config/dependencies.php"

echo "   Correction des permissions des dossiers src..."
chmod 755 src/ 2>/dev/null || echo "   ⚠️ Impossible de modifier src/"
chmod 755 src/Config/ 2>/dev/null || echo "   ⚠️ Impossible de modifier src/Config/"
chmod 755 src/Controller/ 2>/dev/null || echo "   ⚠️ Impossible de modifier src/Controller/"

echo "   Correction des permissions des fichiers src..."
find src/ -type f -exec chmod 644 {} \; 2>/dev/null || echo "   ⚠️ Impossible de modifier certains fichiers src/"

echo "   Correction des permissions des templates..."
chmod 755 templates/ 2>/dev/null || echo "   ⚠️ Impossible de modifier templates/"
find templates/ -type f -exec chmod 644 {} \; 2>/dev/null || echo "   ⚠️ Impossible de modifier certains templates/"

echo ""
echo "🔍 3. Vérification des permissions corrigées..."
echo "   config/container.php:"
ls -la config/container.php 2>/dev/null || echo "   ❌ Fichier manquant"

echo "   src/Config/Env.php:"
ls -la src/Config/Env.php 2>/dev/null || echo "   ❌ Fichier manquant"

echo "   templates/control.twig:"
ls -la templates/control.twig 2>/dev/null || echo "   ❌ Fichier manquant"

echo ""
echo "🧪 4. Test rapide de l'application..."
php -r "
try {
    require_once 'vendor/autoload.php';
    \$container = require 'config/container.php';
    echo '✅ Container chargé avec succès\n';
    
    // Test des services critiques
    \$services = ['pdo', 'logger', 'twig'];
    foreach (\$services as \$service) {
        try {
            \$instance = \$container->get(\$service);
            echo '  ✅ Service ' . \$service . ': ' . get_class(\$instance) . '\n';
        } catch (Exception \$e) {
            echo '  ❌ Service ' . \$service . ': ' . \$e->getMessage() . '\n';
        }
    }
} catch (Exception \$e) {
    echo '❌ Erreur lors du chargement: ' . \$e->getMessage() . '\n';
}
"

echo ""
echo "🧪 5. Test des endpoints..."
endpoints=(
    "https://iot.olution.info/ffp3/"
    "https://iot.olution.info/ffp3/control"
)

for endpoint in "${endpoints[@]}"; do
    echo -n "   Test $endpoint: "
    response=$(curl -s -o /dev/null -w "%{http_code}" "$endpoint" 2>/dev/null)
    if [ "$response" = "200" ]; then
        echo "✅ OK ($response)"
    else
        echo "❌ ERREUR ($response)"
    fi
done

echo ""
echo "🎯 RÉSUMÉ"
echo "=========="
echo "✅ Corrections appliquées:"
echo "   - Permissions des fichiers config corrigées"
echo "   - Permissions des dossiers src corrigées"
echo "   - Permissions des templates corrigées"
echo "   - Tests de fonctionnement effectués"
echo ""
echo "🌐 Testez votre site: https://iot.olution.info/ffp3/"
echo "📋 Si problème persiste: php diagnostic-complet.php"
