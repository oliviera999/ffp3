<?php
/**
 * Script de correction de la configuration du container DI
 * À exécuter sur le serveur pour résoudre les problèmes de services manquants
 */

echo "🔧 CORRECTION DE LA CONFIGURATION DU CONTAINER DI\n";
echo "=================================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

$configFile = 'config/dependencies.php';

echo "📋 1. Sauvegarde du fichier de configuration actuel...\n";
echo "------------------------------------------------------\n";

if (file_exists($configFile)) {
    $backupFile = $configFile . '.backup.' . date('Y-m-d-H-i-s');
    copy($configFile, $backupFile);
    echo "✅ Sauvegarde créée: $backupFile\n";
} else {
    echo "❌ Fichier $configFile non trouvé\n";
    exit(1);
}

echo "\n🔧 2. Ajout des services manquants...\n";
echo "------------------------------------\n";

// Lire le contenu actuel
$content = file_get_contents($configFile);

// Ajouter les services manquants après la définition de PDO
$additionalServices = '
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

// Trouver la position après la définition de PDO et insérer les nouveaux services
$pdoPattern = '/PDO::class => function \(ContainerInterface \$c\) \{\s*return Database::getConnection\(\);\s*\},/';
$replacement = '$0' . $additionalServices;

$newContent = preg_replace($pdoPattern, $replacement, $content);

if ($newContent !== $content) {
    // Écrire le nouveau contenu
    file_put_contents($configFile, $newContent);
    echo "✅ Services ajoutés avec succès\n";
    echo "   - pdo (alias pour PDO::class)\n";
    echo "   - logger (alias pour LogService::class)\n";
    echo "   - twig (alias pour TemplateRenderer::class)\n";
} else {
    echo "❌ Impossible de modifier le fichier\n";
    exit(1);
}

echo "\n🧪 3. Test de la nouvelle configuration...\n";
echo "----------------------------------------\n";

try {
    // Charger l'environnement
    if (file_exists('.env')) {
        $envContent = file_get_contents('.env');
        $lines = explode("\n", $envContent);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line) && substr($line, 0, 1) !== '#') {
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $_ENV[trim($parts[0])] = trim($parts[1]);
                }
            }
        }
    }

    // Charger l'autoloader
    require_once 'vendor/autoload.php';

    // Charger le container
    $container = require 'config/container.php';

    echo "✅ Container chargé avec succès\n";

    // Tester les services
    $services = ['pdo', 'logger', 'twig'];
    foreach ($services as $service) {
        try {
            $instance = $container->get($service);
            echo "  ✅ Service $service: " . get_class($instance) . "\n";
        } catch (Exception $e) {
            echo "  ❌ Service $service: " . $e->getMessage() . "\n";
        }
    }

} catch (Exception $e) {
    echo "❌ Erreur lors du test: " . $e->getMessage() . "\n";
}

echo "\n🧹 4. Nettoyage du cache DI...\n";
echo "----------------------------\n";

$cacheDir = 'var/cache/di';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*');
    $count = count($files);
    foreach ($files as $file) {
        unlink($file);
    }
    echo "✅ Cache DI nettoyé: $count fichier(s) supprimé(s)\n";
} else {
    echo "ℹ️ Aucun cache DI à nettoyer\n";
}

echo "\n🎯 RÉSUMÉ\n";
echo "==========\n";
echo "✅ Corrections appliquées:\n";
echo "   - Services pdo, logger, twig ajoutés comme alias\n";
echo "   - Cache DI nettoyé\n";
echo "   - Configuration sauvegardée\n";
echo "\n📝 Actions suivantes:\n";
echo "   1. Tester les endpoints: php check-endpoints.php\n";
echo "   2. Vérifier le site: https://iot.olution.info/ffp3/\n";
echo "   3. Si problème persiste, restaurer: cp $backupFile $configFile\n";
