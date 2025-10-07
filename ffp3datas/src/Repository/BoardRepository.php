<?php

namespace App\Repository;

use PDO;

/**
 * Repository pour la gestion des Boards (ESP32/microcontrôleurs)
 */
class BoardRepository
{
    private PDO $pdo;
    private string $table = 'Boards'; // Table partagée PROD/TEST

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère un board par son nom
     * 
     * @param string $board Nom du board
     * @return array|null
     */
    public function getBoardByName(string $board): ?array
    {
        $sql = "SELECT board, last_request FROM {$this->table} WHERE board = :board";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':board' => $board]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Récupère tous les boards
     * 
     * @return array
     */
    public function getAllBoards(): array
    {
        $sql = "SELECT board, last_request FROM {$this->table} ORDER BY board";
        $stmt = $this->pdo->query($sql);
        
        return $stmt->fetchAll();
    }

    /**
     * Met à jour la dernière requête d'un board
     * 
     * @param string $board Nom du board
     * @return bool Succès de l'opération
     */
    public function updateLastRequest(string $board): bool
    {
        $sql = "UPDATE {$this->table} SET last_request = NOW() WHERE board = :board";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':board' => $board]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Crée un nouveau board
     * 
     * @param string $board Nom du board
     * @return bool Succès de l'opération
     */
    public function createBoard(string $board): bool
    {
        try {
            $sql = "INSERT INTO {$this->table} (board) VALUES (:board)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':board' => $board]);
            
            return true;
        } catch (\PDOException $e) {
            // Board existe déjà (duplicate key)
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }

    /**
     * Supprime un board
     * 
     * @param string $board Nom du board
     * @return bool Succès de l'opération
     */
    public function deleteBoard(string $board): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE board = :board";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':board' => $board]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Vérifie si un board existe
     * 
     * @param string $board Nom du board
     * @return bool
     */
    public function boardExists(string $board): bool
    {
        return $this->getBoardByName($board) !== null;
    }

    /**
     * Récupère les boards inactifs depuis X minutes
     * 
     * @param int $minutes Durée d'inactivité en minutes
     * @return array
     */
    public function getInactiveBoards(int $minutes = 15): array
    {
        $sql = "SELECT board, last_request 
                FROM {$this->table} 
                WHERE last_request < DATE_SUB(NOW(), INTERVAL :minutes MINUTE)
                ORDER BY last_request DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':minutes' => $minutes]);
        
        return $stmt->fetchAll();
    }
}

