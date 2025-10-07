<?php

namespace App\Service;

use App\Repository\OutputRepository;
use App\Repository\BoardRepository;
use App\Service\LogService;

/**
 * Service pour la gestion des Outputs (GPIO/relais)
 * Contient la logique métier
 */
class OutputService
{
    private OutputRepository $outputRepo;
    private BoardRepository $boardRepo;
    private ?LogService $logService;

    public function __construct(
        OutputRepository $outputRepo,
        BoardRepository $boardRepo,
        ?LogService $logService = null
    ) {
        $this->outputRepo = $outputRepo;
        $this->boardRepo = $boardRepo;
        $this->logService = $logService;
    }

    /**
     * Récupère les données pour l'interface de contrôle
     * 
     * @return array
     */
    public function getOutputsForInterface(): array
    {
        // Récupère les 7 premiers outputs (switches principaux)
        $mainOutputs = $this->outputRepo->getPartialOutputs(7);
        
        // Récupère la configuration système (GPIO 100-116)
        $systemConfig = $this->outputRepo->getSystemConfiguration();
        
        // Mappage des GPIO vers noms lisibles
        $configLabels = [
            100 => ['label' => 'Email', 'type' => 'email'],
            101 => ['label' => 'Notifications par email', 'type' => 'checkbox'],
            102 => ['label' => 'Limite niveau aquarium', 'type' => 'number'],
            103 => ['label' => 'Limite niveau réserve', 'type' => 'number'],
            104 => ['label' => 'Seuil chauffage', 'type' => 'number'],
            105 => ['label' => 'Heure alimentation matin', 'type' => 'number'],
            106 => ['label' => 'Heure alimentation midi', 'type' => 'number'],
            107 => ['label' => 'Heure alimentation soir', 'type' => 'number'],
            111 => ['label' => 'Durée nourrissage gros poissons (sec)', 'type' => 'number'],
            112 => ['label' => 'Durée nourrissage petits poissons (sec)', 'type' => 'number'],
            113 => ['label' => 'Temps remplissage aquarium (sec)', 'type' => 'number'],
            114 => ['label' => 'Limite débordement', 'type' => 'number'],
            115 => ['label' => 'Forçage éveil', 'type' => 'number'],
            116 => ['label' => 'Fréquence forçage éveil', 'type' => 'number'],
        ];
        
        // Formater la configuration avec labels
        $formattedConfig = [];
        foreach ($systemConfig as $gpio => $state) {
            if (isset($configLabels[$gpio])) {
                $formattedConfig[] = [
                    'gpio' => $gpio,
                    'label' => $configLabels[$gpio]['label'],
                    'type' => $configLabels[$gpio]['type'],
                    'value' => $state
                ];
            }
        }
        
        return [
            'main_outputs' => $mainOutputs,
            'system_config' => $formattedConfig
        ];
    }

    /**
     * Récupère les états pour un board (format ESP32)
     * 
     * @param string $board Nom du board
     * @return array States [gpio => state]
     */
    public function getStatesForBoard(string $board): array
    {
        // Enregistrer la requête du board
        if ($this->boardRepo->boardExists($board)) {
            $this->boardRepo->updateLastRequest($board);
        } else {
            $this->boardRepo->createBoard($board);
        }
        
        // Récupérer les états
        return $this->outputRepo->getOutputStates($board);
    }

