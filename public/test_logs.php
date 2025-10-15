<?php
/**
 * Test des logs PHP pour diagnostiquer l'erreur 500
 */

echo "=== TEST LOGS PHP ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Test 1: Vérifier les logs PHP
echo "1. Configuration PHP:\n";
echo "  - display_errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "\n";
echo "  - log_errors: " . (ini_get('log_errors') ? 'ON' : 'OFF') . "\n";
echo "  - error_log: " . ini_get('error_log') . "\n";
echo "  - error_reporting: " . ini_get('error_reporting') . "\n\n";

// Test 2: Tester les logs d'erreur
echo "2. Test des logs d'erreur:\n";
error_log("Test log message - " . date('Y-m-d H:i:s'));
echo "  - Message de test envoyé aux logs\n";

// Test 3: Vérifier les dossiers de logs
echo "3. Dossiers de logs:\n";
$log_dirs = [
    '/var/log/apache2/error.log',
    '/var/log/php_errors.log',
    '/var/log/php/error.log',
    __DIR__ . '/../var/log/php_errors.log',
    ini_get('error_log')
];

foreach ($log_dirs as $log_file) {
    if (file_exists($log_file)) {
        echo "  ✅ $log_file: EXISTS (" . filesize($log_file) . " bytes)\n";
    } else {
        echo "  ❌ $log_file: NOT FOUND\n";
    }
}

// Test 4: Tester une erreur volontaire pour voir si elle est loggée
echo "\n4. Test d'erreur volontaire:\n";
try {
    // Erreur volontaire
    $undefined_variable = $this_does_not_exist;
} catch (Error $e) {
    echo "  - Erreur capturée: " . $e->getMessage() . "\n";
    error_log("Erreur de test: " . $e->getMessage());
}

// Test 5: Informations serveur
echo "\n5. Informations serveur:\n";
echo "  - Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "  - Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "  - Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "  - Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";

echo "\n=== FIN TEST LOGS ===\n";
?>
