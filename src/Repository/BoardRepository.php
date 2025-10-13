<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

/**
 * Repository pour gérer les boards (cartes ESP32) en base de données
 * 
 * Note: La table Boards est partagée entre PROD et TEST (pas de Boards2)
 */
class BoardRepository
{
    public function __construct(private PDO $pdo) {}

    /**
     * Récupère toutes les boards
     * 
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        $sql = "SELECT board, DATE_FORMAT(last_request, '%d/%m/%Y %H:%i:%s') as last_request 
                FROM Boards 
                ORDER BY board ASC";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère uniquement les boards actives pour un environnement donné
     * Une board est considérée active si elle a des outputs dans la table correspondante
     * 
     * @param string $outputsTable Nom de la table outputs (ffp3Outputs ou ffp3Outputs2)
     * @return array<int, array<string, mixed>>
     */
    public function findActiveForEnvironment(string $outputsTable): array
    {
        $sql = "SELECT DISTINCT b.board, DATE_FORMAT(b.last_request, '%d/%m/%Y %H:%i:%s') as last_request 
                FROM Boards b
                INNER JOIN {$outputsTable} o ON b.board = o.board
                WHERE o.name IS NOT NULL AND o.name != ''
                ORDER BY b.board ASC";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère une board spécifique par son nom
     * 
     * @param string $board Nom de la board
     * @return array<string, mixed>|null
     */
    public function findByName(string $board): ?array
    {
        $sql = "SELECT board, DATE_FORMAT(last_request, '%d/%m/%Y %H:%i:%s') as last_request 
                FROM Boards 
                WHERE board = :board";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':board' => $board]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : null;
    }

    /**
     * Met à jour la dernière requête d'une board
     * 
     * @param string $board Nom de la board
     * @return bool Succès de l'opération
     */
    public function updateLastRequest(string $board): bool
    {
        $sql = "UPDATE Boards SET last_request = NOW() WHERE board = :board";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':board' => $board]);
    }

    /**
     * Crée une nouvelle board
     * 
     * @param string $board Nom de la board
     * @return bool Succès de l'opération
     */
    public function create(string $board): bool
    {
        $sql = "INSERT INTO Boards (board) VALUES (:board)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':board' => $board]);
    }

    /**
     * Supprime une board
     * 
     * @param string $board Nom de la board
     * @return bool Succès de l'opération
     */
    public function delete(string $board): bool
    {
        $sql = "DELETE FROM Boards WHERE board = :board";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':board' => $board]);
    }

    /**
     * Vérifie si une board existe
     * 
     * @param string $board Nom de la board
     * @return bool
     */
    public function exists(string $board): bool
    {
        return $this->findByName($board) !== null;
    }
}

