#!/usr/bin/env php
<?php
/**
 * Script de vidage des caches de production
 * 
 * Vide les caches Twig et DI Container pour forcer la recompilation
 * après un déploiement ou une modification du code.
 * 
 * Usage: php bin/clear-cache.php
 */

declare(strict_types=1);

// Chemins des répertoires de cache
$projectRoot = dirname(__DIR__);
$cacheDirs = [
    $projectRoot . '/var/cache/twig',
    $projectRoot . '/var/cache/di',
];

echo "\n🧹 Vidage des caches en cours...\n\n";

$totalDeleted = 0;
$errors = [];

foreach ($cacheDirs as $cacheDir) {
    $dirName = basename($cacheDir);
    
    if (!is_dir($cacheDir)) {
        echo "ℹ️  {$dirName}/ : N'existe pas encore, rien à vider.\n";
        continue;
    }
    
    echo "🗑️  Vidage de {$dirName}/...\n";
    
    try {
        $deleted = deleteRecursive($cacheDir);
        $totalDeleted += $deleted;
        echo "   ✅ {$deleted} fichier(s) supprimé(s)\n";
        
        // Recréer le dossier vide avec les bonnes permissions
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
            echo "   📁 Dossier recréé\n";
        }
    } catch (Exception $e) {
        $errors[] = "Erreur sur {$dirName}: " . $e->getMessage();
        echo "   ❌ ERREUR : " . $e->getMessage() . "\n";
    }
}

echo "\n";

if (empty($errors)) {
    echo "✅ Cache vidé avec succès ! ({$totalDeleted} fichier(s) au total)\n";
    echo "ℹ️  Les caches seront régénérés automatiquement à la prochaine requête.\n\n";
    exit(0);
} else {
    echo "⚠️  Le cache a été partiellement vidé avec " . count($errors) . " erreur(s) :\n";
    foreach ($errors as $error) {
        echo "   - {$error}\n";
    }
    echo "\n";
    exit(1);
}

/**
 * Supprime récursivement un répertoire et son contenu
 * 
 * @param string $dir Chemin du répertoire à supprimer
 * @return int Nombre de fichiers supprimés
 */
function deleteRecursive(string $dir): int
{
    $count = 0;
    
    if (!is_dir($dir)) {
        return 0;
    }
    
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($items as $item) {
        if ($item->isDir()) {
            rmdir($item->getRealPath());
        } else {
            unlink($item->getRealPath());
            $count++;
        }
    }
    
    // Ne pas supprimer le dossier racine du cache, seulement son contenu
    // rmdir($dir);
    
    return $count;
}

