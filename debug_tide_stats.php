<?php
// Script de debug pour tide_stats
require __DIR__ . '/vendor/autoload.php';

use App\Config\Database;
use App\Repository\SensorReadRepository;
use App\Service\TideAnalysisService;

try {
    echo "=== DEBUG TIDE STATS ===\n";
    
    // Connexion à la base de données
    $pdo = Database::getConnection();
    echo "✓ Connexion DB réussie\n";
    
    // Test du repository
    $repo = new SensorReadRepository($pdo);
    echo "✓ Repository créé\n";
    
    // Test récupération des dernières données
    $lastData = $repo->getLastReadings(5);
    echo "✓ Dernières données récupérées: " . count($lastData) . " lignes\n";
    
    if (!empty($lastData)) {
        echo "\n=== STRUCTURE DES DONNÉES ===\n";
        $firstRow = $lastData[0];
        foreach ($firstRow as $key => $value) {
            echo "- $key: " . ($value !== null ? $value : 'NULL') . "\n";
        }
        
        // Vérifier spécifiquement diffMaree
        if (isset($firstRow['diffMaree'])) {
            echo "\n✓ Colonne diffMaree trouvée\n";
            echo "Valeur diffMaree: " . ($firstRow['diffMaree'] !== null ? $firstRow['diffMaree'] : 'NULL') . "\n";
        } else {
            echo "\n✗ Colonne diffMaree NON trouvée\n";
        }
    }
    
    // Test du service TideAnalysisService
    echo "\n=== TEST SERVICE ===\n";
    $tideService = new TideAnalysisService($repo);
    echo "✓ Service créé\n";
    
    // Test sur les dernières 24h
    $endDate = date('Y-m-d H:i:s');
    $startDate = date('Y-m-d H:i:s', strtotime('-1 day'));
    
    echo "Période test: $startDate à $endDate\n";
    
    $stats = $tideService->compute($startDate, $endDate);
    echo "✓ Statistiques calculées\n";
    
    echo "\n=== RÉSULTATS ===\n";
    foreach ($stats as $key => $value) {
        if (is_array($value)) {
            echo "- $key: " . json_encode($value) . "\n";
        } else {
            echo "- $key: " . ($value !== null ? $value : 'NULL') . "\n";
        }
    }
    
    // Test des données hebdomadaires
    echo "\n=== TEST DONNÉES HEBDOMADAIRES ===\n";
    $sixMonthsAgo = date('Y-m-d H:i:s', strtotime('-6 months'));
    $weeklyStats = $tideService->computeWeeklySeries($sixMonthsAgo, $endDate);
    echo "✓ Données hebdomadaires calculées: " . count($weeklyStats['labels']) . " semaines\n";
    
    if (isset($weeklyStats['diff_maree_moyenne'])) {
        echo "✓ Données diff_maree_moyenne trouvées\n";
        echo "Première valeur: " . ($weeklyStats['diff_maree_moyenne'][0] ?? 'NULL') . "\n";
    } else {
        echo "✗ Données diff_maree_moyenne NON trouvées\n";
    }
    
} catch (Exception $e) {
    echo "✗ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
