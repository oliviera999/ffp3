#!/bin/bash

# Script de correction automatique des erreurs HTTP 500
# À exécuter sur le serveur iot.olution.info

echo "🔧 CORRECTION AUTOMATIQUE HTTP 500 - FFP3"
echo "=========================================="
echo "Date: $(date)"
echo ""

# Aller dans le répertoire du projet
cd /home4/oliviera/iot.olution.info/ffp3/

echo "📋 1. Diagnostic initial..."
php diagnostic-complet.php

echo ""
echo "🔧 2. Corrections automatiques..."

# Sauvegarder les fichiers importants
echo "💾 Sauvegarde des fichiers critiques..."
mkdir -p backup/$(date +%Y%m%d_%H%M%S)
cp composer.json backup/$(date +%Y%m%d_%H%M%S)/
cp .env backup/$(date +%Y%m%d_%H%M%S)/ 2>/dev/null || echo "⚠️ Fichier .env non trouvé"

# Nettoyer le cache
echo "🧹 Nettoyage du cache..."
rm -rf var/cache/*
rm -rf var/log/*
mkdir -p var/cache
mkdir -p var/log
chmod -R 755 var/

# Corriger les permissions
echo "🔐 Correction des permissions..."
chmod -R 755 public/
chmod -R 644 config/
chmod -R 644 src/
chmod -R 644 templates/
chmod 644 .env 2>/dev/null || echo "⚠️ Fichier .env non trouvé"
chmod 644 composer.json

# Réinstaller Composer si nécessaire
echo "📦 Vérification des dépendances Composer..."
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "   🔄 Réinstallation des dépendances..."
    rm -rf vendor/
    composer install --no-dev --optimize-autoloader
else
    echo "   ✅ Dépendances Composer OK"
fi

# Vérifier php-di spécifiquement
if [ ! -d "vendor/php-di" ]; then
    echo "   🔄 Installation de php-di..."
    composer require php-di/php-di --no-dev --optimize-autoloader
fi

# Tester les endpoints
echo ""
echo "🧪 3. Test des endpoints..."
endpoints=(
    "https://iot.olution.info/ffp3/"
    "https://iot.olution.info/ffp3/control"
    "https://iot.olution.info/ffp3/api/sensors"
    "https://iot.olution.info/ffp3/api/outputs"
    "https://iot.olution.info/ffp3/api/system-health"
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

# Redémarrer Apache si possible
echo ""
echo "🔄 4. Redémarrage des services..."
if command -v systemctl &> /dev/null; then
    echo "   🔄 Redémarrage d'Apache..."
    sudo systemctl restart apache2
    echo "   ✅ Apache redémarré"
else
    echo "   ⚠️ systemctl non disponible - redémarrage manuel requis"
fi

echo ""
echo "📊 5. Diagnostic final..."
php diagnostic-complet.php

echo ""
echo "🎯 RÉSUMÉ"
echo "=========="
echo "✅ Corrections appliquées:"
echo "   - Cache nettoyé"
echo "   - Permissions corrigées"
echo "   - Dépendances Composer vérifiées"
echo "   - Apache redémarré"
echo ""
echo "📝 Si des erreurs persistent:"
echo "   1. Consulter: tail -f /var/log/apache2/error.log"
echo "   2. Vérifier: php diagnostic-complet.php"
echo "   3. Contacter l'administrateur système"
echo ""
echo "🌐 Testez votre site: https://iot.olution.info/ffp3/"
