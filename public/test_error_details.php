<?php
/**
 * Test simple pour capturer l'erreur exacte des contrôleurs
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Test des erreurs détaillées</h1>";

try {
    require __DIR__ . '/../vendor/autoload.php';
    
    // Charger l'environnement
    App\Config\Env::load();
    
    echo "<h2>1. Test OutputController</h2>";
    
    try {
        $containerBuilder = new DI\ContainerBuilder();
        $containerBuilder->useAutowiring(false);
        $containerBuilder->useAnnotations(false);
        
        // Définir les dépendances minimales
        $containerBuilder->addDefinitions([
            PDO::class => function () {
                $dbHost = getenv('DB_HOST');
                $dbName = getenv('DB_NAME');
                $dbUser = getenv('DB_USER');
                $dbPass = getenv('DB_PASS');
                $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
                return new PDO($dsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            },
            \App\Repository\OutputRepository::class => function (ContainerInterface $c) {
                return new \App\Repository\OutputRepository($c->get(PDO::class));
            },
            \App\Repository\BoardRepository::class => function (ContainerInterface $c) {
                return new \App\Repository\BoardRepository($c->get(PDO::class));
            },
            \App\Repository\SensorReadRepository::class => function (ContainerInterface $c) {
                return new \App\Repository\SensorReadRepository($c->get(PDO::class));
            },
            \App\Service\OutputService::class => function (ContainerInterface $c) {
                return new \App\Service\OutputService(
                    $c->get(\App\Repository\OutputRepository::class),
                    $c->get(\App\Repository\BoardRepository::class)
                );
            },
            \App\Service\TemplateRenderer::class => function () {
                $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
                $twig = new \Twig\Environment($loader, ['cache' => false]);
                return new \App\Service\TemplateRenderer($twig);
            },
            \App\Controller\OutputController::class => function (ContainerInterface $c) {
                return new \App\Controller\OutputController(
                    $c->get(\App\Service\OutputService::class),
                    $c->get(\App\Service\TemplateRenderer::class),
                    $c->get(\App\Repository\SensorReadRepository::class)
                );
            },
        ]);
        
        $container = $containerBuilder->build();
        $controller = $container->get(\App\Controller\OutputController::class);
        
        echo "✅ OutputController créé avec succès<br>";
        
    } catch (\Throwable $e) {
        echo "❌ Erreur OutputController: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    echo "<h2>2. Test RealtimeApiController</h2>";
    
    try {
        $containerBuilder = new DI\ContainerBuilder();
        $containerBuilder->useAutowiring(false);
        $containerBuilder->useAnnotations(false);
        
        $containerBuilder->addDefinitions([
            PDO::class => function () {
                $dbHost = getenv('DB_HOST');
                $dbName = getenv('DB_NAME');
                $dbUser = getenv('DB_USER');
                $dbPass = getenv('DB_PASS');
                $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
                return new PDO($dsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            },
            \App\Repository\SensorReadRepository::class => function (ContainerInterface $c) {
                return new \App\Repository\SensorReadRepository($c->get(PDO::class));
            },
            \App\Service\RealtimeDataService::class => function (ContainerInterface $c) {
                return new \App\Service\RealtimeDataService(
                    $c->get(\App\Repository\SensorReadRepository::class)
                );
            },
            \App\Controller\RealtimeApiController::class => function (ContainerInterface $c) {
                return new \App\Controller\RealtimeApiController(
                    $c->get(\App\Service\RealtimeDataService::class)
                );
            },
        ]);
        
        $container = $containerBuilder->build();
        $controller = $container->get(\App\Controller\RealtimeApiController::class);
        
        echo "✅ RealtimeApiController créé avec succès<br>";
        
    } catch (\Throwable $e) {
        echo "❌ Erreur RealtimeApiController: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
} catch (\Throwable $e) {
    echo "❌ Erreur générale: " . $e->getMessage() . "<br>";
    echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
