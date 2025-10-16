<?php
/**
 * Script de vérification des permissions pour FFP3
 * À exécuter pour diagnostiquer les problèmes de permissions
 */

echo "🔍 VÉRIFICATION DES PERMISSIONS FFP3\n";
echo "=====================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

$basePath = __DIR__;
$errors = [];

echo "📋 1. Informations système\n";
echo "-------------------------\n";
echo "Utilisateur PHP: " . get_current_user() . "\n";
echo "Utilisateur système: " . (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : 'N/A') . "\n";
echo "Répertoire de travail: $basePath\n";

echo "\n📁 2. Vérification des permissions des dossiers critiques\n";
echo "--------------------------------------------------------\n";
$criticalDirs = [
    'src',
    'src/Config',
    'vendor',
    'vendor/composer',
    'public',
    'config',
    'var'
];

foreach ($criticalDirs as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $readable = is_readable($dir);
        $writable = is_writable($dir);
        echo "  " . ($readable ? "✅" : "❌") . " $dir: $perms " . ($readable ? "R" : "-") . ($writable ? "W" : "-") . "\n";
        
        if (!$readable) {
            $errors[] = "Dossier non lisible: $dir";
        }
    } else {
        echo "  ❌ $dir: INEXISTANT\n";
        $errors[] = "Dossier manquant: $dir";
    }
}

echo "\n📄 3. Vérification des permissions des fichiers critiques\n";
echo "-------------------------------------------------------\n";
$criticalFiles = [
    'src/Config/Env.php',
    'src/Config/Database.php',
    'src/Config/TableConfig.php',
    'config/container.php',
    'public/index.php',
    'vendor/autoload.php',
    'composer.json',
    '.env'
];

foreach ($criticalFiles as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        $readable = is_readable($file);
        $writable = is_writable($file);
        echo "  " . ($readable ? "✅" : "❌") . " $file: $perms " . ($readable ? "R" : "-") . ($writable ? "W" : "-") . "\n";
        
        if (!$readable) {
            $errors[] = "Fichier non lisible: $file";
        }
    } else {
        echo "  ❌ $file: INEXISTANT\n";
        $errors[] = "Fichier manquant: $file";
    }
}

echo "\n🧪 4. Test de chargement de fichiers\n";
echo "------------------------------------\n";

// Test de chargement de src/Config/Env.php
echo "Test chargement src/Config/Env.php: ";
try {
    if (file_exists('src/Config/Env.php')) {
        $content = file_get_contents('src/Config/Env.php');
        if ($content !== false) {
            echo "✅ OK\n";
        } else {
            echo "❌ ERREUR - Impossible de lire le contenu\n";
            $errors[] = "Impossible de lire src/Config/Env.php";
        }
    } else {
        echo "❌ FICHIER MANQUANT\n";
        $errors[] = "Fichier src/Config/Env.php manquant";
    }
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    $errors[] = "Exception lors du chargement de src/Config/Env.php";
}

// Test de l'autoloader
echo "Test chargement vendor/autoload.php: ";
try {
    if (file_exists('vendor/autoload.php')) {
        require_once 'vendor/autoload.php';
        echo "✅ OK\n";
        
        // Test de la classe App\Config\Env
        echo "Test classe App\\Config\\Env: ";
        if (class_exists('App\Config\Env')) {
            echo "✅ OK\n";
        } else {
            echo "❌ CLASSE NON TROUVÉE\n";
            $errors[] = "Classe App\\Config\\Env non trouvée";
        }
    } else {
        echo "❌ FICHIER MANQUANT\n";
        $errors[] = "Fichier vendor/autoload.php manquant";
    }
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    $errors[] = "Exception lors du chargement de l'autoloader";
}

echo "\n📊 5. Résumé des erreurs\n";
echo "------------------------\n";
if (empty($errors)) {
    echo "✅ Aucune erreur de permissions détectée !\n";
} else {
    echo "❌ " . count($errors) . " erreur(s) détectée(s):\n";
    foreach ($errors as $i => $error) {
        echo "  " . ($i + 1) . ". $error\n";
    }
}

echo "\n🎯 ACTIONS RECOMMANDÉES\n";
echo "=======================\n";
if (!empty($errors)) {
    echo "1. Exécuter: bash fix-ownership.sh\n";
    echo "2. Ou manuellement:\n";
    echo "   chown -R $(whoami):$(whoami) .\n";
    echo "   find . -type d -exec chmod 755 {} \\;\n";
    echo "   find . -type f -exec chmod 644 {} \\;\n";
    echo "   chmod -R 755 public/\n";
    echo "   chmod -R 755 var/\n";
} else {
    echo "✅ Permissions correctes - Vérifier d'autres causes d'erreur\n";
    echo "1. Consulter les logs Apache: tail -f /var/log/apache2/error.log\n";
    echo "2. Vérifier la configuration PHP\n";
    echo "3. Redémarrer Apache: sudo systemctl restart apache2\n";
}

echo "\n📝 Commandes utiles:\n";
echo "- Voir les permissions: ls -la\n";
echo "- Voir le propriétaire: ls -la | head -10\n";
echo "- Changer le propriétaire: chown -R user:group .\n";
echo "- Changer les permissions: chmod -R 755 .\n";
