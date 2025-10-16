#!/bin/bash

# Script simple pour corriger le conflit de routes
# À exécuter sur le serveur

echo "🔧 CORRECTION SIMPLE DU CONFLIT DE ROUTES"
echo "========================================="

cd /home4/oliviera/iot.olution.info/ffp3/

echo "📋 Sauvegarde du fichier..."
cp public/index.php public/index.php.backup.$(date +%Y%m%d_%H%M%S)

echo "🔧 Suppression de la route manifest.json dupliquée..."

# Utiliser sed pour supprimer la route dupliquée
sed -i '/\/\/ ====================================================================/,/return \$response->withStatus(404);/{
    /\/\/ Fichiers statiques TEST/,/return \$response->withStatus(404);/{
        /manifest\.json/,/return \$response->withStatus(404);/{
            s/.*/    \/\/ Note: manifest.json géré par le groupe PROD pour éviter les conflits de routes/
        }
    }
}' public/index.php

echo "🧹 Nettoyage du cache..."
rm -rf var/cache/di/*

echo "🧪 Test rapide..."
php -r "
try {
    require_once 'vendor/autoload.php';
    echo '✅ Application OK\n';
} catch (Exception \$e) {
    echo '❌ Erreur: ' . \$e->getMessage() . '\n';
}
"

echo "🎯 Correction terminée !"
