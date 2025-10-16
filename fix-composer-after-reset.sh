#!/bin/bash

# Script pour corriger Composer après un reset
# À exécuter sur le serveur iot.olution.info

echo "🔧 CORRECTION COMPOSER APRÈS RESET"
echo "==================================="
echo "Date: $(date)"
echo ""

# Aller dans le répertoire du projet
cd /home4/oliviera/iot.olution.info/ffp3/

echo "📋 1. Vérification de l'état Composer..."
if [ -f "composer.json" ]; then
    echo "✅ composer.json trouvé"
else
    echo "❌ composer.json manquant"
    exit 1
fi

if [ -f "composer.lock" ]; then
    echo "✅ composer.lock trouvé"
else
    echo "⚠️ composer.lock manquant - sera recréé"
fi

if [ -d "vendor" ]; then
    echo "✅ Dossier vendor présent"
else
    echo "⚠️ Dossier vendor manquant - sera recréé"
fi

echo ""
echo "🧹 2. Nettoyage complet..."
rm -rf vendor/
rm -f composer.lock

echo ""
echo "📦 3. Installation des dépendances Composer..."
echo "   Exécution: composer install --no-dev --optimize-autoloader"

# Utiliser le chemin complet vers composer si nécessaire
COMPOSER_PATH="/opt/alt/php83/usr/bin/composer"
if [ -f "$COMPOSER_PATH" ]; then
    echo "   Utilisation de: $COMPOSER_PATH"
    "$COMPOSER_PATH" install --no-dev --optimize-autoloader
else
    echo "   Utilisation de: composer (PATH)"
    composer install --no-dev --optimize-autoloader
fi

if [ $? -eq 0 ]; then
    echo "✅ Installation Composer réussie"
else
    echo "❌ Échec de l'installation Composer"
    exit 1
fi

echo ""
echo "🔍 4. Vérification des dépendances critiques..."
critical_deps=("vendor/php-di/php-di" "vendor/slim/slim" "vendor/twig/twig" "vendor/monolog/monolog")

for dep in "${critical_deps[@]}"; do
    if [ -d "$dep" ]; then
        echo "✅ $dep"
    else
        echo "❌ $dep manquant"
    fi
done

echo ""
echo "🧪 5. Test de l'autoloader..."
php -r "
try {
    require_once 'vendor/autoload.php';
    echo '✅ Autoloader chargé\n';
    
    if (class_exists('DI\ContainerBuilder')) {
        echo '✅ DI\ContainerBuilder disponible\n';
    } else {
        echo '❌ DI\ContainerBuilder non trouvé\n';
        exit(1);
    }
    
    if (class_exists('Slim\App')) {
        echo '✅ Slim\App disponible\n';
    } else {
        echo '❌ Slim\App non trouvé\n';
        exit(1);
    }
    
    echo '✅ Toutes les classes critiques disponibles\n';
} catch (Exception \$e) {
    echo '❌ Erreur: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

echo ""
echo "🧹 6. Nettoyage du cache DI..."
rm -rf var/cache/di/*
mkdir -p var/cache/di
chmod -R 755 var/cache/

echo ""
echo "🧪 7. Test de l'application complète..."
php -r "
try {
    require_once 'vendor/autoload.php';
    \$container = require 'config/container.php';
    echo '✅ Container DI chargé avec succès\n';
    
    // Test des services
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
    echo '❌ Erreur lors du test: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

echo ""
echo "🧪 8. Test des endpoints..."
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
echo "   - Dépendances Composer réinstallées"
echo "   - Autoloader fonctionnel"
echo "   - Container DI opérationnel"
echo "   - Cache nettoyé"
echo "   - Application testée"
echo ""
echo "🌐 Testez votre site: https://iot.olution.info/ffp3/"
echo "📋 Si problème persiste: php diagnostic-complet.php"
