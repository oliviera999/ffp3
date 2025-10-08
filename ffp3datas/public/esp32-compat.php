<?php
/**
 * Proxy de compatibilité pour ESP32
 * 
 * Les ESP32 utilisent encore les anciennes URLs de ffp3control.
 * Ce fichier redirige vers les nouvelles API modernes.
 * 
 * ANCIEN: /ffp3/ffp3control/ffp3-outputs-action.php?action=outputs_state&board=1
 * NOUVEAU: /ffp3/ffp3datas/api/outputs/states/1
 */

// Déterminer le chemin base (sans /public/ car Slim gère le routing)
$basePath = '/ffp3/ffp3datas';

// Récupérer l'action
$action = $_GET['action'] ?? '';

if ($action === 'outputs_state') {
    // Redirection vers API états GPIO
    $board = $_GET['board'] ?? '1';
    $newUrl = $basePath . '/api/outputs/states/' . urlencode($board);
    header('Location: ' . $newUrl);
    exit;
    
} elseif ($action === 'output_update') {
    // Redirection vers API update output
    $id = $_GET['id'] ?? '';
    $state = $_GET['state'] ?? '';
    
    if ($id && $state !== '') {
        // Transformer GET en POST avec JSON
        $ch = curl_init($basePath . '/api/outputs/' . urlencode($id) . '/state');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['state' => (int) $state]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        http_response_code($httpCode);
        echo $result;
        exit;
    }
    
} elseif ($action === 'output_delete') {
    // Redirection vers API delete output
    $id = $_GET['id'] ?? '';
    
    if ($id) {
        $ch = curl_init($basePath . '/api/outputs/' . urlencode($id));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        http_response_code($httpCode);
        echo $result;
        exit;
    }
}

// Action non reconnue ou paramètres manquants
http_response_code(400);
echo json_encode(['error' => 'Invalid action or missing parameters']);

