#!/bin/bash

# Script de correction des services du container DI
# À exécuter sur le serveur iot.olution.info

echo "🔧 CORRECTION DES SERVICES DU CONTAINER DI"
echo "=========================================="
echo "Date: $(date)"
echo ""

# Aller dans le répertoire du projet
cd /home4/oliviera/iot.olution.info/ffp3/

echo "📋 1. Sauvegarde du fichier dependencies.php..."
cp config/dependencies.php config/dependencies.php.backup.$(date +%Y%m%d_%H%M%S)
echo "✅ Sauvegarde créée"

echo ""
echo "🔧 2. Ajout des services manquants..."

# Créer un script PHP temporaire pour modifier le fichier
cat > temp_fix_deps.php << 'EOF'
<?php
$file = 'config/dependencies.php';
$content = file_get_contents($file);

// Ajouter les alias après la définition de PDO
$additional = '
    // ====================================================================
    // ALIASES POUR COMPATIBILITÉ
    // ====================================================================
    "pdo" => function (ContainerInterface $c) {
        return $c->get(PDO::class);
    },

    "logger" => function (ContainerInterface $c) {
        return $c->get(LogService::class);
    },

    "twig" => function (ContainerInterface $c) {
        return $c->get(TemplateRenderer::class);
    },

';

// Trouver et remplacer la fin de la définition PDO
$pattern = '/(PDO::class => function \(ContainerInterface \$c\) \{\s*return Database::getConnection\(\);\s*\},\s*\n)/';
$replacement = '$1' . $additional;

$newContent = preg_replace($pattern, $replacement, $content);

if ($newContent !== $content) {
    file_put_contents($file, $newContent);
    echo "Services ajoutés avec succès\n";
} else {
    echo "Erreur lors de la modification\n";
    exit(1);
}
EOF

# Exécuter le script de correction
php temp_fix_deps.php
rm temp_fix_deps.php

echo ""
echo "🧹 3. Nettoyage du cache DI..."
rm -rf var/cache/di/*
mkdir -p var/cache/di
chmod -R 755 var/cache/
echo "✅ Cache DI nettoyé"

echo ""
echo "🧪 4. Test de la configuration..."
php -r "
try {
    require_once 'vendor/autoload.php';
    \$container = require 'config/container.php';
    echo 'Container chargé avec succès\n';
    
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
    echo 'Erreur: ' . \$e->getMessage() . '\n';
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
echo "   - Services pdo, logger, twig ajoutés comme alias"
echo "   - Cache DI nettoyé"
echo "   - Configuration testée"
echo ""
echo "🌐 Testez votre site: https://iot.olution.info/ffp3/"
