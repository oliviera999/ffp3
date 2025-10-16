<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Config\Env;
use App\Controller\AquaponieController;
use App\Controller\DashboardController;
use App\Controller\ExportController;
use App\Controller\HeartbeatController;
use App\Controller\HomeController;
use App\Controller\OutputController;
use App\Controller\PostDataController;
use App\Controller\RealtimeApiController;
use App\Controller\TideStatsController;
use App\Middleware\EnvironmentMiddleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;

// Charge les variables d'environnement (.env)
Env::load();

// ====================================================================
// Initialisation du container DI
// ====================================================================
$container = require __DIR__ . '/../config/container.php';
AppFactory::setContainer($container);

// ====================================================================
// Création de l'application Slim
// ====================================================================
$app = AppFactory::create();

// Forcer le chemin base pour être identique à l'ancien (dossier parent de /public)
$basePath = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'])));
if ($basePath !== '/' && $basePath !== '') {
    $app->setBasePath($basePath);
}

// ====================================================================
// DEBUG TEMPORAIRE - À SUPPRIMER APRÈS DIAGNOSTIC
// ====================================================================
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../var/log/php_errors.log');

// ====================================================================
// Middleware de gestion d'erreurs personnalisé
// ====================================================================
$app->add(new \App\Middleware\ErrorHandlerMiddleware());

// ====================================================================
// Routes PRODUCTION (par défaut) - avec middleware pour forcer 'prod'
// ====================================================================
$app->group('', function ($group) {
    // Page d'accueil
    $group->get('/', [HomeController::class, 'show']);
    $group->get('/index.html', function (Request $request, Response $response) {
        return $response->withHeader('Location', '/ffp3/')->withStatus(301);
    });

    // Route de diagnostic temporaire
    $group->get('/debug-slim', function (Request $request, Response $response) {
        ob_start();
        include __DIR__ . '/debug_slim.php';
        $content = ob_get_clean();
        $response->getBody()->write($content);
        return $response->withHeader('Content-Type', 'text/html');
    });

    // Dashboard
    $group->get('/dashboard', [DashboardController::class, 'show']);

    // Page aquaponie
    $group->map(['GET', 'POST'], '/aquaponie', [AquaponieController::class, 'show']);
    $group->get('/ffp3-data', function (Request $request, Response $response) {
        return $response->withHeader('Location', '/ffp3/aquaponie')->withStatus(301);
    }); // Redirection legacy vers aquaponie

    // Post data depuis ESP32
    $group->post('/post-data', [PostDataController::class, 'handle']);
    $group->post('/post-ffp3-data.php', [PostDataController::class, 'handle']); // Alias legacy

    // Export CSV
    $group->get('/export-data', [ExportController::class, 'downloadCsv']);
    $group->get('/export-data.php', [ExportController::class, 'downloadCsv']); // Alias legacy

    // Statistiques marées
    $group->map(['GET', 'POST'], '/tide-stats', [TideStatsController::class, 'show']);

    // Interface de contrôle PROD
    $group->get('/control', [OutputController::class, 'showInterface']);
    $group->get('/api/outputs/toggle', [OutputController::class, 'toggleOutput']);
    $group->get('/api/outputs/state', [OutputController::class, 'getOutputsState']);
    $group->post('/api/outputs/parameters', [OutputController::class, 'updateParameters']);

    // ====================================================================
    // API Temps Réel PROD
    // ====================================================================
    $group->get('/api/realtime/sensors/latest', [RealtimeApiController::class, 'getLatestSensors']);
    $group->get('/api/realtime/sensors/since/{timestamp}', [RealtimeApiController::class, 'getSensorsSince']);
    $group->get('/api/realtime/outputs/state', [RealtimeApiController::class, 'getOutputsState']);
    $group->get('/api/realtime/system/health', [RealtimeApiController::class, 'getSystemHealth']);
    $group->get('/api/realtime/alerts/active', [RealtimeApiController::class, 'getActiveAlerts']);
    
    // Alias de compatibilité pour l'ancienne URL
    $group->get('/api/health', [RealtimeApiController::class, 'getSystemHealth']);

    // ====================================================================
    // Heartbeat ESP32 PROD
    // ====================================================================
    $group->post('/heartbeat', [HeartbeatController::class, 'handle']);
    $group->post('/heartbeat.php', function (Request $request, Response $response) {
        return $response->withHeader('Location', '/ffp3/heartbeat')->withStatus(301);
    }); // Redirection legacy vers heartbeat

    // ====================================================================
    // Fichiers statiques PROD (fallback si serveur web ne les sert pas)
    // ====================================================================
    $group->get('/manifest.json', function (Request $request, Response $response) {
        $manifestPath = __DIR__ . '/manifest.json';
        if (file_exists($manifestPath)) {
            $response->getBody()->write(file_get_contents($manifestPath));
            return $response->withHeader('Content-Type', 'application/json');
        }
        return $response->withStatus(404);
    });
})->add(new EnvironmentMiddleware('prod'));

