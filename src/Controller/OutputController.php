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
        // Récupérer tous les outputs
        $outputs = $this->outputService->getAllOutputs();
        
        // Récupérer uniquement les boards actives pour cet environnement
        $boards = $this->outputService->getActiveBoardsForCurrentEnvironment();
        
        // Déterminer l'environnement
        $environment = TableConfig::getEnvironment();
        
        // Récupérer la version du firmware ESP32
        $firmwareVersion = $this->sensorReadRepo->getFirmwareVersion();
        
        // Préparer les données pour le template
        $data = [
            'outputs' => $outputs,
            'boards' => $boards,
            'title' => 'Contrôle du ffp3',
            'environment' => $environment,
            'version' => Version::getWithPrefix(),
            'firmware_version' => $firmwareVersion,
        ];
        
        // Rendre le template Twig et écrire dans la réponse
        $html = $this->renderer->render('control.twig', $data);
        $response->getBody()->write($html);
        return $response;
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
     */
    public function getOutputsState(Request $request, Response $response): Response
    {
        $outputs = $this->outputService->getAllOutputs();
        
        // Mapping GPIO → noms pour compatibilité ESP32
        // L'ESP32 attend des clés par nom (light, heat, etc.) ET par numéro GPIO
        $gpioMapping = [
            2 => 'heat',           // Radiateurs
            15 => 'light',         // Lumière
            16 => 'pump_aqua',     // Pompe aquarium
            18 => 'pump_tank',     // Pompe réservoir
            12 => 'bouffeGros',    // Servo gros
            13 => 'bouffePetits',  // Servo petits
            // Paramètres de configuration (GPIO 100-116)
            100 => 'mail',
            101 => 'mailNotif',
            102 => 'aqThr',
            103 => 'taThr',
            104 => 'chauff',
            105 => 'bouffeMat',
            106 => 'bouffeMid',
            107 => 'bouffeSoir',
            111 => 'tempsGros',
            112 => 'tempsPetits',
            113 => 'tempsRemplissageSec',
            114 => 'limFlood',
            115 => 'WakeUp',
            116 => 'FreqWakeUp',
        ];
        
        $result = [];
        foreach ($outputs as $output) {
            $gpio = (int)$output['gpio'];
            $state = $output['state'];
            
            // Inverser la logique pour la pompe réservoir (GPIO 18)
            // car elle utilise une logique inversée côté hardware
            // GPIO 18 = 0 → pompe ON → on envoie pump_tank=1 à l'ESP32
            // GPIO 18 = 1 → pompe OFF → on envoie pump_tank=0 à l'ESP32
            if ($gpio === 18) {
                $state = $state === 0 ? 1 : 0;
            }
            
            // Ajouter par numéro GPIO (rétrocompatibilité)
            $result[(string)$gpio] = $state;
            
            // Ajouter par nom si mapping existe (nouveau format)
            if (isset($gpioMapping[$gpio])) {
                $result[$gpioMapping[$gpio]] = $state;
            }
        }
        
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
