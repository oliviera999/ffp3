<?php
/**
 * Script de diagnostic - nom de fichier complètement différent
 * URL: https://iot.olution.info/ffp3/public/test123.php
 */

// Désactiver l'affichage d'erreurs pour éviter les conflits
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Diagnostic Test123 - " . date('Y-m-d H:i:s') . "</h1>";

try {
    echo "<h2>1. Test de l'environnement</h2>";
    
    // Charger l'autoloader
    require __DIR__ . '/../vendor/autoload.php';
    echo "✅ Autoloader chargé<br>";
    
    // Charger l'environnement
    \App\Config\Env::load();
    echo "✅ Environnement chargé<br>";
    echo "ENV: " . ($_ENV['ENV'] ?? 'non défini') . "<br>";
    
    // Test de la connexion DB
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    echo "✅ Connexion DB réussie<br>";
    
    echo "<h2>2. Test de TableConfig</h2>";
    
    // Test TableConfig
    $dataTable = \App\Config\TableConfig::getDataTable();
    echo "✅ Data Table: $dataTable<br>";
    
    $outputsTable = \App\Config\TableConfig::getOutputsTable();
    echo "✅ Outputs Table: $outputsTable<br>";
    
    echo "<h2>3. Test des repositories</h2>";
    
    // Test SensorReadRepository
    $sensorRepo = new \App\Repository\SensorReadRepository($pdo);
    $lastReadings = $sensorRepo->getLastReadings();
    echo "✅ SensorReadRepository: " . (is_array($lastReadings) ? "OK" : "ERREUR") . "<br>";
    
    // Test OutputRepository
    $outputRepo = new \App\Repository\OutputRepository($pdo);
    $outputs = $outputRepo->findAll();
    echo "✅ OutputRepository: " . count($outputs) . " outputs trouvés<br>";
    
    // Test BoardRepository
    $boardRepo = new \App\Repository\BoardRepository($pdo);
    $boards = $boardRepo->findActiveForEnvironment($outputsTable);
    echo "✅ BoardRepository: " . count($boards) . " boards trouvés<br>";
    
    echo "<h2>4. Test des services</h2>";
    
    // Test RealtimeDataService
    $realtimeService = new \App\Service\RealtimeDataService($sensorRepo, $outputRepo, $pdo);
    $latestReadings = $realtimeService->getLatestReadings();
    echo "✅ RealtimeDataService::getLatestReadings: OK<br>";
    
    $outputsState = $realtimeService->getOutputsState();
    echo "✅ RealtimeDataService::getOutputsState: " . count($outputsState) . " outputs<br>";
    
    $systemHealth = $realtimeService->getSystemHealth();
    echo "✅ RealtimeDataService::getSystemHealth: " . ($systemHealth['online'] ? 'Online' : 'Offline') . "<br>";
    
    // Test OutputService
    $outputService = new \App\Service\OutputService($outputRepo, $boardRepo);
    $allOutputs = $outputService->getAllOutputs();
    echo "✅ OutputService::getAllOutputs: " . count($allOutputs) . " outputs<br>";
    
    $activeBoards = $outputService->getActiveBoardsForCurrentEnvironment();
    echo "✅ OutputService::getActiveBoardsForCurrentEnvironment: " . count($activeBoards) . " boards<br>";
    
    echo "<h2>5. Test du container DI</h2>";
    
    // Test du container
    $container = require __DIR__ . '/../config/container.php';
    echo "✅ Container DI créé<br>";
    
    // Test des contrôleurs
    $outputController = $container->get(\App\Controller\OutputController::class);
    echo "✅ OutputController résolu<br>";
    
    $realtimeController = $container->get(\App\Controller\RealtimeApiController::class);
    echo "✅ RealtimeApiController résolu<br>";
    
    echo "<h2>6. Test de simulation des contrôleurs</h2>";
    
    // Simuler l'appel à OutputController::showInterface
    try {
        $request = new \Slim\Psr7\Factory\ServerRequestFactory();
        $response = new \Slim\Psr7\Factory\ResponseFactory();
        
        $mockRequest = $request->createServerRequest('GET', '/control');
        $mockResponse = $response->createResponse();
        
        echo "✅ Objets Request/Response créés<br>";
        
        // Tenter d'appeler la méthode
        $result = $outputController->showInterface($mockRequest, $mockResponse);
        echo "✅ OutputController::showInterface() exécuté avec succès<br>";
        echo "Status Code: " . $result->getStatusCode() . "<br>";
        
    } catch (\Throwable $e) {
        echo "❌ Erreur dans OutputController::showInterface(): " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    // Simuler l'appel à RealtimeApiController::getLatestSensors
    try {
        $request = new \Slim\Psr7\Factory\ServerRequestFactory();
        $response = new \Slim\Psr7\Factory\ResponseFactory();
        
        $mockRequest = $request->createServerRequest('GET', '/api/realtime/sensors/latest');
        $mockResponse = $response->createResponse();
        
        $result = $realtimeController->getLatestSensors($mockRequest, $mockResponse);
        echo "✅ RealtimeApiController::getLatestSensors() exécuté avec succès<br>";
        echo "Status Code: " . $result->getStatusCode() . "<br>";
        
    } catch (\Throwable $e) {
        echo "❌ Erreur dans RealtimeApiController::getLatestSensors(): " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    echo "<h2>✅ Diagnostic terminé</h2>";
    echo "<p>Si tous les tests passent, le problème vient probablement du routage Slim ou des middlewares.</p>";
    
} catch (\Throwable $e) {
    echo "❌ ERREUR GÉNÉRALE: " . $e->getMessage() . "<br>";
    echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
