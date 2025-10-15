<?php
/**
 * Endpoint de test simple pour Slim Framework
 * Teste les composants un par un pour isoler l'erreur 500
 */

// Charger l'autoloader
require_once '../vendor/autoload.php';

echo "=== TEST ENDPOINT SIMPLE ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Test 1: Créer une app Slim basique
    echo "1. Création app Slim basique: ";
    $app = Slim\Factory\AppFactory::create();
    echo "OK\n";
    
    // Test 2: Ajouter une route simple
    echo "2. Ajout route simple: ";
    $app->get('/test-simple', function ($request, $response) {
        $response->getBody()->write('Test simple OK');
        return $response;
    });
    echo "OK\n";
    
    // Test 3: Charger .env
    echo "3. Chargement .env: ";
    App\Config\Env::load();
    echo "OK\n";
    
    // Test 4: Connexion DB
    echo "4. Connexion DB: ";
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    echo "OK\n";
    
    // Test 5: Repository OutputRepository
    echo "5. Test OutputRepository: ";
    $outputRepo = new App\Repository\OutputRepository($pdo);
    echo "OK\n";
    
    // Test 6: Repository BoardRepository
    echo "6. Test BoardRepository: ";
    $boardRepo = new App\Repository\BoardRepository($pdo);
    echo "OK\n";
    
    // Test 7: Service OutputService
    echo "7. Test OutputService: ";
    $outputService = new App\Service\OutputService($outputRepo, $boardRepo);
    echo "OK\n";
    
    // Test 8: Méthode getAllOutputs
    echo "8. Test getAllOutputs(): ";
    $outputs = $outputService->getAllOutputs();
    echo "OK (" . count($outputs) . " outputs)\n";
    
    // Test 9: Méthode getActiveBoardsForCurrentEnvironment
    echo "9. Test getActiveBoardsForCurrentEnvironment(): ";
    $boards = $outputService->getActiveBoardsForCurrentEnvironment();
    echo "OK (" . count($boards) . " boards)\n";
    
    // Test 10: SensorReadRepository
    echo "10. Test SensorReadRepository: ";
    $sensorReadRepo = new App\Repository\SensorReadRepository($pdo);
    echo "OK\n";
    
    // Test 11: Méthode getFirmwareVersion
    echo "11. Test getFirmwareVersion(): ";
    $firmwareVersion = $sensorReadRepo->getFirmwareVersion();
    echo "OK (version: $firmwareVersion)\n";
    
    // Test 12: TemplateRenderer
    echo "12. Test TemplateRenderer: ";
    $renderer = new App\Service\TemplateRenderer();
    echo "OK\n";
    
    // Test 13: TableConfig
    echo "13. Test TableConfig::getEnvironment(): ";
    $env = App\Config\TableConfig::getEnvironment();
    echo "OK (environnement: $env)\n";
    
    echo "\n✅ TOUS LES COMPOSANTS FONCTIONNENT\n";
    echo "Le problème 500 ne vient pas des composants individuels.\n";
    echo "Il doit venir de la configuration ou du code des contrôleurs.\n";
    
} catch (Throwable $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
?>
