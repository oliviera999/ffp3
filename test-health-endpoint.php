<?php
/**
 * Test simple pour vérifier que l'endpoint de santé fonctionne
 */

echo "=== Test de l'endpoint de santé ===\n\n";

// Test des URLs
$baseUrl = 'https://iot.olution.info/ffp3';
$endpoints = [
    '/api/health',
    '/api/realtime/system/health',
    '/api/health-test',
    '/api/realtime-test/system/health'
];

foreach ($endpoints as $endpoint) {
    $url = $baseUrl . $endpoint;
    echo "Test de: $url\n";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Accept: application/json',
            'timeout' => 10
        ]
    ]);
    
    $result = @file_get_contents($url, false, $context);
    
    if ($result === false) {
        echo "  ❌ ERREUR: Impossible d'accéder à l'endpoint\n";
    } else {
        $httpCode = 0;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (strpos($header, 'HTTP/') === 0) {
                    $httpCode = (int)substr($header, 9, 3);
                    break;
                }
            }
        }
        
        if ($httpCode === 200) {
            $data = json_decode($result, true);
            if ($data) {
                echo "  ✅ SUCCÈS: HTTP $httpCode - Données JSON reçues\n";
                echo "  📊 Contenu: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
            } else {
                echo "  ⚠️  HTTP $httpCode mais contenu non-JSON valide\n";
                echo "  📄 Contenu: " . substr($result, 0, 200) . "...\n";
            }
        } else {
            echo "  ❌ ERREUR: HTTP $httpCode\n";
            echo "  📄 Contenu: " . substr($result, 0, 200) . "...\n";
        }
    }
    
    echo "\n";
}

echo "=== Fin des tests ===\n";
