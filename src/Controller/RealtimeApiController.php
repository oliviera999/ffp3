<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\RealtimeDataService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Contrôleur API pour les données temps réel
 * Fournit des endpoints JSON pour le polling côté client
 */
class RealtimeApiController
{
    public function __construct(
        private RealtimeDataService $realtimeService
    ) {
    }

    /**
     * GET /api/realtime/sensors/latest
     * Retourne les dernières lectures de tous les capteurs
     */
    public function getLatestSensors(Request $request, Response $response): Response
    {
        $data = $this->realtimeService->getLatestReadings();
        
        return $this->jsonResponse($response, $data);
    }

    /**
     * GET /api/realtime/sensors/since/{timestamp}
     * Retourne les nouvelles lectures depuis un timestamp Unix donné
     */
    public function getSensorsSince(Request $request, Response $response, array $args): Response
    {
        $timestamp = (int)($args['timestamp'] ?? 0);
        
        if ($timestamp <= 0) {
            return $this->jsonResponse($response, [
                'error' => 'Invalid timestamp',
            ], 400);
        }

        $data = $this->realtimeService->getReadingsSince($timestamp);
        
        return $this->jsonResponse($response, [
            'count' => count($data),
            'readings' => $data,
        ]);
    }

    /**
     * GET /api/realtime/outputs/state
     * Retourne l'état actuel de tous les GPIO/outputs
     */
    public function getOutputsState(Request $request, Response $response): Response
    {
        $data = $this->realtimeService->getOutputsState();
        
        return $this->jsonResponse($response, [
            'timestamp' => time(),
            'outputs' => $data,
        ]);
    }

    /**
     * GET /api/realtime/system/health
     * Retourne le statut de santé du système
     */
    public function getSystemHealth(Request $request, Response $response): Response
    {
        $health = $this->realtimeService->getSystemHealth();
        
        return $this->jsonResponse($response, $health);
    }

    /**
     * GET /api/realtime/alerts/active
     * Retourne la liste des alertes actives
     */
    public function getActiveAlerts(Request $request, Response $response): Response
    {
        $alerts = $this->realtimeService->getActiveAlerts();
        
        return $this->jsonResponse($response, [
            'timestamp' => time(),
            'count' => count($alerts),
            'alerts' => $alerts,
        ]);
    }

    /**
     * Helper pour créer une réponse JSON
     */
    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}

