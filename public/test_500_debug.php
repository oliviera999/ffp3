<?php
/**
 * Test spécifique pour diagnostiquer l'erreur 500
 * Utilise le même chemin que diagnostic.php qui fonctionne
 */

// Charger l'autoloader avec le bon chemin
require_once '../vendor/autoload.php';

echo "=== TEST DEBUG ERREUR 500 ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

try {
    echo "1. Chargement .env: ";
    App\Config\Env::load();
    echo "OK\n";
    
    echo "2. Test connexion DB: ";
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    echo "OK\n";
    
    echo "3. Test OutputRepository: ";
    $outputRepo = new App\Repository\OutputRepository($pdo);
    echo "OK\n";
    
    echo "4. Test OutputService: ";
    $boardRepo = new App\Repository\BoardRepository($pdo);
    $outputService = new App\Service\OutputService($outputRepo, $boardRepo);
    echo "OK\n";
    
    echo "5. Test getAllOutputs(): ";
    $outputs = $outputService->getAllOutputs();
    echo "OK (" . count($outputs) . " outputs trouvés)\n";
    
    echo "6. Test getActiveBoardsForCurrentEnvironment(): ";
    $boards = $outputService->getActiveBoardsForCurrentEnvironment();
    echo "OK (" . count($boards) . " boards trouvés)\n";
    
    echo "7. Test TableConfig::getEnvironment(): ";
    $env = App\Config\TableConfig::getEnvironment();
    echo "OK (environnement: $env)\n";
    
    echo "8. Test SensorReadRepository: ";
    $sensorReadRepo = new App\Repository\SensorReadRepository($pdo);
    echo "OK\n";
    
    echo "9. Test getFirmwareVersion(): ";
    $firmwareVersion = $sensorReadRepo->getFirmwareVersion();
    echo "OK (version: $firmwareVersion)\n";
    
    echo "10. Test TemplateRenderer: ";
    $renderer = new App\Service\TemplateRenderer();
    echo "OK\n";
    
    echo "\n✅ TOUS LES COMPOSANTS FONCTIONNENT\n";
    echo "Le problème 500 ne vient pas des composants de base.\n";
    echo "Il doit venir du code des contrôleurs ou de la configuration Slim.\n";
    
} catch (Throwable $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
?>
