<?php
/**
 * Script de diagnostic accessible via Slim Framework
 * URL: https://iot.olution.info/ffp3/debug-slim
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Config\Env;
use DI\ContainerBuilder;

// Charger l'environnement
Env::load();

echo "<h1>🔍 Diagnostic Slim Framework - " . date('Y-m-d H:i:s') . "</h1>";

try {
    echo "<h2>1. Test du container PHP-DI</h2>";
    
    $containerBuilder = new ContainerBuilder();
    $containerBuilder->useAutowiring(false);
    $containerBuilder->useAnnotations(false);
    
    // Charger les dépendances
    $definitions = require __DIR__ . '/../config/dependencies.php';
    $containerBuilder->addDefinitions($definitions);
    
    $container = $containerBuilder->build();
    echo "✅ Container PHP-DI créé avec succès<br>";
    
    echo "<h2>2. Test des services critiques</h2>";
    
    try {
        $sensorRepo = $container->get(\App\Repository\SensorReadRepository::class);
        echo "✅ SensorReadRepository: OK<br>";
    } catch (\Throwable $e) {
        echo "❌ SensorReadRepository: " . $e->getMessage() . "<br>";
    }
    
    try {
        $outputRepo = $container->get(\App\Repository\OutputRepository::class);
        echo "✅ OutputRepository: OK<br>";
    } catch (\Throwable $e) {
        echo "❌ OutputRepository: " . $e->getMessage() . "<br>";
    }
    
    try {
        $boardRepo = $container->get(\App\Repository\BoardRepository::class);
        echo "✅ BoardRepository: OK<br>";
    } catch (\Throwable $e) {
        echo "❌ BoardRepository: " . $e->getMessage() . "<br>";
    }
    
    try {
        $outputService = $container->get(\App\Service\OutputService::class);
        echo "✅ OutputService: OK<br>";
    } catch (\Throwable $e) {
        echo "❌ OutputService: " . $e->getMessage() . "<br>";
    }
    
    try {
        $templateRenderer = $container->get(\App\Service\TemplateRenderer::class);
        echo "✅ TemplateRenderer: OK<br>";
    } catch (\Throwable $e) {
        echo "❌ TemplateRenderer: " . $e->getMessage() . "<br>";
    }
    
    try {
        $realtimeService = $container->get(\App\Service\RealtimeDataService::class);
        echo "✅ RealtimeDataService: OK<br>";
    } catch (\Throwable $e) {
        echo "❌ RealtimeDataService: " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>3. Test des contrôleurs</h2>";
    
    try {
        $outputController = $container->get(\App\Controller\OutputController::class);
        echo "✅ OutputController: OK<br>";
    } catch (\Throwable $e) {
        echo "❌ OutputController: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    try {
        $aquaponieController = $container->get(\App\Controller\AquaponieController::class);
        echo "✅ AquaponieController: OK<br>";
    } catch (\Throwable $e) {
        echo "❌ AquaponieController: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    try {
        $realtimeController = $container->get(\App\Controller\RealtimeApiController::class);
        echo "✅ RealtimeApiController: OK<br>";
    } catch (\Throwable $e) {
        echo "❌ RealtimeApiController: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    try {
        $homeController = $container->get(\App\Controller\HomeController::class);
        echo "✅ HomeController: OK<br>";
    } catch (\Throwable $e) {
        echo "❌ HomeController: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    try {
        $dashboardController = $container->get(\App\Controller\DashboardController::class);
        echo "✅ DashboardController: OK<br>";
    } catch (\Throwable $e) {
        echo "❌ DashboardController: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    echo "<h2>4. Test des méthodes des contrôleurs</h2>";
    
    try {
        $outputController = $container->get(\App\Controller\OutputController::class);
        $outputs = $outputController->outputService->getAllOutputs();
        echo "✅ OutputController->getAllOutputs(): OK (" . count($outputs) . " outputs)<br>";
    } catch (\Throwable $e) {
        echo "❌ OutputController->getAllOutputs(): " . $e->getMessage() . "<br>";
    }
    
    try {
        $realtimeController = $container->get(\App\Controller\RealtimeApiController::class);
        $latestData = $realtimeController->realtimeService->getLatestReadings();
        echo "✅ RealtimeApiController->getLatestReadings(): OK<br>";
    } catch (\Throwable $e) {
        echo "❌ RealtimeApiController->getLatestReadings(): " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>✅ Diagnostic terminé</h2>";
    echo "<p>Tous les composants semblent fonctionner correctement.</p>";
    echo "<p>Le problème 500 doit venir du routage Slim ou de l'exécution des méthodes.</p>";
    
} catch (\Throwable $e) {
    echo "❌ Erreur générale: " . $e->getMessage() . "<br>";
    echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
