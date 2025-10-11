<?php

declare(strict_types=1);

namespace App\Repository;

use App\Config\TableConfig;
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
     * Récupère tous les outputs avec leurs états actuels
     * 
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        $table = TableConfig::getOutputsTable();
        // Filtrer : name NOT NULL et name != '' pour éviter les doublons vides
        // Ordre personnalisé : pompe aquarium, pompe réserve, radiateurs, lumière, nourrissage, reset
        $sql = "SELECT id, board, gpio, name, state 
                FROM {$table} 
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
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un output spécifique par son GPIO
     * 
     * @param int $gpio Numéro GPIO
     * @return array<string, mixed>|null
     */
    public function findByGpio(int $gpio): ?array
    {
        $table = TableConfig::getOutputsTable();
        $sql = "SELECT id, board, gpio, name, state FROM {$table} WHERE gpio = :gpio";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':gpio' => $gpio]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : null;
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
        $table = TableConfig::getOutputsTable();
        $sql = "UPDATE {$table} SET state = :state WHERE gpio = :gpio";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':gpio' => $gpio,
            ':state' => $state
        ]);
    }

    /**
     * Récupère l'état actuel d'un output
     * 
     * @param int $gpio Numéro GPIO
     * @return int|null État (0 ou 1), ou null si non trouvé
     */
    public function getState(int $gpio): ?int
    {
        $output = $this->findByGpio($gpio);
        return $output !== null ? (int)$output['state'] : null;
    }
}

