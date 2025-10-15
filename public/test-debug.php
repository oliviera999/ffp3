<?php
/**
 * Script de diagnostic - nom de fichier différent pour contourner les redirections
 * URL: https://iot.olution.info/ffp3/public/test-debug.php
 */

// Désactiver l'affichage d'erreurs pour éviter les conflits
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Diagnostic FFP3 - " . date('Y-m-d H:i:s') . "</h1>";

try {
    echo "<h2>1. Test de l'environnement</h2>";
    
    // Charger l'autoloader
    require __DIR__ . '/../vendor/autoload.php';
    echo "✅ Autoloader chargé<br>";
    
    // Charger l'environnement
    App\Config\Env::load();
    echo "✅ Environnement chargé<br>";
    
    // Test de la connexion DB
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    echo "✅ Connexion DB réussie<br>";
    
    echo "<h2>2. Test du container DI</h2>";
    
    // Test du container
    $container = require __DIR__ . '/../config/container.php';
    echo "✅ Container DI créé<br>";
    
    echo "<h2>3. Test des services critiques</h2>";
    
    // Test OutputService
    try {
        $outputService = $container->get(\App\Service\OutputService::class);
        $outputs = $outputService->getAllOutputs();
        echo "✅ OutputService: OK (" . count($outputs) . " outputs)<br>";
    } catch (\Throwable $e) {
        echo "❌ OutputService: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
    }
    
    // Test RealtimeDataService
    try {
        $realtimeService = $container->get(\App\Service\RealtimeDataService::class);
        $data = $realtimeService->getLatestReadings();
        echo "✅ RealtimeDataService: OK<br>";
    } catch (\Throwable $e) {
        echo "❌ RealtimeDataService: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
    }
    
    // Test TemplateRenderer
    try {
        $renderer = $container->get(\App\Service\TemplateRenderer::class);
        echo "✅ TemplateRenderer: OK<br>";
    } catch (\Throwable $e) {
        echo "❌ TemplateRenderer: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
    }
    
    echo "<h2>4. Test des contrôleurs</h2>";
    
    // Test OutputController
    try {
        $outputController = $container->get(\App\Controller\OutputController::class);
        echo "✅ OutputController: OK<br>";
        
        // Test des méthodes
        $outputs = $outputController->outputService->getAllOutputs();
        echo "  └─ getAllOutputs(): OK (" . count($outputs) . " outputs)<br>";
        
        $boards = $outputController->outputService->getActiveBoardsForCurrentEnvironment();
        echo "  └─ getActiveBoardsForCurrentEnvironment(): OK (" . count($boards) . " boards)<br>";
        
    } catch (\Throwable $e) {
        echo "❌ OutputController: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    // Test RealtimeApiController
    try {
        $realtimeController = $container->get(\App\Controller\RealtimeApiController::class);
        echo "✅ RealtimeApiController: OK<br>";
        
        // Test des méthodes
        $data = $realtimeController->realtimeService->getLatestReadings();
        echo "  └─ getLatestReadings(): OK<br>";
        
        $outputsState = $realtimeController->realtimeService->getOutputsState();
        echo "  └─ getOutputsState(): OK<br>";
        
    } catch (\Throwable $e) {
        echo "❌ RealtimeApiController: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    echo "<h2>5. Test des templates</h2>";
    
    // Vérifier l'existence des templates
    $templates = [
        'control.twig',
        'aquaponie.twig',
        'dashboard.twig',
        'home.twig',
        'tide_stats.twig'
    ];
    
    foreach ($templates as $template) {
        $path = __DIR__ . '/../templates/' . $template;
        if (file_exists($path)) {
            echo "✅ $template: existe<br>";
        } else {
            echo "❌ $template: manquant<br>";
        }
    }
    
    echo "<h2>6. Test de rendu de template</h2>";
    
    try {
        $renderer = $container->get(\App\Service\TemplateRenderer::class);
        $html = $renderer->render('home.twig', ['page_title' => 'Test', 'active_page' => 'home']);
        echo "✅ Rendu template home.twig: OK<br>";
    } catch (\Throwable $e) {
        echo "❌ Rendu template: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
    }
    
    echo "<h2>7. Test de simulation des contrôleurs</h2>";
    
    // Simuler l'appel à OutputController::showInterface
    try {
        $outputController = $container->get(\App\Controller\OutputController::class);
        
        // Créer des objets Request et Response mock
        $request = new \Slim\Psr7\Factory\ServerRequestFactory();
        $response = new \Slim\Psr7\Factory\ResponseFactory();
        
        $mockRequest = $request->createServerRequest('GET', '/control');
        $mockResponse = $response->createResponse();
        
        echo "✅ Objets Request/Response créés<br>";
        
        // Tenter d'appeler la méthode
        $result = $outputController->showInterface($mockRequest, $mockResponse);
        echo "✅ OutputController::showInterface() exécuté avec succès<br>";
        
    } catch (\Throwable $e) {
        echo "❌ Erreur dans OutputController::showInterface(): " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    // Simuler l'appel à RealtimeApiController::getLatestSensors
    try {
        $realtimeController = $container->get(\App\Controller\RealtimeApiController::class);
        
        $request = new \Slim\Psr7\Factory\ServerRequestFactory();
        $response = new \Slim\Psr7\Factory\ResponseFactory();
        
        $mockRequest = $request->createServerRequest('GET', '/api/realtime/sensors/latest');
        $mockResponse = $response->createResponse();
        
        $result = $realtimeController->getLatestSensors($mockRequest, $mockResponse);
        echo "✅ RealtimeApiController::getLatestSensors() exécuté avec succès<br>";
        
    } catch (\Throwable $e) {
        echo "❌ Erreur dans RealtimeApiController::getLatestSensors(): " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    echo "<h2>✅ Diagnostic terminé</h2>";
    echo "<p>Si tous les tests passent, le problème 500 vient probablement du routage Slim ou des middlewares.</p>";
    
} catch (\Throwable $e) {
    echo "❌ ERREUR GÉNÉRALE: " . $e->getMessage() . "<br>";
    echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
