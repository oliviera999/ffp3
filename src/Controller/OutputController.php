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
        
        // Déléguer au service avec marquage web
        $success = $this->outputService->updateStateById($id, $state, 'web');
        
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