    /**
     * Met à jour l'état d'un output (toggle switch)
     * 
     * @param int $id ID de l'output
     * @param int $state Nouvel état (0 ou 1)
     * @return bool Succès
     * @throws \InvalidArgumentException Si paramètres invalides
     */
    public function updateOutputState(int $id, int $state): bool
    {
        // Validation
        if ($id <= 0) {
            throw new \InvalidArgumentException("ID invalide: {$id}");
        }
        
        if ($state !== 0 && $state !== 1) {
            throw new \InvalidArgumentException("State doit être 0 ou 1");
        }
        
        // Log de l'action
        if ($this->logService) {
            $this->logService->logInfo("Output state update", [
                'output_id' => $id,
                'new_state' => $state,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }
        
        return $this->outputRepo->updateOutputState($id, $state);
    }

    /**
     * Bascule l'état d'un output (0 -> 1, 1 -> 0)
     * 
     * @param int $id ID de l'output
     * @return bool Succès
     */
    public function toggleOutput(int $id): bool
    {
        $output = $this->outputRepo->getOutputById($id);
        
        if (!$output) {
            throw new \RuntimeException("Output #{$id} introuvable");
        }
        
        $newState = $output['state'] == 1 ? 0 : 1;
        
        return $this->updateOutputState($id, $newState);
    }

    /**
     * Met à jour la configuration système complète
     * 
     * @param array $config Configuration [gpio => state]
     * @return bool Succès
     * @throws \InvalidArgumentException Si validation échoue
     */
    public function updateSystemConfiguration(array $config): bool
    {
        // Validation de la configuration
        $errors = $this->validateConfiguration($config);
        
        if (!empty($errors)) {
            throw new \InvalidArgumentException(
                "Configuration invalide: " . implode(', ', $errors)
            );
        }
        
        // Préparer les mises à jour
        $updates = [];
        foreach ($config as $gpio => $state) {
            $updates[] = [
                'gpio' => $gpio,
                'state' => $state
            ];
        }
        
        // Log de l'action
        if ($this->logService) {
            $this->logService->logInfo("System configuration update", [
                'config' => $config,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }
        
        // Mise à jour en transaction
        return $this->outputRepo->updateMultipleOutputs($updates);
    }

    /**
     * Valide une configuration système
     * 
     * @param array $config Configuration à valider
     * @return array Tableau d'erreurs (vide si valide)
     */
    public function validateConfiguration(array $config): array
    {
        $errors = [];
        
        // GPIO valides pour la configuration système
        $validGpios = [100, 101, 102, 103, 104, 105, 106, 107, 111, 112, 113, 114, 115, 116];
        
        foreach ($config as $gpio => $state) {
            // Vérifier que le GPIO est valide
            if (!in_array($gpio, $validGpios)) {
                $errors[] = "GPIO {$gpio} invalide";
                continue;
            }
            
            // Validation spécifique par type
            switch ($gpio) {
                case 100: // Email
                    if (!empty($state) && !filter_var($state, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Email invalide";
                    }
                    break;
                    
                case 101: // Notification checkbox
                    // Accepte "checked", "false", ou booléens
                    break;
                    
                default: // Valeurs numériques
                    if (!is_numeric($state) || $state < 0) {
                        $errors[] = "GPIO {$gpio}: valeur numérique positive requise";
                    }
                    break;
            }
        }
        
        return $errors;
    }

    /**
     * Supprime un output
     * 
     * @param int $id ID de l'output
     * @return bool Succès
     */
    public function deleteOutput(int $id): bool
    {
        // Récupérer le board avant suppression
        $board = $this->outputRepo->getOutputBoard($id);
        
        // Log de l'action
        if ($this->logService) {
            $this->logService->logWarning("Output deleted", [
                'output_id' => $id,
                'board' => $board,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }
        
        // Supprimer l'output
        $deleted = $this->outputRepo->deleteOutput($id);
        
        // Si le board n'a plus d'outputs, le supprimer aussi
        if ($deleted && $board) {
            $remainingOutputs = $this->outputRepo->getOutputsByBoard($board);
            if (empty($remainingOutputs)) {
                $this->boardRepo->deleteBoard($board);
            }
        }
        
        return $deleted;
    }

    /**
     * Récupère les informations d'un board
     * 
     * @param string $board Nom du board
     * @return array|null
     */
    public function getBoardInfo(string $board): ?array
    {
        $boardInfo = $this->boardRepo->getBoardByName($board);
        
        if (!$boardInfo) {
            return null;
        }
        
        // Enrichir avec le nombre d'outputs
        $outputs = $this->outputRepo->getOutputsByBoard($board);
        $boardInfo['output_count'] = count($outputs);
        
        // Calculer le temps depuis dernière requête
        if ($boardInfo['last_request']) {
            $lastRequest = new \DateTime($boardInfo['last_request']);
            $now = new \DateTime();
            $diff = $now->diff($lastRequest);
            
            $boardInfo['minutes_since_last_request'] = 
                $diff->days * 24 * 60 + $diff->h * 60 + $diff->i;
            
            $boardInfo['is_active'] = $boardInfo['minutes_since_last_request'] < 15;
        } else {
            $boardInfo['is_active'] = false;
        }
        
        return $boardInfo;
    }

    /**
     * Récupère tous les boards avec leurs infos
     * 
     * @return array
     */
    public function getAllBoardsInfo(): array
    {
        $boards = $this->boardRepo->getAllBoards();
        
        $enrichedBoards = [];
        foreach ($boards as $board) {
            $enrichedBoards[] = $this->getBoardInfo($board['board']);
        }
        
        return $enrichedBoards;
    }
}

