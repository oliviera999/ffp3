<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Config\Env;
use App\Controller\AquaponieController;
use App\Controller\DashboardController;
use App\Controller\ExportController;
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
// Middleware de gestion d'erreurs personnalisé
// ====================================================================
$app->add(new \App\Middleware\ErrorHandlerMiddleware());

// ====================================================================
// Routes PRODUCTION (par défaut)
// ====================================================================

$app->get('/', [DashboardController::class, 'show']);
$app->get('/dashboard', [DashboardController::class, 'show']);

// Page aquaponie
$app->map(['GET', 'POST'], '/aquaponie', [AquaponieController::class, 'show']);
$app->map(['GET', 'POST'], '/ffp3-data', [AquaponieController::class, 'show']); // Alias legacy

// Post data depuis ESP32
$app->post('/post-data', [PostDataController::class, 'handle']);
$app->post('/post-ffp3-data.php', [PostDataController::class, 'handle']); // Alias legacy

// Export CSV
$app->get('/export-data', [ExportController::class, 'downloadCsv']);
$app->get('/export-data.php', [ExportController::class, 'downloadCsv']); // Alias legacy

// Statistiques marées
$app->map(['GET', 'POST'], '/tide-stats', [TideStatsController::class, 'show']);

// Interface de contrôle PROD
$app->get('/control', [OutputController::class, 'showInterface']);
$app->get('/api/outputs/toggle', [OutputController::class, 'toggleOutput']);
$app->get('/api/outputs/state', [OutputController::class, 'getOutputsState']);
$app->post('/api/outputs/parameters', [OutputController::class, 'updateParameters']);

// ====================================================================
// API Temps Réel PROD
// ====================================================================
$app->get('/api/realtime/sensors/latest', [RealtimeApiController::class, 'getLatestSensors']);
$app->get('/api/realtime/sensors/since/{timestamp}', [RealtimeApiController::class, 'getSensorsSince']);
$app->get('/api/realtime/outputs/state', [RealtimeApiController::class, 'getOutputsState']);
$app->get('/api/realtime/system/health', [RealtimeApiController::class, 'getSystemHealth']);
$app->get('/api/realtime/alerts/active', [RealtimeApiController::class, 'getActiveAlerts']);

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
    
})->add(new EnvironmentMiddleware('test'));

// ====================================================================
// Middleware Slim (routing et erreurs)
// ====================================================================
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->run();
