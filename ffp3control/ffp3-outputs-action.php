<?php
/**
 * API REST pour la gestion des Outputs - PROD
 * Version sécurisée avec validation des entrées
 */

require_once __DIR__ . '/ffp3-database.php';

/**
 * Valide et nettoie les entrées utilisateur
 * 
 * @param mixed $data Donnée à nettoyer
 * @return string Donnée nettoyée
 */
function test_input($data) {
    if ($data === null) {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Valide qu'une variable est un entier positif
 * 
 * @param mixed $value Valeur à valider
 * @param int $default Valeur par défaut
 * @return int Valeur validée
 */
function validate_positive_int($value, $default = 0) {
    $val = filter_var($value, FILTER_VALIDATE_INT);
    return ($val !== false && $val >= 0) ? $val : $default;
}

/**
 * Log les actions critiques
 * 
 * @param string $action Action effectuée
 * @param array $data Données associées
 */
function logAction($action, $data = []) {
    $logFile = __DIR__ . '/actions.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $logEntry = sprintf(
        "[%s] IP:%s Action:%s Data:%s\n",
        $timestamp,
        $ip,
        $action,
        json_encode($data)
    );
    error_log($logEntry, 3, $logFile);
}

// Initialisation des variables
$action = $mail = $mailNotif = $aqThr = $taThr = $tempsRemplissageSec = '';
$limFlood = $chauff = $bouffeMat = $bouffeMid = $bouffeSoir = '';
$tempsGros = $tempsPetits = $WakeUp = $FreqWakeUp = '';

// Gestion des requêtes POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = test_input($_POST["action"] ?? '');
    
    if ($action == "output_create") {
        // Validation et nettoyage de toutes les entrées
        $mail = test_input($_POST["mail"] ?? '');
        $mailNotif = test_input($_POST["mailNotif"] ?? '');
        
        // Validation des valeurs numériques
        $aqThr = validate_positive_int($_POST["aqThr"] ?? 0);
        $taThr = validate_positive_int($_POST["taThr"] ?? 0);
        $tempsRemplissageSec = validate_positive_int($_POST["tempsRemplissageSec"] ?? 0);
        $limFlood = validate_positive_int($_POST["limFlood"] ?? 0);
        $chauff = validate_positive_int($_POST["chauff"] ?? 0);
        $bouffeMat = validate_positive_int($_POST["bouffeMat"] ?? 0);
        $bouffeMid = validate_positive_int($_POST["bouffeMid"] ?? 0);
        $bouffeSoir = validate_positive_int($_POST["bouffeSoir"] ?? 0);
        $tempsGros = validate_positive_int($_POST["tempsGros"] ?? 0);
        $tempsPetits = validate_positive_int($_POST["tempsPetits"] ?? 0);
        $WakeUp = validate_positive_int($_POST["WakeUp"] ?? 0);
        $FreqWakeUp = validate_positive_int($_POST["FreqWakeUp"] ?? 0);
        
        // Log de l'action
        logAction('output_create', [
            'mail' => $mail,
            'aqThr' => $aqThr,
            'taThr' => $taThr
        ]);
        
        $result = createOutput(
            $mail, $mailNotif, $aqThr, $taThr, $tempsRemplissageSec, 
            $limFlood, $chauff, $bouffeMat, $bouffeMid, $bouffeSoir, 
            $tempsGros, $tempsPetits, $WakeUp, $FreqWakeUp
        );
        
        echo $result;
    } else {
        http_response_code(400);
        echo "No data posted with HTTP POST.";
    }
    exit;
}

// Gestion des requêtes GET
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $action = test_input($_GET["action"] ?? '');
    
    if ($action == "outputs_state") {
        // Récupération des états pour l'ESP32
        $board = test_input($_GET["board"] ?? '');
        
        if (empty($board)) {
            http_response_code(400);
            echo json_encode(['error' => 'Board parameter missing']);
            exit;
        }
        
        $result = getAllOutputStates($board);
        $rows = [];
        
        if ($result) {
            while ($row = $result->fetch()) {
                $rows[$row["gpio"]] = $row["state"];
            }
        }
        
        // Log de la requête
        logAction('outputs_state', ['board' => $board]);
        
        // Mise à jour du timestamp du board
        $boardResult = getBoard($board);
        if ($boardResult && $boardResult->fetch()) {
            updateLastBoardTime($board);
        }
        
        header('Content-Type: application/json');
        echo json_encode($rows);
        
    } else if ($action == "output_update") {
        // Mise à jour d'un output
        $id = validate_positive_int($_GET["id"] ?? 0);
        $state = validate_positive_int($_GET["state"] ?? 0);
        
        if ($id === 0) {
            http_response_code(400);
            echo "Invalid ID";
            exit;
        }
        
        if ($state !== 0 && $state !== 1) {
            http_response_code(400);
            echo "Invalid state (must be 0 or 1)";
            exit;
        }
        
        logAction('output_update', ['id' => $id, 'state' => $state]);
        
        $result = updateOutput($id, $state);
        echo $result;
        
    } else if ($action == "output_delete") {
        // Suppression d'un output
        $id = validate_positive_int($_GET["id"] ?? 0);
        
        if ($id === 0) {
            http_response_code(400);
            echo "Invalid ID";
            exit;
        }
        
        // Récupérer le board avant suppression
        $board = getOutputBoardById($id);
        $board_id = '';
        if ($board && $row = $board->fetch()) {
            $board_id = $row["board"];
        }
        
        logAction('output_delete', ['id' => $id, 'board' => $board_id]);
        
        $result = deleteOutput($id);
        
        // Vérifier s'il reste des outputs pour ce board
        if (!empty($board_id)) {
            $result2 = getAllOutputStates($board_id);
            if ($result2 && !$result2->fetch()) {
                deleteBoard($board_id);
            }
        }
        
        echo $result;
        
    } else {
        http_response_code(400);
        echo "Invalid HTTP request.";
    }
    exit;
}

// Méthode non autorisée
http_response_code(405);
echo "Method not allowed";
