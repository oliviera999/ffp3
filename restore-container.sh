#!/bin/bash

# Script de restauration d'urgence du container
# À exécuter si config/container.php est cassé

echo "🚨 RESTAURATION D'URGENCE DU CONTAINER"
echo "======================================"
echo "Date: $(date)"
echo ""

# Aller dans le répertoire du projet
cd /home4/oliviera/iot.olution.info/ffp3/

echo "📋 1. Vérification des fichiers de sauvegarde..."
backup_files=($(ls config/container.php.backup.* 2>/dev/null))
if [ ${#backup_files[@]} -eq 0 ]; then
    echo "❌ Aucune sauvegarde trouvée"
    echo "🔧 Recréation du fichier container.php..."
    
    # Recréer le fichier container.php
    cat > config/container.php << 'EOF'
<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

// Charger les variables d'environnement
App\Config\Env::load();

$containerBuilder = new ContainerBuilder();

// Activer la compilation du container en production pour meilleures performances
if (($_ENV['ENV'] ?? 'prod') === 'prod') {
    $containerBuilder->enableCompilation(__DIR__ . '/../var/cache/di');
    $containerBuilder->writeProxiesToFile(true, __DIR__ . '/../var/cache/di/proxies');
}

// Charger les définitions
$containerBuilder->addDefinitions(__DIR__ . '/dependencies.php');

// Build et retourner le container
return $containerBuilder->build();
EOF
    
    echo "✅ Fichier container.php recréé"
else
    echo "✅ Sauvegarde(s) trouvée(s):"
    for backup in "${backup_files[@]}"; do
        echo "   - $backup"
    done
    
    echo ""
    echo "🔄 2. Restauration depuis la sauvegarde la plus récente..."
    latest_backup=$(ls -t config/container.php.backup.* | head -n1)
    cp "$latest_backup" config/container.php
    echo "✅ Restauré depuis: $latest_backup"
fi

echo ""
echo "🔐 3. Correction des permissions..."
chmod 644 config/container.php
chmod 644 config/dependencies.php
chmod 755 config/

echo ""
echo "🧹 4. Nettoyage du cache DI..."
rm -rf var/cache/di/*
mkdir -p var/cache/di
chmod -R 755 var/cache/

echo ""
echo "🧪 5. Test de la restauration..."
php -r "
try {
    require_once 'vendor/autoload.php';
    \$container = require 'config/container.php';
    echo '✅ Container restauré et fonctionnel\n';
} catch (Exception \$e) {
    echo '❌ Erreur après restauration: ' . \$e->getMessage() . '\n';
}
"

echo ""
echo "🧪 6. Test des endpoints..."
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
echo "✅ Restauration terminée:"
echo "   - Fichier container.php restauré/recréé"
echo "   - Permissions corrigées"
echo "   - Cache DI nettoyé"
echo "   - Tests effectués"
echo ""
echo "🌐 Testez votre site: https://iot.olution.info/ffp3/"
echo "📋 Diagnostic complet: php diagnostic-complet.php"
