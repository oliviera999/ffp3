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

            $data = [
                'outputs' => $outputs,
                'boards' => $boards,
                'params' => $params,
                'title' => 'Contrôle du ffp3',
                'environment' => $environment,
                'version' => Version::getWithPrefix(),
                'firmware_version' => $firmwareVersion,
            ];
            
            $html = $this->renderer->render('control.twig', $data);
            $response->getBody()->write($html);

            return $response;

        } catch (\Throwable $e) {
            $response->getBody()->write("ERREUR OutputController: " . $e->getMessage());
            return $response->withStatus(500);
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
        $params = [];

        if ($request->getMethod() === 'POST') {
            $contentType = strtolower($request->getHeaderLine('Content-Type'));

            if (str_contains($contentType, 'application/json')) {
                $rawBody = (string)$request->getBody();
                $decoded = json_decode($rawBody, true);
                if (is_array($decoded)) {
                    $params = $decoded;
                }
            }

            if ($params === []) {
                $parsedBody = $request->getParsedBody();
                if (is_array($parsedBody)) {
                    $params = $parsedBody;
                }
            }
        }

        if ($params === []) {
            $params = $request->getQueryParams();
        }

        $id = isset($params['id']) ? (int)$params['id'] : 0;
        $state = isset($params['state']) ? (int)$params['state'] : 0;

        if ($id === 0 || ($state !== 0 && $state !== 1)) {
            $payload = json_encode([
                'status' => 'error',
                'message' => 'Invalid parameters',
            ]);

            $response->getBody()->write($payload);
            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        }

        $isTest = \App\Config\TableConfig::getEnvironment() === 'test';
        $success = $this->outputService->updateStateById($id, $state, 'web', $isTest);

        if ($success) {
            $payload = json_encode([
                'status' => 'ok',
                'id' => $id,
                'state' => $state,
            ]);

            $response->getBody()->write($payload);
            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json');
        }

        $payload = json_encode([
            'status' => 'error',
            'message' => 'Failed to update output',
        ]);

        $response->getBody()->write($payload);
        return $response
            ->withStatus(500)
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * API: Met à jour plusieurs paramètres depuis un formulaire
     */
    public function updateParameters(Request $request, Response $response): Response
    {
        $payload = [];

        if ($request->getMethod() === 'POST') {
            $contentType = strtolower($request->getHeaderLine('Content-Type'));

            if (str_contains($contentType, 'application/json')) {
                $rawBody = (string)$request->getBody();
                $decoded = json_decode($rawBody, true);
                if (is_array($decoded)) {
                    if (isset($decoded['param'])) {
                        $payload[$decoded['param']] = $decoded['value'] ?? null;
                    } else {
                        $payload = $decoded;
                    }
                }
            }

            if ($payload === []) {
                $parsed = $request->getParsedBody();
                if (is_array($parsed)) {
                    $payload = $parsed;
                }
            }
        }

        if ($payload === []) {
            $payload = $request->getQueryParams();
        }

        if (!is_array($payload) || $payload === []) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'No parameters provided',
            ]));
            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        }

        try {
            $updated = $this->outputService->updateMultipleParameters($payload);

            $response->getBody()->write(json_encode([
                'status' => 'ok',
                'updated' => $updated,
            ]));
            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Failed to persist parameters',
            ]));
            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * API: Récupère l'état actuel de tous les outputs (pour ESP32)
     * Version 11.68: Format simplifié - GPIO numériques uniquement
     */
    public function getOutputsState(Request $request, Response $response): Response
    {
        // v11.72: Retour direct par liste de GPIOs critiques (sans dépendre des noms)
        // Objectif: garantir que les clés distantes existent toujours

        // Liste des GPIOs critiques attendus par l'ESP32 (voir include/gpio_mapping.h)
        $gpioList = [
            2, 15, 16, 18, // actionneurs physiques: chauffage, lumière, pompe aqua, pompe tank
            100, 101, 102, 103, 104, 105, 106, 107, // email + params
            108, 109, 110, // commandes nourrissage + reset
            111, 112, 113, 114, 115, 116 // durées / limites / wake
        ];

        $table = TableConfig::getOutputsTable();
        $pdo = \App\Config\Database::getConnection();

        // Construire requête IN sécurisée
        $placeholders = [];
        $params = [];
        foreach ($gpioList as $idx => $gpio) {
            $ph = ":g{$idx}";
            $placeholders[] = $ph;
            $params[$ph] = $gpio;
        }

        $sql = "SELECT gpio, state FROM {$table} WHERE gpio IN (" . implode(',', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Indexer par gpio pour accès rapide
        $byGpio = [];
        foreach ($rows as $row) {
            $byGpio[(int)$row['gpio']] = $row['state'];
        }

        // Normalisation: booléens en 0/1, conservation des strings pour email, strings numériques pour configs
        $result = [];

        // Définir ensemble des GPIOs booléens à normaliser (cohérent avec OutputRepository)
        $boolGpios = [];
        for ($i = 0; $i < 100; $i++) { $boolGpios[$i] = true; }
        foreach ([101,108,109,110,115] as $b) { $boolGpios[$b] = true; }

        foreach ($gpioList as $gpio) {
            if (!array_key_exists($gpio, $byGpio)) {
                // Si absent en BDD, ne pas inventer une valeur; passer sous silence
                // (l'ESP32 conservera l'état précédent ou les valeurs par défaut)
                continue;
            }

            $state = $byGpio[$gpio];

            if (isset($boolGpios[$gpio])) {
                // Normaliser vers 0/1
                if (is_string($state)) {
                    $s = strtolower(trim($state));
                    $state = in_array($s, ['checked','true','on','1','yes'], true) ? 1 : (in_array($s, ['unchecked','false','off','0','no'], true) ? 0 : (is_numeric($s) ? (int)$s : 0));
                } else {
                    $state = (int)$state;
                }
            } else {
                // Laisser tel quel (string numérique ou texte)
                // Email (100) reste string, paramètres (102-107,111-116) souvent strings numériques
            }

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
        
        if (!$boardNumber) {
            $response->getBody()->write(json_encode(['error' => 'Board number required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        try {
            $status = $this->outputService->getBoardStatus($boardNumber);

            if ($status === null) {
                $response->getBody()->write(json_encode(['error' => 'Board not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($status));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode(['error' => 'Internal server error']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
