<?php
/**
 * Test de la structure des dossiers
 */

echo "=== TEST STRUCTURE DOSSIERS ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

echo "1. Répertoire courant: " . __DIR__ . "\n";
echo "2. Répertoire parent: " . dirname(__DIR__) . "\n\n";

echo "3. Contenu du répertoire courant (public/):\n";
$public_files = scandir(__DIR__);
foreach ($public_files as $file) {
    if ($file !== '.' && $file !== '..') {
        echo "  - $file\n";
    }
}

echo "\n4. Contenu du répertoire parent:\n";
$parent_files = scandir(dirname(__DIR__));
foreach ($parent_files as $file) {
    if ($file !== '.' && $file !== '..') {
        echo "  - $file\n";
    }
}

echo "\n5. Test chemins autoloader:\n";
$paths_to_test = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php'
];

foreach ($paths_to_test as $path) {
    echo "  - $path: " . (file_exists($path) ? "EXISTS" : "NOT FOUND") . "\n";
}

echo "\n6. Test .env:\n";
$env_paths = [
    __DIR__ . '/../.env',
    __DIR__ . '/.env',
    dirname(__DIR__) . '/.env'
];

foreach ($env_paths as $path) {
    echo "  - $path: " . (file_exists($path) ? "EXISTS" : "NOT FOUND") . "\n";
}

echo "\n=== FIN TEST STRUCTURE ===\n";
?>
