<?php
/**
 * Script de test des endpoints FFP3
 * À exécuter pour vérifier le statut de tous les endpoints
 */

echo "🧪 TEST DES ENDPOINTS FFP3\n";
echo "===========================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

$baseUrl = 'https://iot.olution.info/ffp3';
$endpoints = [
    'Control PROD' => '/control',
    'Control TEST' => '/control-test', 
    'API Sensors' => '/api/sensors',
    'API Outputs' => '/api/outputs',
    'API System Health' => '/api/system-health',
    'Dashboard' => '/',
    'Post Data' => '/post-data'
];

$results = [];
$totalErrors = 0;

echo "📡 Test des endpoints...\n";
echo "------------------------\n";

foreach ($endpoints as $name => $path) {
    $url = $baseUrl . $path;
    echo "Test $name: ";
    
    // Initialiser cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'FFP3-Diagnostic/1.0');
    
    // Exécuter la requête
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Analyser le résultat
    if ($error) {
        echo "❌ ERREUR CURL: $error\n";
        $results[$name] = ['status' => 'error', 'code' => 'CURL_ERROR', 'message' => $error];
        $totalErrors++;
    } elseif ($httpCode == 200) {
        echo "✅ OK (HTTP $httpCode)\n";
        $results[$name] = ['status' => 'success', 'code' => $httpCode, 'message' => 'OK'];
    } else {
        echo "❌ ERREUR (HTTP $httpCode)\n";
        $results[$name] = ['status' => 'error', 'code' => $httpCode, 'message' => 'HTTP Error'];
        $totalErrors++;
        
        // Afficher un extrait de la réponse pour les erreurs 500
        if ($httpCode == 500 && $response) {
            $preview = substr(strip_tags($response), 0, 200);
            echo "   💬 Aperçu: " . trim($preview) . "...\n";
        }
    }
}

echo "\n📊 RÉSULTATS\n";
echo "=============\n";
echo "Total des tests: " . count($endpoints) . "\n";
echo "Succès: " . (count($endpoints) - $totalErrors) . "\n";
echo "Erreurs: $totalErrors\n";

if ($totalErrors > 0) {
    echo "\n❌ ENDPOINTS EN ERREUR:\n";
    foreach ($results as $name => $result) {
        if ($result['status'] === 'error') {
            echo "   - $name: {$result['code']} - {$result['message']}\n";
        }
    }
    
    echo "\n🎯 ACTIONS RECOMMANDÉES:\n";
    echo "1. Exécuter le diagnostic complet: php diagnostic-complet.php\n";
    echo "2. Appliquer les corrections: bash fix-http500.sh\n";
    echo "3. Consulter les logs Apache: tail -f /var/log/apache2/error.log\n";
    echo "4. Vérifier les logs PHP: tail -f var/log/php_errors.log\n";
    echo "5. Redémarrer Apache: sudo systemctl restart apache2\n";
} else {
    echo "\n✅ TOUS LES ENDPOINTS FONCTIONNENT CORRECTEMENT !\n";
}

echo "\n📝 LOGS À CONSULTER:\n";
echo "- /var/log/apache2/error.log\n";
echo "- var/log/php_errors.log\n";
echo "- var/cache/ (nettoyer si nécessaire)\n";

// Sauvegarder les résultats
$logFile = 'var/log/endpoint-test-' . date('Y-m-d-H-i-s') . '.log';
file_put_contents($logFile, json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'results' => $results,
    'total_errors' => $totalErrors
], JSON_PRETTY_PRINT));

echo "\n💾 Résultats sauvegardés dans: $logFile\n";
