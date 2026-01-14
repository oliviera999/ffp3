<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\OutputRepository;
use App\Repository\BoardRepository;
use App\Service\OutputCacheService;

/**
 * Service de gestion des outputs (GPIO/relais)
 * 
 * Gère la logique métier pour les contrôles à distance des GPIO
 */
class OutputService
{
    public function __construct(
        private OutputRepository $outputRepository,
        private BoardRepository $boardRepository,
        private OutputCacheService $outputCache
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

    public function getParametersMap(): array
    {
        $outputs = $this->outputRepository->findAll();
        $parameters = [];

        $parameterMap = [
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

        foreach ($outputs as $output) {
            $gpio = (int)($output['gpio'] ?? -1);
            $value = $output['state'] ?? null;

            if (isset($parameterMap[$gpio])) {
                $parameters[$parameterMap[$gpio]] = $value;
            }
        }

        return $parameters;
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

        $result = $this->outputRepository->updateState($gpio, $state);
        if ($result) {
            $this->outputCache->invalidateCache();
        }
        return $result;
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
     * Retourne le statut complet d'une board (dernière requête + dernière GPIO modifiée)
     *
     * @param string $board Nom de la board
     * @return array<string, mixed>|null
     */
    public function getBoardStatus(string $board): ?array
    {
        $boardInfo = $this->boardRepository->findByName($board);
        if ($boardInfo === null) {
            return null;
        }

        $lastGpio = $this->outputRepository->findLastModifiedGpio($board);

        return [
            'board' => $boardInfo['board'] ?? $board,
            'last_request' => $boardInfo['last_request'] ?? null,
            'last_gpio' => $lastGpio,
        ];
    }

    /**
     * Met à jour l'état d'un output par son ID
     * 
     * @param int $id ID de l'output
     * @param int $state Nouvel état (0 ou 1)
     * @param string $modifiedBy Source de la modification ('web', 'esp32', etc.)
     * @return bool Succès de l'opération
     */
    public function updateStateById(int $id, int $state, string $modifiedBy = 'web', bool $isTest = false): bool
    {
        // Validation de l'état
        if ($state !== 0 && $state !== 1) {
            return false;
        }

        $table = \App\Config\TableConfig::getOutputsTableFor($isTest ? 'test' : 'prod');
        $pdo = \App\Config\Database::getConnection();
        
        // Marquer la modification avec la source spécifiée
        // Cela permet de gérer la priorité des modifications
        $sql = "UPDATE {$table} 
                SET state = :state, 
                    requestTime = NOW(), 
                    lastModifiedBy = :modifiedBy
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        $result = $stmt->execute([
            ':state' => $state, 
            ':id' => $id,
            ':modifiedBy' => $modifiedBy
        ]);
        
        // Log pour debugging
        if ($result) {
            error_log("Output ID {$id} mis à jour par l'interface web vers state={$state}");
            // Invalider le cache après modification
            $this->outputCache->invalidateCache();
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
                if (array_key_exists($paramName, $params)) {
                    $value = $params[$paramName];
                    
                    // Cas spéciaux
                    if ($paramName === 'mail') {
                        $value = (string)$value;
                    } elseif ($paramName === 'mailNotif') {
                        $value = (is_string($value) && in_array(strtolower($value), ['1', 'true', 'on', 'yes', 'checked'], true))
                            || $value === 1
                            || $value === true
                            ? 1
                            : 0;
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
            
            // Invalider le cache après modification
            $this->outputCache->invalidateCache();
            
            // Petite pause pour garantir la synchronisation
            usleep(100000);
            
            return $updated;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
