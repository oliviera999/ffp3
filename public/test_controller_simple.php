<?php
/**
 * Test simple du contrôleur OutputController
 * Pour isoler l'erreur 500 sans attendre le déploiement
 */

echo "=== TEST CONTROLLER SIMPLE ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Test 1: Charger l'autoloader
    echo "1. Chargement autoloader: ";
    require_once '../vendor/autoload.php';
    echo "OK\n";
    
    // Test 2: Charger .env
    echo "2. Chargement .env: ";
    App\Config\Env::load();
    echo "OK\n";
    
    // Test 3: Créer PDO
    echo "3. Création PDO: ";
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    echo "OK\n";
    
    // Test 4: Créer OutputRepository
    echo "4. Création OutputRepository: ";
    $outputRepo = new App\Repository\OutputRepository($pdo);
    echo "OK\n";
    
    // Test 5: Créer BoardRepository
    echo "5. Création BoardRepository: ";
    $boardRepo = new App\Repository\BoardRepository($pdo);
    echo "OK\n";
    
    // Test 6: Créer OutputService
    echo "6. Création OutputService: ";
    $outputService = new App\Service\OutputService($outputRepo, $boardRepo);
    echo "OK\n";
    
    // Test 7: Créer TemplateRenderer
    echo "7. Création TemplateRenderer: ";
    $renderer = new App\Service\TemplateRenderer();
    echo "OK\n";
    
    // Test 8: Créer SensorReadRepository
    echo "8. Création SensorReadRepository: ";
    $sensorReadRepo = new App\Repository\SensorReadRepository($pdo);
    echo "OK\n";
    
    // Test 9: Créer OutputController
    echo "9. Création OutputController: ";
    $controller = new App\Controller\OutputController($outputService, $renderer, $sensorReadRepo);
    echo "OK\n";
    
    // Test 10: Tester la méthode getAllOutputs
    echo "10. Test getAllOutputs(): ";
    $outputs = $outputService->getAllOutputs();
    echo "OK (" . count($outputs) . " outputs)\n";
    
    // Test 11: Tester la méthode getActiveBoardsForCurrentEnvironment
    echo "11. Test getActiveBoardsForCurrentEnvironment(): ";
    $boards = $outputService->getActiveBoardsForCurrentEnvironment();
    echo "OK (" . count($boards) . " boards)\n";
    
    // Test 12: Tester getFirmwareVersion
    echo "12. Test getFirmwareVersion(): ";
    $firmwareVersion = $sensorReadRepo->getFirmwareVersion();
    echo "OK (version: $firmwareVersion)\n";
    
    // Test 13: Tester TableConfig::getEnvironment
    echo "13. Test TableConfig::getEnvironment(): ";
    $environment = App\Config\TableConfig::getEnvironment();
    echo "OK (environnement: $environment)\n";
    
    // Test 14: Tester Version::getWithPrefix
    echo "14. Test Version::getWithPrefix(): ";
    $version = App\Config\Version::getWithPrefix();
    echo "OK (version: $version)\n";
    
    // Test 15: Tester le rendu du template
    echo "15. Test rendu template: ";
    $data = [
        'outputs' => $outputs,
        'boards' => $boards,
        'title' => 'Test',
        'environment' => $environment,
        'version' => $version,
        'firmware_version' => $firmwareVersion,
    ];
    $html = $renderer->render('control.twig', $data);
    echo "OK (" . strlen($html) . " caractères)\n";
    
    echo "\n✅ TOUS LES TESTS RÉUSSIS\n";
    echo "Le problème ne vient pas des composants individuels.\n";
    echo "Il doit venir de la configuration Slim ou du routing.\n";
    
} catch (Throwable $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
?>
