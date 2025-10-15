<?php
/**
 * Test direct des contrôleurs sans passer par Slim
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Test direct des contrôleurs</h1>";

try {
    require __DIR__ . '/../vendor/autoload.php';
    
    // Charger l'environnement
    App\Config\Env::load();
    
    echo "<h2>1. Test AquaponieController</h2>";
    
    try {
        // Créer les dépendances manuellement
        $pdo = new PDO(
            'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
            getenv('DB_USER'),
            getenv('DB_PASS'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        
        $sensorRepo = new \App\Repository\SensorReadRepository($pdo);
        $statsService = new \App\Service\SensorStatisticsService($pdo);
        $statsAggregator = new \App\Service\StatisticsAggregatorService($statsService);
        $chartService = new \App\Service\ChartDataService();
        $waterService = new \App\Service\WaterBalanceService($sensorRepo);
        
        $controller = new \App\Controller\AquaponieController(
            $sensorRepo,
            $statsAggregator,
            $chartService,
            $waterService
        );
        
        echo "✅ AquaponieController créé avec succès<br>";
        
        // Tester l'appel de méthode
        ob_start();
        $controller->show();
        $output = ob_get_clean();
        
        if (strlen($output) > 100) {
            echo "✅ Méthode show() exécutée avec succès (output: " . strlen($output) . " chars)<br>";
        } else {
            echo "⚠️ Méthode show() exécutée mais output court: " . htmlspecialchars($output) . "<br>";
        }
        
    } catch (\Throwable $e) {
        echo "❌ Erreur AquaponieController: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    echo "<h2>2. Test OutputController</h2>";
    
    try {
        $outputRepo = new \App\Repository\OutputRepository($pdo);
        $boardRepo = new \App\Repository\BoardRepository($pdo);
        $outputService = new \App\Service\OutputService($outputRepo, $boardRepo);
        
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
        $twig = new \Twig\Environment($loader, ['cache' => false]);
        $templateRenderer = new \App\Service\TemplateRenderer($twig);
        
        $controller = new \App\Controller\OutputController(
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
    
    echo "<h2>3. Test RealtimeApiController</h2>";
    
    try {
        $realtimeService = new \App\Service\RealtimeDataService($sensorRepo, $outputRepo, $pdo);
        
        $controller = new \App\Controller\RealtimeApiController($realtimeService);
        
        echo "✅ RealtimeApiController créé avec succès<br>";
        
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
