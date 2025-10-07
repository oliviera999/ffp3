<?php
/**
 * Fonctions de gestion des Outputs et Boards - PROD
 * Version sécurisée avec prepared statements
 * Utilise ffp3Outputs (PROD)
 */

require_once __DIR__ . '/autoload.php';

use FFP3Control\Config\Database;

/**
 * Met à jour plusieurs outputs (configuration système)
 * 
 * @param string $mail Email pour notifications
 * @param string $mailNotif Activation notifications (checked/false)
 * @param int $aqThr Limite niveau aquarium
 * @param int $taThr Limite niveau réserve
 * @param int $tempsRemplissageSec Temps remplissage aquarium (secondes)
 * @param int $limFlood Limite débordement
 * @param int $chauff Seuil température chauffage
 * @param int $bouffeMat Heure alimentation matin
 * @param int $bouffeMid Heure alimentation midi
 * @param int $bouffeSoir Heure alimentation soir
 * @param int $tempsGros Durée nourrissage gros poissons (sec)
 * @param int $tempsPetits Durée nourrissage petits poissons (sec)
 * @param int $WakeUp Forçage éveil
 * @param int $FreqWakeUp Fréquence forçage éveil
 * @return string Message de succès ou erreur
 */
function createOutput($mail, $mailNotif, $aqThr, $taThr, $tempsRemplissageSec, $limFlood, $chauff, $bouffeMat, $bouffeMid, $bouffeSoir, $tempsGros, $tempsPetits, $WakeUp, $FreqWakeUp) {
    try {
        $pdo = Database::getConnection();
        $table = Database::getOutputsTable();
        
        // Préparer les mises à jour en une seule transaction
        $pdo->beginTransaction();
        
        $sql = "UPDATE {$table} SET state = :state WHERE gpio = :gpio";
        $stmt = $pdo->prepare($sql);
        
        // Tableau des GPIO à mettre à jour
        $updates = [
            ['gpio' => 100, 'state' => $mail],
            ['gpio' => 101, 'state' => $mailNotif],
            ['gpio' => 102, 'state' => $aqThr],
            ['gpio' => 103, 'state' => $taThr],
            ['gpio' => 113, 'state' => $tempsRemplissageSec],
            ['gpio' => 114, 'state' => $limFlood],
            ['gpio' => 104, 'state' => $chauff],
            ['gpio' => 105, 'state' => $bouffeMat],
            ['gpio' => 106, 'state' => $bouffeMid],
            ['gpio' => 107, 'state' => $bouffeSoir],
            ['gpio' => 111, 'state' => $tempsGros],
            ['gpio' => 112, 'state' => $tempsPetits],
            ['gpio' => 115, 'state' => $WakeUp],
            ['gpio' => 116, 'state' => $FreqWakeUp],
        ];
        
        foreach ($updates as $update) {
            $stmt->execute([
                ':gpio' => $update['gpio'],
                ':state' => $update['state']
            ]);
        }
        
        $pdo->commit();
        return "New output created successfully";
        
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return "Error: " . $e->getMessage();
    }
}

/**
 * Supprime un output
 * 
 * @param int $id ID de l'output
 * @return string Message de succès ou erreur
 */
function deleteOutput($id) {
    try {
        $pdo = Database::getConnection();
        $table = Database::getOutputsTable();
        
        $sql = "DELETE FROM {$table} WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        if ($stmt->rowCount() > 0) {
            return "Output deleted successfully";
        } else {
            return "No output found with ID: {$id}";
        }
        
    } catch (PDOException $e) {
        return "Error: " . $e->getMessage();
    }
}

/**
 * Met à jour l'état d'un output
 * 
 * @param int $id ID de l'output
 * @param int $state Nouvel état (0 ou 1)
 * @return string Message de succès ou erreur
 */
function updateOutput($id, $state) {
    try {
        $pdo = Database::getConnection();
        $table = Database::getOutputsTable();
        
        $sql = "UPDATE {$table} SET state = :state WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':state' => $state,
            ':id' => $id
        ]);
        
        if ($stmt->rowCount() > 0) {
            return "Output state updated successfully";
        } else {
            return "No output found with ID: {$id}";
        }
        
    } catch (PDOException $e) {
        return "Error: " . $e->getMessage();
    }
}

/**
 * Récupère tous les outputs
 * 
 * @return mysqli_result|false Résultat ou false
 */
