<?php
/**
 * Test simple de l'endpoint control-test
 * Contourne le problème des dépendances PHP-DI
 */

// Charger l'autoloader
require_once '../vendor/autoload.php';

// Charger .env
App\Config\Env::load();

try {
    // Créer PDO
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    
    // Créer les repositories
    $outputRepo = new App\Repository\OutputRepository($pdo);
    $boardRepo = new App\Repository\BoardRepository($pdo);
    $sensorReadRepo = new App\Repository\SensorReadRepository($pdo);
    
    // Créer les services
    $outputService = new App\Service\OutputService($outputRepo, $boardRepo);
    $renderer = new App\Service\TemplateRenderer();
    
    // Définir l'environnement test
    $_ENV['ENV'] = 'test';
    
    // Récupérer les données
    $outputs = $outputService->getAllOutputs();
    $boards = $outputService->getActiveBoardsForCurrentEnvironment();
    $environment = App\Config\TableConfig::getEnvironment();
    $firmwareVersion = $sensorReadRepo->getFirmwareVersion();
    $version = App\Config\Version::getWithPrefix();
    
    // Préparer les données pour le template
    $data = [
        'outputs' => $outputs,
        'boards' => $boards,
        'title' => 'Contrôle du ffp3 (TEST)',
        'environment' => $environment,
        'version' => $version,
        'firmware_version' => $firmwareVersion,
    ];
    
    // Rendre le template
    $html = $renderer->render('control.twig', $data);
    
    // Retourner la réponse
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    
} catch (Throwable $e) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>Erreur</h1>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Fichier:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "<h2>Trace</h2>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
