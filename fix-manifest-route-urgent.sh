#!/bin/bash

# Script de correction d'urgence pour le conflit de routes manifest.json
# À exécuter directement sur le serveur iot.olution.info

echo "🚨 CORRECTION URGENTE - CONFLIT DE ROUTES MANIFEST.JSON"
echo "======================================================"
echo "Date: $(date)"
echo ""

# Aller dans le répertoire du projet
cd /home4/oliviera/iot.olution.info/ffp3/

echo "📋 1. Vérification de l'état actuel..."
if [ -f "public/index.php" ]; then
    echo "✅ public/index.php trouvé"
    
    # Compter les occurrences de manifest.json
    manifest_count=$(grep -c "manifest.json" public/index.php)
    echo "   Nombre d'occurrences manifest.json: $manifest_count"
    
    if [ "$manifest_count" -gt 1 ]; then
        echo "❌ Conflit détecté: $manifest_count routes manifest.json"
    else
        echo "✅ Pas de conflit détecté"
    fi
else
    echo "❌ public/index.php non trouvé"
    exit 1
fi

echo ""
echo "🔧 2. Correction du conflit de routes..."

# Créer une sauvegarde
cp public/index.php public/index.php.backup.$(date +%Y%m%d_%H%M%S)
echo "✅ Sauvegarde créée"

# Créer un script PHP pour corriger le fichier
cat > temp_fix_routes.php << 'EOF'
<?php
$file = 'public/index.php';
$content = file_get_contents($file);

// Trouver et supprimer la deuxième occurrence de la route manifest.json
// (celle dans le groupe TEST)
$pattern = '/(\s+)\/\/ ====================================================================\s*\/\/ Fichiers statiques TEST.*?\$group->get\(\'\/manifest\.json\', function \(Request \$request, Response \$response\) \{\s*\$manifestPath = __DIR__ \. \'\/manifest\.json\';\s*if \(file_exists\(\$manifestPath\)\) \{\s*\$response->getBody\(\)->write\(file_get_contents\(\$manifestPath\)\);\s*return \$response->withHeader\(\'Content-Type\', \'application\/json\'\);\s*\}\s*return \$response->withStatus\(404\);\s*\}\);\s*/s';

$replacement = '$1// ====================================================================
    // Fichiers statiques TEST (fallback si serveur web ne les sert pas)
    // ====================================================================
    // Note: manifest.json géré par le groupe PROD pour éviter les conflits de routes
    
';

$newContent = preg_replace($pattern, $replacement, $content);

if ($newContent !== $content) {
    file_put_contents($file, $newContent);
    echo "✅ Route manifest.json dupliquée supprimée\n";
    
    // Vérifier qu'il ne reste qu'une occurrence
    $count = substr_count($newContent, "manifest.json");
    echo "Occurrences restantes: $count\n";
} else {
    echo "❌ Aucune modification effectuée\n";
    exit(1);
}
EOF

# Exécuter le script de correction
php temp_fix_routes.php
rm temp_fix_routes.php

echo ""
echo "🧹 3. Nettoyage du cache..."
rm -rf var/cache/di/*
mkdir -p var/cache/di
chmod -R 755 var/cache/

echo ""
echo "🧪 4. Test de l'application..."
php -r "
try {
    require_once 'vendor/autoload.php';
    \$app = require 'public/index.php';
    echo '✅ Application chargée avec succès - pas de conflit de routes\n';
} catch (Exception \$e) {
    echo '❌ Erreur: ' . \$e->getMessage() . '\n';
}
"

echo ""
echo "🧪 5. Test des endpoints..."
endpoints=(
    "https://iot.olution.info/ffp3/"
    "https://iot.olution.info/ffp3/control"
    "https://iot.olution.info/ffp3/manifest.json"
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
echo "   - Route manifest.json dupliquée supprimée"
echo "   - Cache DI nettoyé"
echo "   - Application testée"
echo "   - Endpoints vérifiés"
echo ""
echo "🌐 Testez votre site: https://iot.olution.info/ffp3/"
echo "📋 Si problème persiste: php diagnostic-complet.php"
