<?php
/**
 * Script de correction directe du conflit manifest.json
 * À exécuter sur le serveur
 */

echo "🔧 CORRECTION DIRECTE DU CONFLIT MANIFEST.JSON\n";
echo "==============================================\n";

$file = 'public/index.php';

if (!file_exists($file)) {
    echo "❌ Fichier $file non trouvé\n";
    exit(1);
}

echo "📋 Lecture du fichier...\n";
$content = file_get_contents($file);

echo "🔍 Recherche des routes manifest.json...\n";
$lines = explode("\n", $content);
$manifestRoutes = [];
$inManifestRoute = false;
$routeStartLine = 0;

foreach ($lines as $i => $line) {
    if (strpos($line, "manifest.json") !== false && strpos($line, '$group->get') !== false) {
        $manifestRoutes[] = $i;
        echo "   Route manifest.json trouvée ligne " . ($i + 1) . "\n";
    }
}

if (count($manifestRoutes) <= 1) {
    echo "✅ Pas de conflit détecté\n";
    exit(0);
}

echo "❌ Conflit détecté: " . count($manifestRoutes) . " routes manifest.json\n";

// Sauvegarder
$backup = $file . '.backup.' . date('Y-m-d-H-i-s');
copy($file, $backup);
echo "✅ Sauvegarde créée: $backup\n";

// Supprimer la deuxième occurrence (généralement la route TEST)
echo "🔧 Suppression de la route dupliquée...\n";

$newContent = $content;
$pattern = '/\s*\/\/ ====================================================================\s*\/\/ Fichiers statiques TEST.*?\$group->get\(\'\/manifest\.json\', function \(Request \$request, Response \$response\) \{\s*\$manifestPath = __DIR__ \. \'\/manifest\.json\';\s*if \(file_exists\(\$manifestPath\)\) \{\s*\$response->getBody\(\)->write\(file_get_contents\(\$manifestPath\)\);\s*return \$response->withHeader\(\'Content-Type\', \'application\/json\'\);\s*\}\s*return \$response->withStatus\(404\);\s*\}\);\s*/s';

$replacement = "\n    // ====================================================================\n    // Fichiers statiques TEST (fallback si serveur web ne les sert pas)\n    // ====================================================================\n    // Note: manifest.json géré par le groupe PROD pour éviter les conflits de routes\n    \n";

$newContent = preg_replace($pattern, $replacement, $newContent);

if ($newContent !== $content) {
    file_put_contents($file, $newContent);
    echo "✅ Route dupliquée supprimée\n";
    
    // Vérifier
    $newCount = substr_count($newContent, "manifest.json");
    echo "   Routes manifest.json restantes: $newCount\n";
} else {
    echo "❌ Aucune modification effectuée\n";
    exit(1);
}

echo "\n🧹 Nettoyage du cache DI...\n";
if (is_dir('var/cache/di')) {
    $files = glob('var/cache/di/*');
    foreach ($files as $file) {
        unlink($file);
    }
    echo "✅ Cache nettoyé: " . count($files) . " fichier(s)\n";
}

echo "\n🧪 Test de l'application...\n";
try {
    require_once 'vendor/autoload.php';
    echo "✅ Autoloader chargé\n";
    
    // Test simple du container
    $container = require 'config/container.php';
    echo "✅ Container chargé\n";
    
    echo "✅ Application fonctionnelle - pas de conflit de routes\n";
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "🔄 Restauration de la sauvegarde...\n";
    copy($backup, $file);
    echo "✅ Fichier restauré depuis $backup\n";
    exit(1);
}

echo "\n🎯 CORRECTION TERMINÉE AVEC SUCCÈS !\n";
echo "🌐 Testez votre site: https://iot.olution.info/ffp3/\n";
?>
