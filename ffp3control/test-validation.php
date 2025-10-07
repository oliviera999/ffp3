<?php
/**
 * Script de validation - Phase 1
 * Vérifie que la configuration est correcte
 */

echo "=== Tests de validation Phase 1 ===\n\n";

$errors = [];
$warnings = [];

// Test 1 : Fichiers de configuration
echo "1. Vérification fichiers de configuration...\n";
$requiredFiles = [
    '.env',
    'env.dist',
    'config/Database.php',
    'autoload.php',
    'README.md'
];

foreach ($requiredFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "   ✓ {$file} existe\n";
    } else {
        $errors[] = "Fichier manquant: {$file}";
        echo "   ✗ {$file} MANQUANT\n";
    }
}

// Test 2 : Chargement autoloader
echo "\n2. Test chargement autoloader...\n";
try {
    require_once __DIR__ . '/autoload.php';
    echo "   ✓ Autoloader chargé\n";
} catch (Exception $e) {
    $errors[] = "Erreur autoloader: " . $e->getMessage();
    echo "   ✗ Erreur: " . $e->getMessage() . "\n";
}

// Test 3 : Classe Database accessible
echo "\n3. Test classe Database...\n";
try {
    if (class_exists('FFP3Control\\Config\\Database')) {
        echo "   ✓ Classe Database accessible\n";
        
        // Test méthodes statiques
        $methods = ['getOutputsTable', 'getBoardsTable'];
        foreach ($methods as $method) {
            if (method_exists('FFP3Control\\Config\\Database', $method)) {
                echo "   ✓ Méthode {$method}() existe\n";
            } else {
                $errors[] = "Méthode Database::{$method}() manquante";
            }
        }
    } else {
        $errors[] = "Classe Database non accessible";
        echo "   ✗ Classe Database introuvable\n";
    }
} catch (Exception $e) {
    $errors[] = "Erreur classe Database: " . $e->getMessage();
    echo "   ✗ Erreur: " . $e->getMessage() . "\n";
}

// Test 4 : Variables d'environnement
echo "\n4. Test variables d'environnement...\n";
$requiredEnvVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'ENV'];
foreach ($requiredEnvVars as $var) {
    if (isset($_ENV[$var]) && $_ENV[$var] !== '') {
        echo "   ✓ {$var} définie\n";
    } else {
        $warnings[] = "Variable {$var} non définie (normal si pas encore chargée)";
        echo "   ⚠ {$var} non définie\n";
    }
}

// Test 5 : Fichiers legacy présents (pas modifiés pour l'instant)
echo "\n5. Vérification fichiers legacy...\n";
$legacyFiles = [
    'ffp3-database.php',
    'ffp3-database2.php',
    'ffp3-outputs-action.php',
    'ffp3-outputs-action2.php',
    'securecontrol/ffp3-outputs.php',
    'securecontrol/ffp3-outputs2.php'
];

foreach ($legacyFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "   ✓ {$file} présent\n";
    } else {
        $warnings[] = "Fichier legacy absent: {$file}";
        echo "   ⚠ {$file} absent\n";
    }
}

// Résumé
echo "\n=== RÉSUMÉ ===\n";
if (count($errors) === 0) {
    echo "✓ Tous les tests passent\n";
} else {
    echo "✗ " . count($errors) . " erreur(s) détectée(s):\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
}

if (count($warnings) > 0) {
    echo "\n⚠ " . count($warnings) . " avertissement(s):\n";
    foreach ($warnings as $warning) {
        echo "  - {$warning}\n";
    }
}

echo "\n=== Phase 1 : " . (count($errors) === 0 ? "VALIDÉE ✓" : "ÉCHOUÉE ✗") . " ===\n";

exit(count($errors) === 0 ? 0 : 1);

