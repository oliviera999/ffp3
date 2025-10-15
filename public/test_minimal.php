<?php
/**
 * Test minimal pour isoler le problème
 * Placé dans public/ pour être accessible via Slim
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TEST MINIMAL FFP3 ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

try {
    echo "1. Test PHP: ";
    echo "OK (" . PHP_VERSION . ")\n";
    
    echo "2. Test autoloader: ";
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
        echo "OK\n";
    } else {
        echo "ÉCHEC - Fichier manquant\n";
        echo "Chemin testé: " . __DIR__ . '/../vendor/autoload.php' . "\n";
        echo "Répertoire courant: " . __DIR__ . "\n";
        echo "Fichiers dans le répertoire courant:\n";
        $files = scandir(__DIR__);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                echo "  - $file\n";
            }
        }
        exit(1);
    }
    
    echo "3. Test chargement .env: ";
    App\Config\Env::load();
    echo "OK\n";
    
    echo "4. Test variables DB: ";
    if (isset($_ENV['DB_HOST'], $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS'])) {
        echo "OK\n";
    } else {
        echo "ÉCHEC - Variables manquantes\n";
        var_dump($_ENV);
        exit(1);
    }
    
    echo "5. Test connexion DB: ";
    $dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4";
    $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS']);
    echo "OK\n";
    
    echo "6. Test table ffp3Outputs2: ";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM ffp3Outputs2 LIMIT 1");
    $result = $stmt->fetch();
    echo "OK (" . $result['count'] . " lignes)\n";
    
    echo "7. Test Slim: ";
    $app = Slim\Factory\AppFactory::create();
    echo "OK\n";
    
    echo "8. Test PHP-DI: ";
    $builder = new DI\ContainerBuilder();
    echo "OK\n";
    
    echo "\n✅ TOUS LES TESTS RÉUSSIS\n";
    
} catch (Throwable $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
?>
