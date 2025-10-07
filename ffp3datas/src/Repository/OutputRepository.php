<?php

namespace App\Repository;

use PDO;
use App\Config\TableConfig;

/**
 * Repository pour la gestion des Outputs (GPIO/relais)
 */
class OutputRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère tous les outputs
     * 
     * @return array
     */
    public function getAllOutputs(): array
    {
        $table = TableConfig::getOutputsTable();
        $sql = "SELECT id, name, board, gpio, state FROM {$table} ORDER BY id";
        $stmt = $this->pdo->query($sql);
        
        return $stmt->fetchAll();
    }

    /**
     * Récupère les N premiers outputs (pour affichage interface)
     * 
     * @param int $limit Nombre d'outputs à récupérer
     * @return array
     */
    public function getPartialOutputs(int $limit = 7): array
    {
        $table = TableConfig::getOutputsTable();
        $sql = "SELECT id, name, board, gpio, state FROM {$table} ORDER BY id LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Récupère tous les outputs d'un board spécifique
     * 
     * @param string $board Nom du board
     * @return array
     */
    public function getOutputsByBoard(string $board): array
    {
        $table = TableConfig::getOutputsTable();
        $sql = "SELECT id, name, board, gpio, state FROM {$table} WHERE board = :board ORDER BY id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':board' => $board]);
        
        return $stmt->fetchAll();
    }

    /**
     * Récupère un output par son ID
     * 
     * @param int $id ID de l'output
     * @return array|null
     */
    public function getOutputById(int $id): ?array
    {
        $table = TableConfig::getOutputsTable();
        $sql = "SELECT id, name, board, gpio, state FROM {$table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Récupère les états GPIO d'un board (format pour ESP32)
     * Retourne un tableau associatif [gpio => state]
     * 
     * @param string $board Nom du board
     * @return array Tableau [gpio => state]
     */
    public function getOutputStates(string $board): array
    {
        $table = TableConfig::getOutputsTable();
        $sql = "SELECT gpio, state FROM {$table} WHERE board = :board";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':board' => $board]);
        
        $states = [];
        while ($row = $stmt->fetch()) {
            $states[$row['gpio']] = $row['state'];
        }
        
        return $states;
    }

    /**
     * Met à jour l'état d'un output
     * 
     * @param int $id ID de l'output
     * @param int $state Nouvel état (0 ou 1)
     * @return bool Succès de l'opération
     */
    public function updateOutputState(int $id, int $state): bool
    {
        $table = TableConfig::getOutputsTable();
        $sql = "UPDATE {$table} SET state = :state WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':state' => $state,
            ':id' => $id
        ]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Met à jour plusieurs outputs en une transaction (configuration système)
     * 
     * @param array $updates Tableau de ['gpio' => X, 'state' => Y]
     * @return bool Succès de l'opération
     * @throws \PDOException Si la transaction échoue
     */
    public function updateMultipleOutputs(array $updates): bool
    {
        $table = TableConfig::getOutputsTable();
        
        try {
            $this->pdo->beginTransaction();
            
            $sql = "UPDATE {$table} SET state = :state WHERE gpio = :gpio";
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($updates as $update) {
                $stmt->execute([
                    ':gpio' => $update['gpio'],
                    ':state' => $update['state']
                ]);
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Supprime un output
     * 
     * @param int $id ID de l'output
     * @return bool Succès de l'opération
     */
    public function deleteOutput(int $id): bool
    {
        $table = TableConfig::getOutputsTable();
        $sql = "DELETE FROM {$table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Récupère le board d'un output
     * 
     * @param int $id ID de l'output
     * @return string|null Nom du board ou null
     */
    public function getOutputBoard(int $id): ?string
    {
        $table = TableConfig::getOutputsTable();
        $sql = "SELECT board FROM {$table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $result = $stmt->fetch();
        return $result ? $result['board'] : null;
    }

    /**
     * Récupère la configuration système (GPIO 100-116)
     * 
     * @return array Tableau associatif [gpio => state]
     */
    public function getSystemConfiguration(): array
    {
        $table = TableConfig::getOutputsTable();
        $sql = "SELECT gpio, state FROM {$table} 
                WHERE gpio >= 100 AND gpio <= 116 
                ORDER BY gpio";
        $stmt = $this->pdo->query($sql);
        
        $config = [];
        while ($row = $stmt->fetch()) {
            $config[$row['gpio']] = $row['state'];
        }
        
        return $config;
    }
}

