<?php

namespace App\Controller;

use App\Config\Database;
use App\Repository\OutputRepository;
use App\Repository\BoardRepository;
use App\Service\OutputService;
use App\Service\LogService;
use App\Service\TemplateRenderer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Contrôleur pour la gestion des Outputs (GPIO/relais)
 */
class OutputController
{
    private OutputService $outputService;
    private TemplateRenderer $renderer;

    public function __construct()
    {
        $pdo = Database::getConnection();
        
        $outputRepo = new OutputRepository($pdo);
        $boardRepo = new BoardRepository($pdo);
        $logService = new LogService();
        
        $this->outputService = new OutputService($outputRepo, $boardRepo, $logService);
        $this->renderer = new TemplateRenderer();
    }

    /**
     * Affiche l'interface de contrôle
     */
    public function showInterface(): void
    {
        try {
            // Récupérer les données pour l'interface
            $data = $this->outputService->getOutputsForInterface();
            
            // Récupérer les boards actifs (board 1 par défaut)
            $boardInfo = $this->outputService->getBoardInfo('1');
            
            // Préparer les données pour le template
            $templateData = [
                'main_outputs' => $data['main_outputs'],
                'system_config' => $data['system_config'],
                'board_info' => $boardInfo,
                'page_title' => 'Contrôle du FFP3'
            ];
            
            // Rendre le template
            $this->renderer->render('control.twig', $templateData);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo "Erreur serveur: " . htmlspecialchars($e->getMessage());
        }
    }

    /**
     * API : Récupère les états pour un board (ESP32)
     * GET /api/outputs/states/{board}
     */
    public function getStatesApi(Request $request, Response $response, array $args): Response
    {
        try {
            $board = $args['board'] ?? '';
            
            if (empty($board)) {
                $response->getBody()->write(json_encode([
                    'error' => 'Board parameter missing'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }
            
            // Récupérer les états
            $states = $this->outputService->getStatesForBoard($board);
            
            $response->getBody()->write(json_encode($states));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * API : Met à jour un output
     * POST /api/outputs/{id}/state
     * Body: {"state": 0|1}
     */
    public function updateOutputApi(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) ($args['id'] ?? 0);
            
            if ($id <= 0) {
                $response->getBody()->write(json_encode([
                    'error' => 'Invalid ID'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }
            
            // Récupérer le body JSON
            $body = $request->getParsedBody();
            $state = isset($body['state']) ? (int) $body['state'] : null;
            
            if ($state === null || ($state !== 0 && $state !== 1)) {
                $response->getBody()->write(json_encode([
                    'error' => 'Invalid state (must be 0 or 1)'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }
            
            // Mettre à jour
            $success = $this->outputService->updateOutputState($id, $state);
            
            if ($success) {
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'Output state updated successfully'
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            } else {
                $response->getBody()->write(json_encode([
                    'error' => 'Output not found or update failed'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }
            
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
                
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * API : Met à jour la configuration système
     * POST /api/system/config
     * Body: {"100": "email@test.com", "102": "15", ...}
     */
    public function updateConfigApi(Request $request, Response $response): Response
    {
        try {
            // Récupérer le body JSON
            $config = $request->getParsedBody();
            
            if (empty($config) || !is_array($config)) {
                $response->getBody()->write(json_encode([
                    'error' => 'Invalid configuration data'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }
            
            // Mettre à jour la configuration
            $success = $this->outputService->updateSystemConfiguration($config);
            
            if ($success) {
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'System configuration updated successfully'
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            } else {
                $response->getBody()->write(json_encode([
                    'error' => 'Configuration update failed'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            }
            
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Validation error',
                'message' => $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
                
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * API : Supprime un output
     * DELETE /api/outputs/{id}
     */
    public function deleteOutputApi(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) ($args['id'] ?? 0);
            
            if ($id <= 0) {
                $response->getBody()->write(json_encode([
                    'error' => 'Invalid ID'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }
            
            // Supprimer
            $success = $this->outputService->deleteOutput($id);
            
            if ($success) {
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'Output deleted successfully'
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            } else {
                $response->getBody()->write(json_encode([
                    'error' => 'Output not found'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * API : Toggle un output (change son état)
     * POST /api/outputs/{id}/toggle
     */
    public function toggleOutputApi(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) ($args['id'] ?? 0);
            
            if ($id <= 0) {
                $response->getBody()->write(json_encode([
                    'error' => 'Invalid ID'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }
            
            // Toggle
            $this->outputService->toggleOutput($id);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Output toggled successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
                
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * API : Récupère les infos de tous les boards
     * GET /api/boards
     */
    public function getAllBoardsApi(Request $request, Response $response): Response
    {
        try {
            $boards = $this->outputService->getAllBoardsInfo();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'boards' => $boards
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}

