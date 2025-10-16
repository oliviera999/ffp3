<?php
/**
 * Script de diagnostic pour le serveur de production
 * À exécuter sur le serveur pour vérifier l'état du projet
 */

echo "🔍 Diagnostic du serveur FFP3\n";
echo "=============================\n\n";

// Vérifier PHP
echo "📋 Version PHP: " . PHP_VERSION . "\n";

// Vérifier Composer
echo "📦 Composer: ";
if (file_exists('composer.json')) {
    echo "✅ composer.json trouvé\n";
} else {
    echo "❌ composer.json manquant\n";
}

// Vérifier vendor
echo "📁 Dossier vendor: ";
if (is_dir('vendor')) {
    echo "✅ Présent\n";
    
    // Vérifier php-di
    if (is_dir('vendor/php-di')) {
        echo "   ✅ php-di installé\n";
    } else {
        echo "   ❌ php-di manquant\n";
    }
    
    // Vérifier autoloader
    if (file_exists('vendor/autoload.php')) {
        echo "   ✅ autoload.php présent\n";
    } else {
        echo "   ❌ autoload.php manquant\n";
    }
} else {
    echo "❌ Manquant - Exécutez 'composer install'\n";
}

// Vérifier les permissions
echo "\n🔐 Permissions:\n";
$dirs = ['public', 'config', 'src', 'var'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        echo "   $dir: $perms\n";
    }
}

// Vérifier .env
echo "\n⚙️ Configuration:\n";
if (file_exists('.env')) {
    echo "   ✅ .env présent\n";
} else {
    echo "   ❌ .env manquant\n";
}

// Test de chargement de classes
echo "\n🧪 Test de chargement des classes:\n";
try {
    require_once 'vendor/autoload.php';
    echo "   ✅ Autoloader chargé\n";
    
    if (class_exists('DI\ContainerBuilder')) {
        echo "   ✅ DI\ContainerBuilder disponible\n";
    } else {
        echo "   ❌ DI\ContainerBuilder non trouvé\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n🎯 Actions recommandées:\n";
echo "1. Exécuter: composer install --no-dev --optimize-autoloader\n";
echo "2. Vérifier les permissions des dossiers\n";
echo "3. Redémarrer le serveur web si nécessaire\n";