function getAllOutputs() {
    try {
        $pdo = Database::getConnection();
        $table = Database::getOutputsTable();
        
        $sql = "SELECT id, name, board, gpio, state FROM {$table} ORDER BY id";
        $stmt = $pdo->query($sql);
        
        return $stmt;
        
    } catch (PDOException $e) {
        error_log("getAllOutputs error: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les N premiers outputs
 * 
 * @param int $limit Nombre d'outputs à récupérer (défaut: 7)
 * @return PDOStatement|false Résultat ou false
 */
function getPartOutputs($limit = 7) {
    try {
        $pdo = Database::getConnection();
        $table = Database::getOutputsTable();
        
        $sql = "SELECT id, name, board, gpio, state FROM {$table} ORDER BY id LIMIT :limit";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
        
    } catch (PDOException $e) {
        error_log("getPartOutputs error: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les états de tous les GPIO d'un board
 * Utilisé par ESP32 pour récupérer les états
 * 
 * @param string $board Nom du board
 * @return PDOStatement|false Résultat ou false
 */
function getAllOutputStates($board) {
    try {
        $pdo = Database::getConnection();
        $table = Database::getOutputsTable();
        
        $sql = "SELECT gpio, state FROM {$table} WHERE board = :board";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':board' => $board]);
        
        return $stmt;
        
    } catch (PDOException $e) {
        error_log("getAllOutputStates error: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère le board d'un output par son ID
 * 
 * @param int $id ID de l'output
 * @return PDOStatement|false Résultat ou false
 */
function getOutputBoardById($id) {
    try {
        $pdo = Database::getConnection();
        $table = Database::getOutputsTable();
        
        $sql = "SELECT board FROM {$table} WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        return $stmt;
        
    } catch (PDOException $e) {
        error_log("getOutputBoardById error: " . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour la dernière requête d'un board
 * 
 * @param string $board Nom du board
 * @return string Message de succès ou erreur
 */
function updateLastBoardTime($board) {
    try {
        $pdo = Database::getConnection();
        $table = Database::getBoardsTable();
        
        $sql = "UPDATE {$table} SET last_request = NOW() WHERE board = :board";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':board' => $board]);
        
        if ($stmt->rowCount() > 0) {
            return "Board time updated successfully";
        } else {
            return "No board found: {$board}";
        }
        
    } catch (PDOException $e) {
        return "Error: " . $e->getMessage();
    }
}

/**
 * Récupère tous les boards (utilisé avec un paramètre spécifique)
 * 
 * @param string $board Nom du board
 * @return PDOStatement|false Résultat ou false
 */
function getAllBoards($board) {
    try {
        $pdo = Database::getConnection();
        $table = Database::getBoardsTable();
        
        $sql = "SELECT board, last_request FROM {$table} WHERE board = :board";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':board' => $board]);
        
        return $stmt;
        
    } catch (PDOException $e) {
        error_log("getAllBoards error: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère un board par son nom
 * 
 * @param string $board Nom du board
 * @return PDOStatement|false Résultat ou false
 */
function getBoard($board) {
    try {
        $pdo = Database::getConnection();
        $table = Database::getBoardsTable();
        
        $sql = "SELECT board, last_request FROM {$table} WHERE board = :board";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':board' => $board]);
        
        return $stmt;
        
    } catch (PDOException $e) {
        error_log("getBoard error: " . $e->getMessage());
        return false;
    }
}

/**
 * Crée un nouveau board
 * 
 * @param string $board Nom du board
 * @return string Message de succès ou erreur
 */
function createBoard($board) {
    try {
        $pdo = Database::getConnection();
        $table = Database::getBoardsTable();
        
        $sql = "INSERT INTO {$table} (board) VALUES (:board)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':board' => $board]);
        
        return "New board created successfully";
        
    } catch (PDOException $e) {
        return "Error: " . $e->getMessage();
    }
}

/**
 * Supprime un board
 * 
 * @param string $board Nom du board
 * @return string Message de succès ou erreur
 */
function deleteBoard($board) {
    try {
        $pdo = Database::getConnection();
        $table = Database::getBoardsTable();
        
        $sql = "DELETE FROM {$table} WHERE board = :board";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':board' => $board]);
        
        if ($stmt->rowCount() > 0) {
            return "Board deleted successfully";
        } else {
            return "No board found: {$board}";
        }
        
    } catch (PDOException $e) {
        return "Error: " . $e->getMessage();
    }
}
