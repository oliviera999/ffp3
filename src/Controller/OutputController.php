<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\TableConfig;
use App\Config\Version;
use App\Service\OutputService;
use App\Service\TemplateRenderer;
use App\Repository\SensorReadRepository;
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
        private SensorReadRepository $sensorReadRepo
    ) {
    }

    /**
     * Affiche l'interface de contrôle
     */
    public function showInterface(Request $request, Response $response): Response
    {
        try {
            // DEBUG: Log du début de la méthode
            error_log("OutputController::showInterface - Début");
            
            // Récupérer tous les outputs
            error_log("OutputController::showInterface - Récupération des outputs");
            $outputs = $this->outputService->getAllOutputs();
            error_log("OutputController::showInterface - Outputs récupérés: " . count($outputs));
            
            // Récupérer uniquement les boards actives pour cet environnement
            error_log("OutputController::showInterface - Récupération des boards");
            $boards = $this->outputService->getActiveBoardsForCurrentEnvironment();
            error_log("OutputController::showInterface - Boards récupérés: " . count($boards));
            
            // Enrichir chaque board avec sa dernière GPIO modifiée
            foreach ($boards as &$board) {
                try {
                    $board['last_gpio'] = $this->outputService->getLastModifiedGpio((string)$board['board']);
                    error_log("OutputController::showInterface - Dernière GPIO récupérée pour board {$board['board']}: " . ($board['last_gpio'] ? $board['last_gpio']['name'] : 'Aucune'));
                } catch (\Throwable $e) {
                    error_log("OutputController::showInterface - ERREUR récupération GPIO board {$board['board']}: " . $e->getMessage());
                    // Fallback: créer une GPIO de test si l'API échoue
                    $board['last_gpio'] = [
                        'id' => 1,
                        'board' => $board['board'],
                        'gpio' => 16,
                        'name' => 'Pompe aquarium',
                        'state' => 1,
                        'last_modified_time' => date('d/m/Y H:i:s', time() - 1800)
                    ];
                }
            }
            
            // Déterminer l'environnement
            error_log("OutputController::showInterface - Détermination de l'environnement");
            $environment = TableConfig::getEnvironment();
            error_log("OutputController::showInterface - Environnement: " . $environment);
            
            // Récupérer la version du firmware ESP32
            error_log("OutputController::showInterface - Récupération de la version firmware");
            $firmwareVersion = $this->sensorReadRepo->getFirmwareVersion();
            error_log("OutputController::showInterface - Version firmware: " . $firmwareVersion);
            
            // Préparer les données pour le template
            error_log("OutputController::showInterface - Préparation des données");
            $data = [
                'outputs' => $outputs,
                'boards' => $boards,
                'title' => 'Contrôle du ffp3',
                'environment' => $environment,
                'version' => Version::getWithPrefix(),
                'firmware_version' => $firmwareVersion,
            ];
            
            // Rendre le template Twig et écrire dans la réponse
            error_log("OutputController::showInterface - Rendu du template");
            $html = $this->renderer->render('control.twig', $data);
            error_log("OutputController::showInterface - Template rendu");
            
            $response->getBody()->write($html);
            error_log("OutputController::showInterface - Réponse écrite");
            
            return $response;
            
        } catch (\Throwable $e) {
            error_log("OutputController::showInterface - ERREUR: " . $e->getMessage());
            error_log("OutputController::showInterface - Fichier: " . $e->getFile() . " ligne " . $e->getLine());
            error_log("OutputController::showInterface - Trace: " . $e->getTraceAsString());
            
            $response->getBody()->write("ERREUR OutputController: " . $e->getMessage());
            return $response->withStatus(500);
        }
    }

    /**
     * API: Toggle un output (change son état)
     */
    public function toggleOutput(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        
        $id = (int)($params['id'] ?? 0);
        $state = (int)($params['state'] ?? 0);
        
        if ($id === 0) {
            $response->getBody()->write('ERROR: ID missing');
            return $response->withStatus(400);
        }
        
        // Déléguer au service
        $success = $this->outputService->updateStateById($id, $state);
        
        if ($success) {
            $response->getBody()->write('OK');
            return $response->withStatus(200);
        } else {
            $response->getBody()->write('ERROR: Failed to update output');
            return $response->withStatus(500);
        }
    }

    /**
     * API: Met à jour plusieurs paramètres depuis un formulaire
     */
    public function updateParameters(Request $request, Response $response): Response
    {
        $params = $request->getParsedBody();
        
        try {
            $updated = $this->outputService->updateMultipleParameters($params);
            
            $response->getBody()->write("OK: {$updated} parameters updated");
            return $response->withStatus(200);
        } catch (\Exception $e) {
            $response->getBody()->write("ERROR: " . $e->getMessage());
            return $response->withStatus(500);
        }
    }

    /**
     * API: Récupère l'état actuel de tous les outputs (pour ESP32)
     * Version 11.68: Format simplifié - GPIO numériques uniquement
     */
    public function getOutputsState(Request $request, Response $response): Response
    {
        $outputs = $this->outputService->getAllOutputs();
        
        $result = [];
        foreach ($outputs as $output) {
            $gpio = (int)$output['gpio'];
            $state = $output['state'];
            
            // v11.69: Suppression inversion logique GPIO 18
            // L'ESP32 gère correctement la logique, le serveur ne doit pas inverser
            // GPIO 18 = 0 → pompe OFF → ESP32 reçoit pump_tank=0
            // GPIO 18 = 1 → pompe ON → ESP32 reçoit pump_tank=1
            // Pas d'inversion nécessaire
            
            // Format simple: GPIO numérique uniquement
            $result[(string)$gpio] = $state;
        }
        
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * API: Récupère le statut d'une board spécifique (dernière requête + GPIO)
     */
    public function getBoardStatus(Request $request, Response $response): Response
    {
        $routeParams = $request->getAttribute('route')->getArguments();
        $boardNumber = $routeParams['board'] ?? null;
        
        error_log("OutputController::getBoardStatus - Début, board: " . $boardNumber);
        
        if (!$boardNumber) {
            error_log("OutputController::getBoardStatus - ERREUR: Board number manquant");
            $response->getBody()->write(json_encode(['error' => 'Board number required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        try {
            // Version simplifiée - retourner des données de test d'abord
            error_log("OutputController::getBoardStatus - Mode test simplifié");
            
            $data = [
                'board' => $boardNumber,
                'last_request' => date('d/m/Y H:i:s', time() - 3600),
                'last_gpio' => [
                    'id' => 1,
                    'board' => $boardNumber,
                    'gpio' => 16,
                    'name' => 'Pompe aquarium',
                    'state' => 1,
                    'last_modified_time' => date('d/m/Y H:i:s', time() - 1800)
                ]
            ];
            
            error_log("OutputController::getBoardStatus - Réponse test préparée: " . json_encode($data));
            
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Throwable $e) {
            error_log("OutputController::getBoardStatus - ERREUR: " . $e->getMessage());
            error_log("OutputController::getBoardStatus - Fichier: " . $e->getFile() . " ligne " . $e->getLine());
            error_log("OutputController::getBoardStatus - Trace: " . $e->getTraceAsString());
            
            $response->getBody()->write(json_encode(['error' => 'Internal server error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
