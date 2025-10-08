<?php
/**
 * Script de test des endpoints ESP32
 * 
 * Ce script teste tous les endpoints utilisés par l'ESP32 pour vérifier
 * qu'ils répondent correctement.
 * 
 * Usage: php test_endpoints_esp32.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$baseUrl = 'http://iot.olution.info/ffp3/ffp3datas';
$board = '1';

echo "=====================================\n";
echo "TEST DES ENDPOINTS ESP32\n";
echo "=====================================\n";
echo "Base URL: $baseUrl\n";
echo "Board: $board\n\n";

// Fonction helper pour tester un endpoint
function testEndpoint($name, $url, $method = 'GET', $data = null, $contentType = 'application/x-www-form-urlencoded') {
    echo "-----------------------------------\n";
    echo "TEST: $name\n";
    echo "URL: $url\n";
    echo "Method: $method\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Suivre les redirections
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            if ($contentType === 'application/json') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    
    if ($error) {
        echo "❌ ERREUR CURL: $error\n";
        return false;
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        echo "✅ SUCCÈS\n";
        echo "Response: " . substr($response, 0, 200) . (strlen($response) > 200 ? '...' : '') . "\n";
        return true;
    } else {
        echo "❌ ÉCHEC\n";
        echo "Response: $response\n";
        return false;
    }
}

// =============================================================================
// TEST 1: Récupération des états GPIO (endpoint principal ESP32)
// =============================================================================
$success1 = testEndpoint(
    "GET Output States (Production)",
    "$baseUrl/api/outputs/states/$board",
    'GET'
);

// =============================================================================
// TEST 2: Récupération des états GPIO (endpoint TEST)
// =============================================================================
$success2 = testEndpoint(
    "GET Output States (Test)",
    "$baseUrl/api/outputs-test/states/$board",
    'GET'
);

// =============================================================================
// TEST 3: POST données capteurs (sans API key pour voir l'erreur attendue)
// =============================================================================
$testData = [
    'sensor' => 'TEST_SCRIPT',
    'version' => '10.52',
    'TempAir' => '22.5',
    'Humidite' => '65.0',
    'TempEau' => '18.3',
    'EauPotager' => '50',
    'EauAquarium' => '45',
    'EauReserve' => '80',
    'diffMaree' => '2',
    'Luminosite' => '150',
    'etatPompeAqua' => '0',
    'etatPompeTank' => '0',
    'etatHeat' => '1',
    'etatUV' => '0',
    'bouffeMatin' => '8',
    'bouffeMidi' => '12',
    'bouffeSoir' => '18',
    'resetMode' => '0'
];

echo "\n⚠️  Note: Le test suivant devrait ÉCHOUER (401) car pas d'API key fournie\n";
$success3 = testEndpoint(
    "POST Data (sans API key - devrait échouer)",
    "$baseUrl/post-data",
    'POST',
    $testData
);

// =============================================================================
// TEST 4: Ancien endpoint de compatibilité (esp32-compat.php)
// =============================================================================
$success4 = testEndpoint(
    "Legacy endpoint - outputs_state (esp32-compat.php)",
    "$baseUrl/public/esp32-compat.php?action=outputs_state&board=$board",
    'GET'
);

// =============================================================================
// TEST 5: Vérifier que le routage Slim fonctionne
// =============================================================================
$success5 = testEndpoint(
    "Dashboard (test routing Slim)",
    "$baseUrl/dashboard",
    'GET'
);

// =============================================================================
// RÉSUMÉ
// =============================================================================
echo "\n=====================================\n";
echo "RÉSUMÉ DES TESTS\n";
echo "=====================================\n";
echo "Test 1 (Output States PROD): " . ($success1 ? "✅ OK" : "❌ ÉCHEC") . "\n";
echo "Test 2 (Output States TEST): " . ($success2 ? "✅ OK" : "❌ ÉCHEC") . "\n";
echo "Test 3 (POST Data sans key): " . (!$success3 ? "✅ OK (erreur attendue)" : "⚠️  Inattendu") . "\n";
echo "Test 4 (Legacy compat): " . ($success4 ? "✅ OK" : "❌ ÉCHEC") . "\n";
echo "Test 5 (Routing Slim): " . ($success5 ? "✅ OK" : "❌ ÉCHEC") . "\n";

$totalSuccess = ($success1 ? 1 : 0) + ($success2 ? 1 : 0) + (!$success3 ? 1 : 0) + ($success4 ? 1 : 0) + ($success5 ? 1 : 0);
echo "\nScore: $totalSuccess/5\n";

if ($totalSuccess === 5) {
    echo "\n🎉 TOUS LES TESTS ONT RÉUSSI!\n";
} else {
    echo "\n⚠️  Certains tests ont échoué. Vérifiez la configuration.\n";
}

echo "\n=====================================\n";
echo "INSTRUCTIONS\n";
echo "=====================================\n";
echo "Si les tests échouent:\n";
echo "1. Vérifiez que le serveur est accessible: $baseUrl\n";
echo "2. Vérifiez les logs Apache/Nginx\n";
echo "3. Vérifiez que le fichier .htaccess redirige vers public/index.php\n";
echo "4. Activez le debug dans public/index.php (ligne 33)\n";
echo "5. Vérifiez les permissions des fichiers\n";
echo "\nPour tester avec une vraie API key:\n";
echo "Modifiez la ligne \$testData et ajoutez: 'api_key' => 'VOTRE_CLE'\n";

