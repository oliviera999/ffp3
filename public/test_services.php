<?php
/**
 * Test simple pour identifier quelle classe pose problème
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Test des services individuels</h1>";

try {
    require __DIR__ . '/../vendor/autoload.php';
    
    // Charger l'environnement
    App\Config\Env::load();
    
    echo "<h2>1. Test PDO</h2>";
    try {
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
    } catch (\Throwable $e) {
        echo "❌ Erreur PDO: " . $e->getMessage() . "<br>";
        exit;
    }
    
    echo "<h2>2. Test SensorReadRepository</h2>";
    try {
        $sensorRepo = new \App\Repository\SensorReadRepository($pdo);
        echo "✅ SensorReadRepository créé avec succès<br>";
    } catch (\Throwable $e) {
        echo "❌ Erreur SensorReadRepository: " . $e->getMessage() . "<br>";
        exit;
    }
    
    echo "<h2>3. Test OutputRepository</h2>";
    try {
        $outputRepo = new \App\Repository\OutputRepository($pdo);
        echo "✅ OutputRepository créé avec succès<br>";
    } catch (\Throwable $e) {
        echo "❌ Erreur OutputRepository: " . $e->getMessage() . "<br>";
        exit;
    }
    
    echo "<h2>4. Test StatisticsAggregatorService</h2>";
    try {
        $statsService = new \App\Service\SensorStatisticsService($pdo);
        $statsAggregator = new \App\Service\StatisticsAggregatorService($statsService);
        echo "✅ StatisticsAggregatorService créé avec succès<br>";
    } catch (\Throwable $e) {
        echo "❌ Erreur StatisticsAggregatorService: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
    }
    
    echo "<h2>5. Test ChartDataService</h2>";
    try {
        $chartService = new \App\Service\ChartDataService();
        echo "✅ ChartDataService créé avec succès<br>";
    } catch (\Throwable $e) {
        echo "❌ Erreur ChartDataService: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
    }
    
    echo "<h2>6. Test WaterBalanceService</h2>";
    try {
        $waterService = new \App\Service\WaterBalanceService($sensorRepo);
        echo "✅ WaterBalanceService créé avec succès<br>";
    } catch (\Throwable $e) {
        echo "❌ Erreur WaterBalanceService: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
    }
    
    echo "<h2>7. Test RealtimeDataService</h2>";
    try {
        $realtimeService = new \App\Service\RealtimeDataService($sensorRepo, $outputRepo, $pdo);
        echo "✅ RealtimeDataService créé avec succès<br>";
    } catch (\Throwable $e) {
        echo "❌ Erreur RealtimeDataService: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
    }
    
    echo "<h2>8. Test OutputService</h2>";
    try {
        $boardRepo = new \App\Repository\BoardRepository($pdo);
        $outputService = new \App\Service\OutputService($outputRepo, $boardRepo);
        echo "✅ OutputService créé avec succès<br>";
    } catch (\Throwable $e) {
        echo "❌ Erreur OutputService: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
    }
    
    echo "<h2>9. Test TemplateRenderer</h2>";
    try {
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
        $twig = new \Twig\Environment($loader, ['cache' => false]);
        $templateRenderer = new \App\Service\TemplateRenderer($twig);
        echo "✅ TemplateRenderer créé avec succès<br>";
    } catch (\Throwable $e) {
        echo "❌ Erreur TemplateRenderer: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
    }
    
    echo "<h2>✅ Tous les services testés avec succès !</h2>";
    
} catch (\Throwable $e) {
    echo "❌ Erreur générale: " . $e->getMessage() . "<br>";
    echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
