<?php

declare(strict_types=1);

namespace App\Repository;

use App\Config\TableConfig;
use App\Domain\SensorData;
use App\Util\StateNormalizer;
use PDO;

/**
 * Repository pour gérer les outputs (GPIO/relais) en base de données
 * 
 * Gère la table ffp3Outputs (PROD) ou ffp3Outputs2 (TEST)
 */
class OutputRepository
{
    public function __construct(private PDO $pdo) {}

    /**
     * Valide qu'un nom de table est dans la whitelist autorisée
     * 
     * @param string $tableName Nom de la table
     * @return string Nom de la table validé
     * @throws \InvalidArgumentException Si le nom de table n'est pas autorisé
     */
    private function validateTableName(string $tableName): string
    {
        $allowedTables = ['ffp3Outputs', 'ffp3Outputs2'];
        if (!in_array($tableName, $allowedTables, true)) {
            throw new \InvalidArgumentException("Table name not allowed: {$tableName}");
        }
        return $tableName;
    }

    /**
     * Récupère tous les outputs avec leurs états actuels
     * 
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        $table = $this->validateTableName(TableConfig::getOutputsTable());
        // Filtrer : name NOT NULL et name != '' pour éviter les doublons vides
        // Ordre personnalisé : pompe aquarium, pompe réserve, radiateurs, lumière, nourrissage, reset
        $sql = "SELECT id, board, gpio, name, state 
                FROM `{$table}` 
                WHERE name IS NOT NULL AND name != ''
                ORDER BY 
                    CASE 
                        WHEN name LIKE '%Pompe aquarium%' OR name LIKE '%pompe aquarium%' THEN 1
                        WHEN name LIKE '%Pompe r%serve%' OR name LIKE '%pompe r%serve%' THEN 2
                        WHEN name LIKE '%Radiateur%' OR name LIKE '%radiateur%' THEN 3
                        WHEN name LIKE '%Lumi%re%' OR name LIKE '%lumi%re%' THEN 4
                        WHEN gpio = 101 THEN 5  -- Notifications (switch)
                        WHEN gpio = 115 THEN 6  -- Forçage réveil (switch)
                        WHEN name LIKE '%petits poissons%' THEN 7
                        WHEN name LIKE '%gros poissons%' THEN 8
                        WHEN name LIKE '%reset%' OR name LIKE '%Reset%' THEN 9
                        ELSE 99
                    END,
                    gpio ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Normaliser les valeurs booléennes via StateNormalizer
        return StateNormalizer::normalizeResults($results);
    }

    /**
     * Récupère un output spécifique par son GPIO
     * 
     * @param int $gpio Numéro GPIO
     * @return array<string, mixed>|null
     */
    public function findByGpio(int $gpio): ?array
    {
        $table = $this->validateTableName(TableConfig::getOutputsTable());
        $sql = "SELECT id, board, gpio, name, state FROM `{$table}` WHERE gpio = :gpio";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':gpio' => $gpio]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result === false) {
            return null;
        }
        
        // Normaliser la valeur via StateNormalizer
        $result['state'] = StateNormalizer::normalize($gpio, $result['state']);
        
        return $result;
    }

    /**
     * Met à jour l'état d'un output
     * 
     * @param int $gpio Numéro GPIO
     * @param int $state Nouvel état (0 ou 1)
     * @return bool Succès de l'opération
     */
    public function updateState(int $gpio, int $state): bool
    {
        $table = $this->validateTableName(TableConfig::getOutputsTable());
        $sql = "UPDATE `{$table}` SET state = :state WHERE gpio = :gpio";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':gpio' => $gpio,
            ':state' => $state
        ]);
    }

    /**
     * Récupère tous les GPIO d'une board spécifique avec leurs noms et états
     * 
     * @param string $board Numéro de la board
     * @return array<int, array<string, mixed>>
     */
    public function findByBoard(string $board): array
    {
        $table = $this->validateTableName(TableConfig::getOutputsTable());
        $sql = "SELECT id, board, gpio, name, state 
                FROM `{$table}` 
                WHERE board = :board AND name IS NOT NULL AND name != ''
                ORDER BY gpio ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':board' => $board]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Normaliser les valeurs booléennes via StateNormalizer
        return StateNormalizer::normalizeResults($results);
    }

    /**
     * Récupère la dernière GPIO modifiée d'une board spécifique
     * 
     * @param string $board Numéro de la board
     * @return array<string, mixed>|null
     */
    public function findLastModifiedGpio(string $board): ?array
    {
        $table = $this->validateTableName(TableConfig::getOutputsTable());
        
        // Vérifier si la colonne requestTime existe
        $checkColumnSql = "SHOW COLUMNS FROM `{$table}` LIKE 'requestTime'";
        $checkStmt = $this->pdo->prepare($checkColumnSql);
        $checkStmt->execute();
        $hasRequestTime = $checkStmt->fetch() !== false;
        
        if ($hasRequestTime) {
            $sql = "SELECT id, board, gpio, name, state, 
                           DATE_FORMAT(requestTime, '%d/%m/%Y %H:%i:%s') as last_modified_time
                    FROM `{$table}` 
                    WHERE board = :board AND name IS NOT NULL AND name != '' AND requestTime IS NOT NULL
                    ORDER BY requestTime DESC 
                    LIMIT 1";
        } else {
            // Fallback: utiliser la première GPIO trouvée avec l'heure actuelle
            $sql = "SELECT id, board, gpio, name, state, 
                           DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') as last_modified_time
                    FROM `{$table}` 
                    WHERE board = :board AND name IS NOT NULL AND name != ''
                    ORDER BY gpio ASC 
                    LIMIT 1";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':board' => $board]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result === false) {
            return null;
        }
        
        // Normaliser la valeur via StateNormalizer
        $gpio = (int)$result['gpio'];
        $result['state'] = StateNormalizer::normalize($gpio, $result['state']);
        
        return $result;
    }

    /**
     * Met à jour plusieurs GPIO avec logique de priorité.
     * 
     * Les modifications web ont priorité pendant la durée spécifiée.
     * 
     * @param array<int, mixed> $gpioValues [gpio => value]
     * @param string $modifiedBy Source de la modification ('esp32', 'web', etc.)
     * @param int $prioritySeconds Durée de priorité pour les changements web
     * @return array Statistiques ['updated' => int, 'skipped' => int]
     */
    public function batchUpdateWithPriority(array $gpioValues, string $modifiedBy, int $prioritySeconds): array
    {
        $table = $this->validateTableName(TableConfig::getOutputsTable());
        $updated = 0;
        $skipped = 0;
        
        $this->pdo->beginTransaction();
        
        try {
            foreach ($gpioValues as $gpio => $value) {
                if ($value === null) {
                    $skipped++;
                    continue;
                }
                
                $stateValue = (string)$value;
                
                // Protection contre écrasement des changements web récents
                $sql = "UPDATE `{$table}` 
                        SET state = :state, 
                            requestTime = NOW(), 
                            lastModifiedBy = :modifiedBy
                        WHERE gpio = :gpio 
                          AND name IS NOT NULL 
                          AND name != ''
                          AND (
                              lastModifiedBy != 'web' 
                              OR requestTime IS NULL 
                              OR requestTime < DATE_SUB(NOW(), INTERVAL :priority SECOND)
                          )";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':gpio' => $gpio,
                    ':state' => $stateValue,
                    ':modifiedBy' => $modifiedBy,
                    ':priority' => $prioritySeconds
                ]);
                
                if ($stmt->rowCount() > 0) {
                    $updated++;
                } else {
                    $skipped++;
                }
            }
            
            $this->pdo->commit();
            
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        
        return ['updated' => $updated, 'skipped' => $skipped];
    }

    /**
     * Synchronise les états des GPIO depuis les données capteurs
     * Met à jour ffp3Outputs ou ffp3Outputs2 selon l'environnement
     * 
     * @deprecated Utilisez OutputSyncService::syncFromSensorData() à la place
     * 
     * @param SensorData $data Données capteurs contenant les états à synchroniser
     */
    public function syncStatesFromSensorData(SensorData $data): void
    {
        // Mapping des champs SensorData vers les GPIO
        $gpioUpdates = [
            // Actionneurs physiques
            2 => $data->etatHeat,
            15 => $data->etatUV,
            16 => $data->etatPompeAqua,
            18 => $data->etatPompeTank,
            
            // Configuration
            100 => $data->mail,
            101 => $data->mailNotif,
            102 => $data->aqThreshold,
            103 => $data->tankThreshold,
            104 => $data->chauffageThreshold,
            105 => $data->bouffeMatin,
            106 => $data->bouffeMidi,
            107 => $data->bouffeSoir,
            
            // Commandes nourrissage (flags remis à 0 par ESP32 après exécution)
            108 => $data->bouffePetits,
            109 => $data->bouffeGros,
            
            // Paramètres timing
            111 => $data->tempsGros,
            112 => $data->tempsPetits,
            113 => $data->tempsRemplissageSec,
            114 => $data->limFlood,
            115 => $data->wakeUp,
            116 => $data->freqWakeUp,
        ];
        
        // Déléguer à la nouvelle méthode
        $this->batchUpdateWithPriority($gpioUpdates, 'esp32', 10);
    }
}