// ====================================================================
// Groupe de routes TEST (avec middleware EnvironmentMiddleware)
// ====================================================================
$app->group('', function ($group) {
    // Dashboard TEST
    $group->get('/dashboard-test', [DashboardController::class, 'show']);
    
    // Page aquaponie TEST
    $group->map(['GET', 'POST'], '/aquaponie-test', [AquaponieController::class, 'show']);
    
    // Post data TEST
    $group->post('/post-data-test', [PostDataController::class, 'handle']);
    
    // Statistiques marées TEST
    $group->map(['GET', 'POST'], '/tide-stats-test', [TideStatsController::class, 'show']);
    
    // Export CSV TEST
    $group->get('/export-data-test', [ExportController::class, 'downloadCsv']);
    
    // Interface de contrôle TEST
    $group->get('/control-test', [OutputController::class, 'showInterface']);
    $group->get('/api/outputs-test/toggle', [OutputController::class, 'toggleOutput']);
    $group->get('/api/outputs-test/state', [OutputController::class, 'getOutputsState']);
    $group->post('/api/outputs-test/parameters', [OutputController::class, 'updateParameters']);
    
    // API Temps Réel TEST
    $group->get('/api/realtime-test/sensors/latest', [RealtimeApiController::class, 'getLatestSensors']);
    $group->get('/api/realtime-test/sensors/since/{timestamp}', [RealtimeApiController::class, 'getSensorsSince']);
    $group->get('/api/realtime-test/outputs/state', [RealtimeApiController::class, 'getOutputsState']);
    $group->get('/api/realtime-test/system/health', [RealtimeApiController::class, 'getSystemHealth']);
    $group->get('/api/realtime-test/alerts/active', [RealtimeApiController::class, 'getActiveAlerts']);
    
    // Alias de compatibilité pour l'ancienne URL TEST
    $group->get('/api/health-test', [RealtimeApiController::class, 'getSystemHealth']);
    
    // Heartbeat ESP32 TEST
    $group->post('/heartbeat-test', [HeartbeatController::class, 'handle']);
    $group->post('/heartbeat-test.php', [HeartbeatController::class, 'handle']); // Alias legacy
    
    // ====================================================================
    // Fichiers statiques TEST (fallback si serveur web ne les sert pas)
    // ====================================================================
    // Note: Les fichiers statiques sont gérés par le groupe global pour éviter les conflits de routes
    
})->add(new EnvironmentMiddleware('test'));

// ====================================================================
// Fichiers statiques GLOBAUX (disponibles pour PROD et TEST)
// ====================================================================
// Ces routes sont partagées entre les deux environnements pour éviter les conflits
$app->get('/assets/js/{filename}', function (Request $request, Response $response, $args) {
    $filename = $args['filename'];
    $allowedFiles = [
        'control-values-updater.js',
        'control-sync.js', 
        'chart-updater.js',
        'stats-updater.js',
        'realtime-updater.js',
        'toast-notifications.js',
        'pwa-init.js',
        'mobile-gestures.js'
    ];
    
    if (!in_array($filename, $allowedFiles)) {
        return $response->withStatus(404);
    }
    
    $filePath = __DIR__ . '/assets/js/' . $filename;
    if (file_exists($filePath)) {
        $response->getBody()->write(file_get_contents($filePath));
        return $response->withHeader('Content-Type', 'application/javascript');
    }
    return $response->withStatus(404);
});

$app->get('/assets/css/{filename}', function (Request $request, Response $response, $args) {
    $filename = $args['filename'];
    $allowedFiles = [
        'mobile-optimized.css',
        'realtime-styles.css'
    ];
    
    if (!in_array($filename, $allowedFiles)) {
        return $response->withStatus(404);
    }
    
    $filePath = __DIR__ . '/assets/css/' . $filename;
    if (file_exists($filePath)) {
        $response->getBody()->write(file_get_contents($filePath));
        return $response->withHeader('Content-Type', 'text/css');
    }
    return $response->withStatus(404);
});

$app->get('/assets/icons/{filename}', function (Request $request, Response $response, $args) {
    $filename = $args['filename'];
    $allowedFiles = [
        'icon-72.png', 'icon-96.png', 'icon-128.png', 'icon-144.png',
        'icon-152.png', 'icon-192.png', 'icon-384.png', 'icon-512.png'
    ];
    
    if (!in_array($filename, $allowedFiles)) {
        return $response->withStatus(404);
    }
    
    $filePath = __DIR__ . '/assets/icons/' . $filename;
    if (file_exists($filePath)) {
        $response->getBody()->write(file_get_contents($filePath));
        return $response->withHeader('Content-Type', 'image/png');
    }
    return $response->withStatus(404);
});

$app->get('/service-worker.js', function (Request $request, Response $response) {
    $swPath = __DIR__ . '/service-worker.js';
    if (file_exists($swPath)) {
        $response->getBody()->write(file_get_contents($swPath));
        return $response->withHeader('Content-Type', 'application/javascript');
    }
    return $response->withStatus(404);
});

// ====================================================================
// Middleware Slim (routing et erreurs)
// ====================================================================
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->run();
