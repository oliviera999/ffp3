<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use App\Config\TableConfig;
use App\Config\Version;
use App\Service\OutputCacheService;
use App\Service\OutputService;
use App\Service\TemplateRenderer;
use App\Repository\SensorReadRepository;
use App\Util\RequestHelper;
use App\Util\ResponseHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Contrôleur pour l'interface de contrôle des GPIO/outputs
 * 
 * Gère l'affichage et les actions (toggle, update) sur les outputs
 */
class OutputController
{
    public function __construct(
        private OutputService $outputService,
        private TemplateRenderer $renderer,
        private SensorReadRepository $sensorReadRepo,
        private OutputCacheService $outputCache
    ) {
    }

    /**
     * Affiche l'interface de contrôle
     */
    public function showInterface(Request $request, Response $response): Response
    {
        try {
            $outputs = $this->outputService->getAllOutputs();
            $boards = $this->outputService->getActiveBoardsForCurrentEnvironment();
            $params = $this->outputService->getParametersMap();

            foreach ($boards as &$board) {
                try {
                    $board['last_gpio'] = $this->outputService->getLastModifiedGpio((string)$board['board']);
                } catch (\Throwable $e) {
                    $board['last_gpio'] = null;
                }
            }

            $environment = TableConfig::getEnvironment();
            $firmwareVersion = $this->sensorReadRepo->getFirmwareVersion();

            // Mapping des paramètres vers leurs GPIOs (utilisé dans le template)
            $parameterGpioMap = [
                'aqThr' => 102,
                'taThr' => 103,
                'tempsRemplissageSec' => 113,
                'limFlood' => 114,
                'bouffeMat' => 105,
                'bouffeMid' => 106,
                'bouffeSoir' => 107,
                'tempsGros' => 111,
                'tempsPetits' => 112,
                'chauff' => 104,
                'mail' => 100,
                'FreqWakeUp' => 116,
            ];

            $data = [
                'outputs' => $outputs,
                'boards' => $boards,
                'params' => $params,
                'parameter_gpio_map' => $parameterGpioMap,
                'title' => 'Contrôle du ffp3',
                'environment' => $environment,
                'version' => Version::getWithPrefix(),
                'firmware_version' => $firmwareVersion,
            ];
            
            $html = $this->renderer->render('control.twig', $data);
            $response->getBody()->write($html);

            return $response;

        } catch (\Throwable $e) {
            // Log détaillé de l'erreur pour le debugging
            error_log(sprintf(
                "OutputController::showInterface ERROR: %s in %s:%d\nStack trace:\n%s",
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            ));
            
            // Message d'erreur selon l'environnement
            $isDevelopment = ($_ENV['ENV'] ?? 'prod') === 'test' || (bool)($_ENV['DEBUG'] ?? false);
            
            if ($isDevelopment) {
                $errorMessage = sprintf(
                    "ERREUR OutputController: %s\nFichier: %s\nLigne: %d\n\nStack trace:\n%s",
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getTraceAsString()
                );
            } else {
                $errorMessage = "Une erreur serveur est survenue. Veuillez contacter l'administrateur.";
            }
            
            return ResponseHelper::text($response, $errorMessage, 500);
        }
    }

    /**
     * API: Toggle un output (change son état)
     */
    public function toggleOutput(Request $request, Response $response): Response
    {
        \App\Config\TableConfig::setEnvironment('prod');
        return $this->handleToggle($request, $response);
    }

    public function toggleOutputTest(Request $request, Response $response): Response
    {
        \App\Config\TableConfig::setEnvironment('test');
        return $this->handleToggle($request, $response);
    }

    private function handleToggle(Request $request, Response $response): Response
    {
        $params = RequestHelper::extractParams($request);

        $id = RequestHelper::getInt($params, 'id', 0);
        $state = RequestHelper::getInt($params, 'state', -1);

        if ($id === 0 || ($state !== 0 && $state !== 1)) {
            return ResponseHelper::error($response, 'Invalid parameters', 400);
        }

        $isTest = \App\Config\TableConfig::getEnvironment() === 'test';
        $success = $this->outputService->updateStateById($id, $state, 'web', $isTest);

        if ($success) {
            return ResponseHelper::success($response, ['id' => $id, 'state' => $state]);
        }

        return ResponseHelper::error($response, 'Failed to update output', 500);
    }

    /**
     * API: Met à jour plusieurs paramètres depuis un formulaire
     */
    public function updateParameters(Request $request, Response $response): Response
    {
        $payload = RequestHelper::extractParams($request);
        
        // Gestion du format {param: ..., value: ...}
        if (isset($payload['param'])) {
            $payload = [$payload['param'] => $payload['value'] ?? null];
        }

        if (!is_array($payload) || $payload === []) {
            return ResponseHelper::error($response, 'No parameters provided', 400);
        }

        try {
            $updated = $this->outputService->updateMultipleParameters($payload);

            return ResponseHelper::success($response, ['updated' => $updated]);
        } catch (\Throwable $e) {
            return ResponseHelper::error($response, 'Failed to persist parameters', 500);
        }
    }

    /**
     * API: Récupère l'état actuel de tous les outputs (pour ESP32)
     * Version 11.68: Format simplifié - GPIO numériques uniquement
     * Version 11.127: Cache ajouté pour réduire charge serveur
     */
    public function getOutputsState(Request $request, Response $response): Response
    {
        // Liste des GPIOs critiques attendus par l'ESP32 (voir include/gpio_mapping.h)
        $gpioList = [
            2, 15, 16, 18, // actionneurs physiques: chauffage, lumière, pompe aqua, pompe tank
            100, 101, 102, 103, 104, 105, 106, 107, // email + params
            108, 109, 110, // commandes nourrissage + reset
            111, 112, 113, 114, 115, 116 // durées / limites / wake
        ];

        // Utiliser le cache pour éviter requêtes SQL répétées
        $pdo = Database::getConnection();
        $result = $this->outputCache->getOutputsState($pdo, $gpioList);

        return ResponseHelper::json($response, $result);
    }

    /**
     * API: Récupère le statut d'une board spécifique (dernière requête + GPIO)
     */
    public function getBoardStatus(Request $request, Response $response): Response
    {
        $route = $request->getAttribute('route');
        if ($route === null) {
            return ResponseHelper::error($response, 'Route not found', 500);
        }
        
        $routeParams = $route->getArguments();
        $boardNumber = $routeParams['board'] ?? null;
        
        if (!$boardNumber) {
            return ResponseHelper::error($response, 'Board number required', 400);
        }
        
        try {
            $status = $this->outputService->getBoardStatus($boardNumber);

            if ($status === null) {
                return ResponseHelper::error($response, 'Board not found', 404);
            }

            return ResponseHelper::json($response, $status);

        } catch (\Throwable $e) {
            return ResponseHelper::error($response, 'Internal server error', 500);
        }
    }
}
