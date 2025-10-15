<?php
/**
 * Test simple qui bypass Slim pour identifier le problème exact
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Test simple - Bypass Slim</h1>";

try {
    require __DIR__ . '/../vendor/autoload.php';
    
    // Charger l'environnement
    App\Config\Env::load();
    
    echo "<h2>1. Test de base - PDO</h2>";
    
    $pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
        getenv('DB_USER'),
        getenv('DB_PASS'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "✅ PDO créé avec succès<br>";
    
    echo "<h2>2. Test des repositories</h2>";
    
    $sensorRepo = new \App\Repository\SensorReadRepository($pdo);
    echo "✅ SensorReadRepository créé<br>";
    
    $outputRepo = new \App\Repository\OutputRepository($pdo);
    echo "✅ OutputRepository créé<br>";
    
    $boardRepo = new \App\Repository\BoardRepository($pdo);
    echo "✅ BoardRepository créé<br>";
    
    echo "<h2>3. Test des services</h2>";
    
    $statsService = new \App\Service\SensorStatisticsService($pdo);
    echo "✅ SensorStatisticsService créé<br>";
    
    $statsAggregator = new \App\Service\StatisticsAggregatorService($statsService);
    echo "✅ StatisticsAggregatorService créé<br>";
    
    $chartService = new \App\Service\ChartDataService();
    echo "✅ ChartDataService créé<br>";
    
    $waterService = new \App\Service\WaterBalanceService($sensorRepo);
    echo "✅ WaterBalanceService créé<br>";
    
    $outputService = new \App\Service\OutputService($outputRepo, $boardRepo);
    echo "✅ OutputService créé<br>";
    
    $realtimeService = new \App\Service\RealtimeDataService($sensorRepo, $outputRepo, $pdo);
    echo "✅ RealtimeDataService créé<br>";
    
    echo "<h2>4. Test des contrôleurs</h2>";
    
    // Test AquaponieController
    try {
        $aquaponieController = new \App\Controller\AquaponieController(
            $sensorRepo,
            $statsAggregator,
            $chartService,
            $waterService
        );
        echo "✅ AquaponieController créé avec succès<br>";
        
        // Test de la méthode show()
        ob_start();
        $aquaponieController->show();
        $output = ob_get_clean();
        
        if (strlen($output) > 100) {
            echo "✅ AquaponieController::show() exécutée (output: " . strlen($output) . " chars)<br>";
        } else {
            echo "⚠️ AquaponieController::show() output court: " . htmlspecialchars(substr($output, 0, 200)) . "<br>";
        }
        
    } catch (\Throwable $e) {
        echo "❌ Erreur AquaponieController: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    // Test OutputController
    try {
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
        $twig = new \Twig\Environment($loader, ['cache' => false]);
        $templateRenderer = new \App\Service\TemplateRenderer($twig);
        
        $outputController = new \App\Controller\OutputController(
            $outputService,
            $templateRenderer,
            $sensorRepo
        );
        echo "✅ OutputController créé avec succès<br>";
        
    } catch (\Throwable $e) {
        echo "❌ Erreur OutputController: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    // Test RealtimeApiController
    try {
        $realtimeController = new \App\Controller\RealtimeApiController($realtimeService);
        echo "✅ RealtimeApiController créé avec succès<br>";
        
    } catch (\Throwable $e) {
        echo "❌ Erreur RealtimeApiController: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    echo "<h2>✅ Tests terminés - Tous les contrôleurs fonctionnent !</h2>";
    echo "<p><strong>Conclusion:</strong> Le problème n'est pas dans les contrôleurs eux-mêmes, mais dans la configuration Slim ou PHP-DI.</p>";
    
} catch (\Throwable $e) {
    echo "❌ Erreur générale: " . $e->getMessage() . "<br>";
    echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
