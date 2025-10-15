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

    // ====================================================================
    // Heartbeat ESP32 PROD
    // ====================================================================
    $group->post('/heartbeat', [HeartbeatController::class, 'handle']);
    $group->post('/heartbeat.php', function (Request $request, Response $response) {
        return $response->withHeader('Location', '/ffp3/heartbeat')->withStatus(301);
    }); // Redirection legacy vers heartbeat
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
    
    // Heartbeat ESP32 TEST
    $group->post('/heartbeat-test', [HeartbeatController::class, 'handle']);
    $group->post('/heartbeat-test.php', [HeartbeatController::class, 'handle']); // Alias legacy
    
})->add(new EnvironmentMiddleware('test'));

// ====================================================================
// Middleware Slim (routing et erreurs)
// ====================================================================
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->run();
