<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\OutputRepository;
use App\Repository\BoardRepository;

/**
 * Service de gestion des outputs (GPIO/relais)
 * 
 * Gère la logique métier pour les contrôles à distance des GPIO
 */
class OutputService
{
    public function __construct(
        private OutputRepository $outputRepository,
        private BoardRepository $boardRepository
    ) {}

    /**
     * Récupère tous les outputs avec leurs états
     * 
     * @return array<int, array<string, mixed>>
     */
    public function getAllOutputs(): array
    {
        return $this->outputRepository->findAll();
    }

    /**
     * Récupère un output spécifique
     * 
     * @param int $gpio Numéro GPIO
     * @return array<string, mixed>|null
     */
    public function getOutput(int $gpio): ?array
    {
        return $this->outputRepository->findByGpio($gpio);
    }

    /**
     * Change l'état d'un output
     * 
     * @param int $gpio Numéro GPIO
     * @param int $state Nouvel état (0 ou 1)
     * @return bool Succès de l'opération
     */
    public function toggleOutput(int $gpio, int $state): bool
    {
        // Validation de l'état
        if ($state !== 0 && $state !== 1) {
            return false;
        }

        return $this->outputRepository->updateState($gpio, $state);
    }

    /**
     * Met à jour la dernière requête d'une board
     * 
     * @param string $board Nom de la board
     * @return bool Succès de l'opération
     */
    public function updateBoardLastRequest(string $board): bool
    {
        // Créer la board si elle n'existe pas
        if (!$this->boardRepository->exists($board)) {
            $this->boardRepository->create($board);
        }

        return $this->boardRepository->updateLastRequest($board);
    }

    /**
     * Récupère toutes les boards
     * 
     * @return array<int, array<string, mixed>>
     */
    public function getAllBoards(): array
    {
        return $this->boardRepository->findAll();
    }

    /**
     * Récupère uniquement les boards actives pour l'environnement actuel
     * 
     * @return array<int, array<string, mixed>>
     */
    public function getActiveBoardsForCurrentEnvironment(): array
    {
        $table = \App\Config\TableConfig::getOutputsTable();
        return $this->boardRepository->findActiveForEnvironment($table);
    }

    /**
     * Récupère tous les GPIO d'une board spécifique avec leurs noms et états
     * 
     * @param string $board Numéro de la board
     * @return array<int, array<string, mixed>>
     */
    public function getBoardGpios(string $board): array
    {
        return $this->outputRepository->findByBoard($board);
    }

    /**
     * Récupère la dernière GPIO modifiée d'une board spécifique
     * 
     * @param string $board Numéro de la board
     * @return array<string, mixed>|null
     */
    public function getLastModifiedGpio(string $board): ?array
    {
        return $this->outputRepository->findLastModifiedGpio($board);
    }

    /**
     * Récupère une board spécifique
     * 
     * @param string $board Nom de la board
     * @return array<string, mixed>|null
     */
    public function getBoard(string $board): ?array
    {
        return $this->boardRepository->findByName($board);
    }

    /**
     * Met à jour l'état d'un output par son ID
     * 
     * @param int $id ID de l'output
     * @param int $state Nouvel état (0 ou 1)
     * @return bool Succès de l'opération
     */
    public function updateStateById(int $id, int $state): bool
    {
        // Validation de l'état
        if ($state !== 0 && $state !== 1) {
            return false;
        }

        $table = \App\Config\TableConfig::getOutputsTable();
        $pdo = \App\Config\Database::getConnection();
        
        // Marquer la modification comme venant de l'interface web
        // Cela donne priorité à cette modification pendant 5 minutes
        $sql = "UPDATE {$table} 
                SET state = :state, 
                    requestTime = NOW(), 
                    lastModifiedBy = 'web'
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        $result = $stmt->execute([
            ':state' => $state, 
            ':id' => $id
        ]);
        
        // Log pour debugging
        if ($result) {
            error_log("Output ID {$id} mis à jour par l'interface web vers state={$state}");
        }
        
        return $result;
    }

    /**
     * Met à jour plusieurs paramètres depuis un formulaire
     * 
     * @param array $params Paramètres à mettre à jour
     * @return int Nombre de paramètres mis à jour
     */
    public function updateMultipleParameters(array $params): int
    {
        // Liste des GPIO spéciaux pour les paramètres
        $parameterMap = [
            'mail' => 100,
            'mailNotif' => 101,
            'aqThr' => 102,
            'taThr' => 103,
            'chauff' => 104,
            'bouffeMat' => 105,
            'bouffeMid' => 106,
            'bouffeSoir' => 107,
            'tempsGros' => 111,
            'tempsPetits' => 112,
            'tempsRemplissageSec' => 113,
            'limFlood' => 114,
            'WakeUp' => 115,
            'FreqWakeUp' => 116,
        ];

        $table = \App\Config\TableConfig::getOutputsTable();
        $pdo = \App\Config\Database::getConnection();
        
        // Transaction pour garantir la cohérence
        $pdo->beginTransaction();
        
        try {
            $updated = 0;
            foreach ($parameterMap as $paramName => $gpio) {
                if (isset($params[$paramName])) {
                    $value = $params[$paramName];
                    
                    // Cas spéciaux
                    if ($paramName === 'mail') {
                        $value = (string)$value;
                    } elseif ($paramName === 'mailNotif') {
                        $value = ($value === 'checked') ? 1 : 0;
                    } else {
                        $value = is_numeric($value) ? (int)$value : 0;
                    }
                    
                    $sql = "UPDATE {$table} 
                            SET state = :state, 
                                requestTime = NOW(), 
                                lastModifiedBy = 'web'
                            WHERE gpio = :gpio";
                    $stmt = $pdo->prepare($sql);
                    if ($stmt->execute([':state' => $value, ':gpio' => $gpio])) {
                        $updated++;
                    }
                }
            }
            
            $pdo->commit();
            
            // Petite pause pour garantir la synchronisation
            usleep(100000);
            
            return $updated;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
