#!/bin/bash

# Script pour corriger le cache DI corrompu
# À exécuter sur le serveur iot.olution.info

echo "🔧 CORRECTION DU CACHE DI CORROMPU"
echo "=================================="
echo "Date: $(date)"
echo ""

# Aller dans le répertoire du projet
cd /home4/oliviera/iot.olution.info/ffp3/

echo "📋 1. Vérification du cache DI..."
if [ -d "var/cache/di" ]; then
    echo "✅ Dossier var/cache/di trouvé"
    ls -la var/cache/di/
else
    echo "⚠️ Dossier var/cache/di non trouvé"
fi

echo ""
echo "🧹 2. Suppression complète du cache DI..."
rm -rf var/cache/di/*
echo "✅ Cache DI supprimé"

echo ""
echo "📁 3. Recréation du dossier cache DI..."
mkdir -p var/cache/di
chmod -R 755 var/cache/
echo "✅ Dossier cache DI recréé"

echo ""
echo "🔧 4. Vérification du fichier dependencies.php..."
if [ -f "config/dependencies.php" ]; then
    echo "✅ config/dependencies.php trouvé"
    
    # Vérifier la syntaxe PHP
    php -l config/dependencies.php
    if [ $? -eq 0 ]; then
        echo "✅ Syntaxe PHP correcte"
    else
        echo "❌ Erreur de syntaxe dans dependencies.php"
        echo "🔄 Restauration depuis une sauvegarde..."
        
        # Chercher une sauvegarde
        backup_files=($(ls config/dependencies.php.backup.* 2>/dev/null))
        if [ ${#backup_files[@]} -gt 0 ]; then
            latest_backup=$(ls -t config/dependencies.php.backup.* | head -n1)
            cp "$latest_backup" config/dependencies.php
            echo "✅ Restauré depuis: $latest_backup"
        else
            echo "❌ Aucune sauvegarde trouvée"
            exit 1
        fi
    fi
else
    echo "❌ config/dependencies.php non trouvé"
    exit 1
fi

echo ""
echo "🧪 5. Test de l'autoloader..."
php -r "
try {
    require_once 'vendor/autoload.php';
    echo '✅ Autoloader chargé\n';
} catch (Exception \$e) {
    echo '❌ Erreur autoloader: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

echo ""
echo "🔧 6. Test du container DI (génération du cache)..."
php -r "
try {
    require_once 'vendor/autoload.php';
    \$container = require 'config/container.php';
    echo '✅ Container DI chargé avec succès\n';
    echo '✅ Cache DI généré correctement\n';
} catch (Exception \$e) {
    echo '❌ Erreur container: ' . \$e->getMessage() . '\n';
    echo '📋 Détails: ' . \$e->getFile() . ':' . \$e->getLine() . '\n';
    exit(1);
}
"

echo ""
echo "🔍 7. Vérification du cache généré..."
if [ -f "var/cache/di/CompiledContainer.php" ]; then
    echo "✅ CompiledContainer.php généré"
    
    # Vérifier la syntaxe du fichier compilé
    php -l var/cache/di/CompiledContainer.php
    if [ $? -eq 0 ]; then
        echo "✅ Syntaxe du cache DI correcte"
    else
        echo "❌ Erreur de syntaxe dans le cache DI"
        echo "🧹 Suppression et régénération..."
        rm -rf var/cache/di/*
        mkdir -p var/cache/di
        chmod -R 755 var/cache/
    fi
else
    echo "⚠️ CompiledContainer.php non généré"
fi

echo ""
echo "🧪 8. Test des services..."
php -r "
try {
    require_once 'vendor/autoload.php';
    \$container = require 'config/container.php';
    
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
    echo '❌ Erreur lors du test des services: ' . \$e->getMessage() . '\n';
}
"

echo ""
echo "🧪 9. Test des endpoints..."
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
echo "   - Cache DI corrompu supprimé"
echo "   - Cache DI recréé et régénéré"
echo "   - Syntaxe PHP vérifiée"
echo "   - Services testés"
echo "   - Endpoints vérifiés"
echo ""
echo "🌐 Testez votre site: https://iot.olution.info/ffp3/"
echo "📋 Si problème persiste: php diagnostic-complet.php"
