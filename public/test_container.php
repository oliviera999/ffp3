<?php
/**
 * Test direct du container PHP-DI
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Test du container PHP-DI</h1>";

try {
    require __DIR__ . '/../vendor/autoload.php';
    
    // Charger l'environnement
    App\Config\Env::load();
    
    echo "<h2>1. Test création du container</h2>";
    
    $containerBuilder = new DI\ContainerBuilder();
    $containerBuilder->useAutowiring(false);
    $containerBuilder->useAnnotations(false);
    
    // Charger les dépendances
    $definitions = require __DIR__ . '/../config/dependencies.php';
    $containerBuilder->addDefinitions($definitions);
    
    $container = $containerBuilder->build();
    echo "✅ Container PHP-DI créé avec succès<br>";
    
    echo "<h2>2. Test des services individuels</h2>";
    
    try {
        $sensorRepo = $container->get(\App\Repository\SensorReadRepository::class);
        echo "✅ SensorReadRepository récupéré du container<br>";
    } catch (\Throwable $e) {
        echo "❌ Erreur SensorReadRepository: " . $e->getMessage() . "<br>";
    }
    
    try {
        $outputRepo = $container->get(\App\Repository\OutputRepository::class);
        echo "✅ OutputRepository récupéré du container<br>";
    } catch (\Throwable $e) {
        echo "❌ Erreur OutputRepository: " . $e->getMessage() . "<br>";
    }
    
    try {
        $boardRepo = $container->get(\App\Repository\BoardRepository::class);
        echo "✅ BoardRepository récupéré du container<br>";
    } catch (\Throwable $e) {
        echo "❌ Erreur BoardRepository: " . $e->getMessage() . "<br>";
    }
    
    try {
        $outputService = $container->get(\App\Service\OutputService::class);
        echo "✅ OutputService récupéré du container<br>";
    } catch (\Throwable $e) {
        echo "❌ Erreur OutputService: " . $e->getMessage() . "<br>";
    }
    
    try {
        $templateRenderer = $container->get(\App\Service\TemplateRenderer::class);
        echo "✅ TemplateRenderer récupéré du container<br>";
    } catch (\Throwable $e) {
        echo "❌ Erreur TemplateRenderer: " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>3. Test des contrôleurs</h2>";
    
    try {
        $outputController = $container->get(\App\Controller\OutputController::class);
        echo "✅ OutputController récupéré du container<br>";
    } catch (\Throwable $e) {
        echo "❌ Erreur OutputController: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    try {
        $aquaponieController = $container->get(\App\Controller\AquaponieController::class);
        echo "✅ AquaponieController récupéré du container<br>";
    } catch (\Throwable $e) {
        echo "❌ Erreur AquaponieController: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    try {
        $realtimeController = $container->get(\App\Controller\RealtimeApiController::class);
        echo "✅ RealtimeApiController récupéré du container<br>";
    } catch (\Throwable $e) {
        echo "❌ Erreur RealtimeApiController: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    echo "<h2>✅ Tests du container terminés</h2>";
    
} catch (\Throwable $e) {
    echo "❌ Erreur générale: " . $e->getMessage() . "<br>";
    echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
