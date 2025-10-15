<?php
/**
 * Script de diagnostic des tables TEST
 * 
 * Ce script vérifie l'existence et la structure des tables TEST
 * pour diagnostiquer les erreurs HTTP 500 sur /post-data-test
 * 
 * Usage: php check_test_tables.php
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use App\Config\TableConfig;
use App\Config\Env;

echo "========================================\n";
echo "DIAGNOSTIC TABLES TEST - FFP3\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "========================================\n\n";

// Charger les variables d'environnement
Env::load();

// Afficher la configuration
echo "1. CONFIGURATION ENVIRONNEMENT\n";
echo "==============================\n";
echo "ENV: " . ($_ENV['ENV'] ?? 'NON DÉFINI') . "\n";
echo "TableConfig::getEnvironment(): " . TableConfig::getEnvironment() . "\n";
echo "TableConfig::isTest(): " . (TableConfig::isTest() ? 'true' : 'false') . "\n";
echo "Table données: " . TableConfig::getDataTable() . "\n";
echo "Table outputs: " . TableConfig::getOutputsTable() . "\n";
echo "Table heartbeat: " . TableConfig::getHeartbeatTable() . "\n\n";

try {
    $pdo = Database::getConnection();
    echo "✅ Connexion DB réussie\n\n";
} catch (Exception $e) {
    echo "❌ Erreur connexion DB: " . $e->getMessage() . "\n";
    exit(1);
}

// Vérifier l'existence des tables
echo "2. EXISTENCE DES TABLES\n";
echo "========================\n";

$tablesToCheck = [
    'ffp3Data' => 'PROD - Données capteurs',
    'ffp3Data2' => 'TEST - Données capteurs',
    'ffp3Outputs' => 'PROD - GPIO/relais',
    'ffp3Outputs2' => 'TEST - GPIO/relais',
    'ffp3Heartbeat' => 'PROD - Heartbeat ESP32',
    'ffp3Heartbeat2' => 'TEST - Heartbeat ESP32'
];

foreach ($tablesToCheck as $table => $description) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
    $exists = $stmt->rowCount() > 0;
    
    if ($exists) {
        // Compter les lignes
        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $countStmt->fetch()['count'];
        echo "✅ $table ($description) - $count lignes\n";
    } else {
        echo "❌ $table ($description) - TABLE MANQUANTE\n";
    }
}

echo "\n";

// Vérifier la structure des tables TEST
echo "3. STRUCTURE DES TABLES TEST\n";
echo "=============================\n";

$testTables = ['ffp3Data2', 'ffp3Outputs2', 'ffp3Heartbeat2'];

foreach ($testTables as $table) {
    echo "\n--- Structure de $table ---\n";
    
    try {
        $stmt = $pdo->query("DESCRIBE `$table`");
        $columns = $stmt->fetchAll();
        
        if (empty($columns)) {
            echo "❌ Table vide ou inexistante\n";
            continue;
        }
        
        foreach ($columns as $column) {
            $name = $column['Field'];
            $type = $column['Type'];
            $null = $column['Null'];
            $key = $column['Key'];
            $default = $column['Default'];
            
            echo sprintf("%-20s %-15s %s %s %s\n", 
                $name, 
                $type, 
                $null === 'YES' ? 'NULL' : 'NOT NULL',
                $key ? "($key)" : '',
                $default !== null ? "DEFAULT $default" : ''
            );
        }
        
    } catch (Exception $e) {
        echo "❌ Erreur structure: " . $e->getMessage() . "\n";
    }
}

// Vérifier les GPIO dans ffp3Outputs2
echo "\n4. GPIO DANS ffp3Outputs2\n";
echo "===========================\n";

try {
    $stmt = $pdo->query("SELECT gpio, name, state FROM `ffp3Outputs2` ORDER BY gpio");
    $outputs = $stmt->fetchAll();
    
    if (empty($outputs)) {
        echo "❌ Aucun GPIO dans ffp3Outputs2\n";
    } else {
        echo "GPIO trouvés:\n";
        foreach ($outputs as $output) {
            echo sprintf("GPIO %-3d: %-30s = %s\n", 
                $output['gpio'], 
                $output['name'], 
                $output['state']
            );
        }
    }
} catch (Exception $e) {
    echo "❌ Erreur lecture GPIO: " . $e->getMessage() . "\n";
}

// Comparer les structures PROD vs TEST
echo "\n5. COMPARAISON PROD vs TEST\n";
echo "============================\n";

$comparisons = [
    ['ffp3Data', 'ffp3Data2'],
    ['ffp3Outputs', 'ffp3Outputs2']
];

foreach ($comparisons as [$prodTable, $testTable]) {
    echo "\n--- Comparaison $prodTable vs $testTable ---\n";
    
    try {
        // Structure PROD
        $prodStmt = $pdo->query("DESCRIBE `$prodTable`");
        $prodColumns = $prodStmt->fetchAll();
        $prodColumnNames = array_column($prodColumns, 'Field');
        
        // Structure TEST
        $testStmt = $pdo->query("DESCRIBE `$testTable`");
        $testColumns = $testStmt->fetchAll();
        $testColumnNames = array_column($testColumns, 'Field');
        
        // Colonnes manquantes dans TEST
        $missingInTest = array_diff($prodColumnNames, $testColumnNames);
        if (!empty($missingInTest)) {
            echo "❌ Colonnes manquantes dans $testTable: " . implode(', ', $missingInTest) . "\n";
        } else {
            echo "✅ Structure identique\n";
        }
        
        // Colonnes supplémentaires dans TEST
        $extraInTest = array_diff($testColumnNames, $prodColumnNames);
        if (!empty($extraInTest)) {
            echo "⚠️  Colonnes supplémentaires dans $testTable: " . implode(', ', $extraInTest) . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Erreur comparaison: " . $e->getMessage() . "\n";
    }
}

// Test d'insertion simulée
echo "\n6. TEST D'INSERTION SIMULÉE\n";
echo "============================\n";

try {
    $dataTable = TableConfig::getDataTable();
    echo "Table cible pour insertion: $dataTable\n";
    
    // Vérifier que la table existe
    $stmt = $pdo->query("SHOW TABLES LIKE '$dataTable'");
    if ($stmt->rowCount() === 0) {
        echo "❌ Table $dataTable n'existe pas!\n";
    } else {
        echo "✅ Table $dataTable existe\n";
        
        // Tester une insertion avec des valeurs NULL (simulation)
        $testSql = "INSERT INTO `$dataTable` (
            sensor, version, TempAir, Humidite, TempEau, EauPotager, EauAquarium, EauReserve,
            diffMaree, Luminosite, etatPompeAqua, etatPompeTank, etatHeat, etatUV,
            bouffeMatin, bouffeMidi, bouffePetits, bouffeGros,
            aqThreshold, tankThreshold, chauffageThreshold, mail, mailNotif, resetMode, bouffeSoir
        ) VALUES (
            'test', '11.35', NULL, NULL, NULL, NULL, NULL, NULL,
            NULL, NULL, NULL, NULL, NULL, NULL,
            NULL, NULL, NULL, NULL,
            NULL, NULL, NULL, NULL, NULL, NULL, NULL
        )";
        
        $stmt = $pdo->prepare($testSql);
        $stmt->execute();
        $insertId = $pdo->lastInsertId();
        
        echo "✅ Test insertion réussi (ID: $insertId)\n";
        
        // Supprimer l'enregistrement de test
        $pdo->exec("DELETE FROM `$dataTable` WHERE id = $insertId");
        echo "✅ Enregistrement de test supprimé\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur test insertion: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
echo "DIAGNOSTIC TERMINÉ\n";
echo "========================================\n";
