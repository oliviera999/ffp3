<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Config\Env;
use App\Config\TableConfig;
use App\Controller\DashboardController;
use App\Controller\ExportController;
use App\Controller\PostDataController;
use App\Controller\AquaponieController;
use App\Controller\OutputController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;

// Charge les variables d'environnement (.env)
Env::load();

// -----------------------------------------------------------------------------
// Création de l'application Slim (pas de container pour l'instant)
// -----------------------------------------------------------------------------
$app = AppFactory::create();

// Forcer le chemin base pour être identique à l'ancien (dossier parent de /public)
$basePath = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'])));
if ($basePath !== '/' && $basePath !== '') {
    $app->setBasePath($basePath);
}

// -----------------------------------------------------------------------------
// Définition des routes principales
// -----------------------------------------------------------------------------
$app->get('/', function (Request $request, Response $response) {
    (new DashboardController())->show();
    return $response;
});
$app->get('/dashboard', function (Request $request, Response $response) {
    (new DashboardController())->show();
    return $response;
});
$app->get('/aquaponie', function (Request $request, Response $response) {
    (new AquaponieController())->show();
    return $response;
});
// Permettre également le POST (formulaire filtre + export CSV)
$app->map(['POST'], '/aquaponie', function (Request $request, Response $response) {
    (new AquaponieController())->show();
    return $response;
});
$app->get('/ffp3-data', function (Request $request, Response $response) {
    // Alias legacy
    (new AquaponieController())->show();
    return $response;
});
// Accept POST on alias as well
$app->map(['POST'], '/ffp3-data', function (Request $request, Response $response) {
    (new AquaponieController())->show();
    return $response;
});
$app->post('/post-data', function (Request $request, Response $response) {
    (new PostDataController())->handle();
    return $response;
});
$app->post('/post-ffp3-data.php', function (Request $request, Response $response) {
    // Alias legacy (ESP32)
    (new PostDataController())->handle();
    return $response;
});
$app->get('/export-data', function (Request $request, Response $response) {
    (new ExportController())->downloadCsv();
    return $response;
});
$app->get('/export-data.php', function (Request $request, Response $response) {
    // Alias legacy
    (new ExportController())->downloadCsv();
    return $response;
});

// Page statistiques marée (GET + POST pour formulaire)
$app->map(['GET', 'POST'], '/tide-stats', function (Request $request, Response $response) {
    (new \App\Controller\TideStatsController())->show();
    return $response;
});

// -----------------------------------------------------------------------------
// Routes TEST (utilisent ffp3Data2, ffp3Outputs2)
// -----------------------------------------------------------------------------
$app->get('/dashboard-test', function (Request $request, Response $response) {
    TableConfig::setEnvironment('test');
    (new DashboardController())->show();
    return $response;
});

$app->map(['GET', 'POST'], '/aquaponie-test', function (Request $request, Response $response) {
    TableConfig::setEnvironment('test');
    (new AquaponieController())->show();
    return $response;
});

$app->post('/post-data-test', function (Request $request, Response $response) {
    TableConfig::setEnvironment('test');
    (new PostDataController())->handle();
    return $response;
});

$app->map(['GET', 'POST'], '/tide-stats-test', function (Request $request, Response $response) {
    TableConfig::setEnvironment('test');
    (new \App\Controller\TideStatsController())->show();
    return $response;
});

$app->get('/export-data-test', function (Request $request, Response $response) {
    TableConfig::setEnvironment('test');
    (new ExportController())->downloadCsv();
    return $response;
});

// -----------------------------------------------------------------------------
// Routes CONTRÔLE OUTPUTS - PRODUCTION (ffp3Outputs)
// -----------------------------------------------------------------------------

// Interface de contrôle PROD
$app->get('/control', function (Request $request, Response $response) {
    TableConfig::setEnvironment('prod');
    (new OutputController())->showInterface();
    return $response;
});

// API : États GPIO pour board (ESP32)
$app->get('/api/outputs/states/{board}', function (Request $request, Response $response, array $args) {
    TableConfig::setEnvironment('prod');
    return (new OutputController())->getStatesApi($request, $response, $args);
});

// API : Mettre à jour un output
$app->post('/api/outputs/{id}/state', function (Request $request, Response $response, array $args) {
    TableConfig::setEnvironment('prod');
    return (new OutputController())->updateOutputApi($request, $response, $args);
});

// API : Toggle un output
$app->post('/api/outputs/{id}/toggle', function (Request $request, Response $response, array $args) {
    TableConfig::setEnvironment('prod');
    return (new OutputController())->toggleOutputApi($request, $response, $args);
});

// API : Mettre à jour configuration système
$app->post('/api/system/config', function (Request $request, Response $response) {
    TableConfig::setEnvironment('prod');
    return (new OutputController())->updateConfigApi($request, $response);
});

// API : Supprimer un output
$app->delete('/api/outputs/{id}', function (Request $request, Response $response, array $args) {
    TableConfig::setEnvironment('prod');
    return (new OutputController())->deleteOutputApi($request, $response, $args);
});

// API : Informations boards
$app->get('/api/boards', function (Request $request, Response $response) {
    TableConfig::setEnvironment('prod');
    return (new OutputController())->getAllBoardsApi($request, $response);
});

// -----------------------------------------------------------------------------
// Routes CONTRÔLE OUTPUTS - TEST (ffp3Outputs2)
// -----------------------------------------------------------------------------

// Interface de contrôle TEST
$app->get('/control-test', function (Request $request, Response $response) {
    TableConfig::setEnvironment('test');
    (new OutputController())->showInterface();
    return $response;
});

// API : États GPIO pour board TEST (ESP32)
$app->get('/api/outputs-test/states/{board}', function (Request $request, Response $response, array $args) {
    TableConfig::setEnvironment('test');
    return (new OutputController())->getStatesApi($request, $response, $args);
});

// API : Mettre à jour un output TEST
$app->post('/api/outputs-test/{id}/state', function (Request $request, Response $response, array $args) {
    TableConfig::setEnvironment('test');
    return (new OutputController())->updateOutputApi($request, $response, $args);
});

// API : Toggle un output TEST
$app->post('/api/outputs-test/{id}/toggle', function (Request $request, Response $response, array $args) {
    TableConfig::setEnvironment('test');
    return (new OutputController())->toggleOutputApi($request, $response, $args);
});

// API : Mettre à jour configuration système TEST
$app->post('/api/system-test/config', function (Request $request, Response $response) {
    TableConfig::setEnvironment('test');
    return (new OutputController())->updateConfigApi($request, $response);
});

// API : Supprimer un output TEST
$app->delete('/api/outputs-test/{id}', function (Request $request, Response $response, array $args) {
    TableConfig::setEnvironment('test');
    return (new OutputController())->deleteOutputApi($request, $response, $args);
});

// API : Informations boards TEST
$app->get('/api/boards-test', function (Request $request, Response $response) {
    TableConfig::setEnvironment('test');
    return (new OutputController())->getAllBoardsApi($request, $response);
});

// -----------------------------------------------------------------------------
// Fallback 404 pour toute route non définie (Slim le gère mais on peut personnaliser)
// -----------------------------------------------------------------------------
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->run(); 