<?php
/**
 * Script de diagnostic complet pour FFP3
 * À exécuter sur le serveur pour identifier les problèmes
 */

echo "🔍 DIAGNOSTIC COMPLET FFP3\n";
echo "==========================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Configuration
$basePath = __DIR__;
$errors = [];

echo "📋 1. Vérification de l'environnement PHP\n";
echo "----------------------------------------\n";
echo "Version PHP: " . PHP_VERSION . "\n";
echo "Extensions PHP:\n";
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'curl'];
foreach ($requiredExtensions as $ext) {
    echo "  " . ($extension_loaded($ext) ? "✅" : "❌") . " $ext\n";
    if (!extension_loaded($ext)) {
        $errors[] = "Extension PHP manquante: $ext";
    }
}

echo "\n📁 2. Vérification des fichiers critiques\n";
echo "----------------------------------------\n";
$criticalFiles = [
    'composer.json',
    'composer.lock',
    'vendor/autoload.php',
    'config/container.php',
    'public/index.php',
    '.env'
];

foreach ($criticalFiles as $file) {
    $exists = file_exists($file);
    echo "  " . ($exists ? "✅" : "❌") . " $file\n";
    if (!$exists) {
        $errors[] = "Fichier critique manquant: $file";
    }
}

echo "\n📦 3. Vérification des dépendances Composer\n";
echo "-------------------------------------------\n";
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
    
    $requiredClasses = [
        'DI\ContainerBuilder',
        'Slim\App',
        'Twig\Environment',
        'Monolog\Logger'
    ];
    
    foreach ($requiredClasses as $class) {
        $exists = class_exists($class);
        echo "  " . ($exists ? "✅" : "❌") . " $class\n";
        if (!$exists) {
            $errors[] = "Classe manquante: $class";
        }
    }
} else {
    $errors[] = "Autoloader Composer non trouvé";
}

echo "\n⚙️ 4. Test de configuration\n";
echo "--------------------------\n";
try {
    if (file_exists('.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
        echo "✅ Fichier .env chargé\n";
        
        // Vérifier les variables critiques
        $requiredVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
        foreach ($requiredVars as $var) {
            $value = $_ENV[$var] ?? null;
            echo "  " . ($value ? "✅" : "❌") . " $var\n";
            if (!$value) {
                $errors[] = "Variable d'environnement manquante: $var";
            }
        }
    } else {
        $errors[] = "Fichier .env manquant";
    }
} catch (Exception $e) {
    $errors[] = "Erreur chargement .env: " . $e->getMessage();
}

echo "\n🗄️ 5. Test de connexion base de données\n";
echo "--------------------------------------\n";
try {
    if (isset($_ENV['DB_HOST'])) {
        $dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4";
        $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        echo "✅ Connexion base de données réussie\n";
        
        // Test des tables
        $tables = ['ffp3Data', 'ffp3Outputs'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->rowCount() > 0;
            echo "  " . ($exists ? "✅" : "❌") . " Table $table\n";
            if (!$exists) {
                $errors[] = "Table manquante: $table";
            }
        }
    }
} catch (Exception $e) {
    $errors[] = "Erreur base de données: " . $e->getMessage();
}

echo "\n🔧 6. Test de chargement de l'application\n";
echo "---------------------------------------\n";
try {
    if (file_exists('config/container.php')) {
        $container = require 'config/container.php';
        echo "✅ Container DI chargé\n";
        
        // Test des services critiques
        $services = ['logger', 'twig', 'pdo'];
        foreach ($services as $service) {
            try {
                $container->get($service);
                echo "  ✅ Service $service disponible\n";
            } catch (Exception $e) {
                echo "  ❌ Service $service: " . $e->getMessage() . "\n";
                $errors[] = "Service $service non disponible";
            }
        }
    }
} catch (Exception $e) {
    $errors[] = "Erreur chargement container: " . $e->getMessage();
}

echo "\n📊 7. Résumé des erreurs\n";
echo "------------------------\n";
if (empty($errors)) {
    echo "✅ Aucune erreur détectée !\n";
} else {
    echo "❌ " . count($errors) . " erreur(s) détectée(s):\n";
    foreach ($errors as $i => $error) {
        echo "  " . ($i + 1) . ". $error\n";
    }
}

echo "\n🎯 Actions recommandées\n";
echo "----------------------\n";
if (!empty($errors)) {
    echo "1. Résoudre les erreurs listées ci-dessus\n";
    echo "2. Exécuter: composer update --no-dev --optimize-autoloader\n";
    echo "3. Vérifier les permissions des dossiers\n";
    echo "4. Redémarrer Apache: sudo systemctl restart apache2\n";
    echo "5. Consulter les logs: tail -f /var/log/apache2/error.log\n";
} else {
    echo "✅ Système en bon état de fonctionnement\n";
}

echo "\n📝 Logs à consulter:\n";
echo "- /var/log/apache2/error.log\n";
echo "- var/log/php_errors.log\n";
echo "- var/cache/ (nettoyer si nécessaire)\n";
