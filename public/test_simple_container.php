<?php
/**
 * Test simple du container PHP-DI
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Test simple du container PHP-DI</h1>";

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
    
    echo "<h2>2. Test OutputController</h2>";
    
    try {
        $outputController = $container->get(\App\Controller\OutputController::class);
        echo "✅ OutputController récupéré du container<br>";
    } catch (\Throwable $e) {
        echo "❌ Erreur OutputController: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    echo "<h2>3. Test AquaponieController</h2>";
    
    try {
        $aquaponieController = $container->get(\App\Controller\AquaponieController::class);
        echo "✅ AquaponieController récupéré du container<br>";
    } catch (\Throwable $e) {
        echo "❌ Erreur AquaponieController: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    echo "<h2>4. Test RealtimeApiController</h2>";
    
    try {
        $realtimeController = $container->get(\App\Controller\RealtimeApiController::class);
        echo "✅ RealtimeApiController récupéré du container<br>";
    } catch (\Throwable $e) {
        echo "❌ Erreur RealtimeApiController: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    echo "<h2>✅ Tests terminés</h2>";
    
} catch (\Throwable $e) {
    echo "❌ Erreur générale: " . $e->getMessage() . "<br>";
    echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
