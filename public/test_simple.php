<?php
/**
 * Test simple pour vérifier le fonctionnement de base
 * Placé dans public/ pour être accessible via Slim
 */

// Test 1: PHP fonctionne
echo "✅ PHP fonctionne - Version: " . PHP_VERSION . "\n";

// Test 2: Autoloader
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    echo "✅ Autoloader chargé\n";
} else {
    echo "❌ Autoloader manquant\n";
    exit(1);
}

// Test 3: Chargement .env
try {
    if (class_exists('App\\Config\\Env')) {
        App\Config\Env::load();
        echo "✅ Configuration .env chargée\n";
    } else {
        echo "❌ Classe Env non trouvée\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur chargement .env: " . $e->getMessage() . "\n";
}

// Test 4: Connexion DB
try {
    if (isset($_ENV['DB_HOST'], $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS'])) {
        $dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4";
        $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS']);
        echo "✅ Connexion DB réussie\n";
        
        // Test table ffp3Outputs2
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM ffp3Outputs2 LIMIT 1");
        $result = $stmt->fetch();
        echo "✅ Table ffp3Outputs2 accessible (" . $result['count'] . " lignes)\n";
    } else {
        echo "❌ Variables DB manquantes\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur DB: " . $e->getMessage() . "\n";
}

// Test 5: Slim Framework
try {
    if (class_exists('Slim\\Factory\\AppFactory')) {
        $app = Slim\Factory\AppFactory::create();
        echo "✅ Slim App créée\n";
    } else {
        echo "❌ Slim Framework non trouvé\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur Slim: " . $e->getMessage() . "\n";
}

// Test 6: PHP-DI
try {
    if (class_exists('DI\\ContainerBuilder')) {
        $builder = new DI\ContainerBuilder();
        echo "✅ ContainerBuilder créé\n";
    } else {
        echo "❌ PHP-DI non trouvé\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur PHP-DI: " . $e->getMessage() . "\n";
}

// Test 7: Classes métier
try {
    if (class_exists('App\\Service\\OutputService')) {
        echo "✅ OutputService trouvée\n";
    } else {
        echo "❌ OutputService non trouvée\n";
    }
    
    if (class_exists('App\\Repository\\OutputRepository')) {
        echo "✅ OutputRepository trouvée\n";
    } else {
        echo "❌ OutputRepository non trouvée\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur classes métier: " . $e->getMessage() . "\n";
}

echo "\n🎯 Test terminé - " . date('Y-m-d H:i:s') . "\n";
?>
