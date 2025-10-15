<?php
/**
 * Test simple pour vérifier le contenu du fichier dependencies.php
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Test du fichier dependencies.php</h1>";

try {
    // Lire le fichier dependencies.php
    $dependenciesFile = __DIR__ . '/../config/dependencies.php';
    
    if (!file_exists($dependenciesFile)) {
        echo "❌ Fichier dependencies.php non trouvé: $dependenciesFile<br>";
        exit;
    }
    
    echo "✅ Fichier dependencies.php trouvé<br>";
    
    // Lire le contenu
    $content = file_get_contents($dependenciesFile);
    
    echo "<h2>Analyse du contenu:</h2>";
    
    // Vérifier AquaponieController
    if (strpos($content, '\\App\\Controller\\AquaponieController::class') !== false) {
        echo "✅ AquaponieController défini<br>";
        
        // Vérifier les dépendances
        if (strpos($content, '\\App\\Repository\\SensorReadRepository::class') !== false) {
            echo "✅ SensorReadRepository trouvé<br>";
        } else {
            echo "❌ SensorReadRepository manquant<br>";
        }
        
        if (strpos($content, '\\App\\Service\\StatisticsAggregatorService::class') !== false) {
            echo "✅ StatisticsAggregatorService trouvé<br>";
        } else {
            echo "❌ StatisticsAggregatorService manquant<br>";
        }
        
        if (strpos($content, '\\App\\Service\\ChartDataService::class') !== false) {
            echo "✅ ChartDataService trouvé<br>";
        } else {
            echo "❌ ChartDataService manquant<br>";
        }
        
        if (strpos($content, '\\App\\Service\\WaterBalanceService::class') !== false) {
            echo "✅ WaterBalanceService trouvé<br>";
        } else {
            echo "❌ WaterBalanceService manquant<br>";
        }
    } else {
        echo "❌ AquaponieController non défini<br>";
    }
    
    // Vérifier OutputController
    if (strpos($content, '\\App\\Controller\\OutputController::class') !== false) {
        echo "✅ OutputController défini<br>";
    } else {
        echo "❌ OutputController non défini<br>";
    }
    
    // Vérifier RealtimeApiController
    if (strpos($content, '\\App\\Controller\\RealtimeApiController::class') !== false) {
        echo "✅ RealtimeApiController défini<br>";
    } else {
        echo "❌ RealtimeApiController non défini<br>";
    }
    
    echo "<h2>Extrait du fichier (AquaponieController):</h2>";
    echo "<pre>";
    
    // Extraire la section AquaponieController
    $lines = explode("\n", $content);
    $inAquaponie = false;
    $braceCount = 0;
    
    foreach ($lines as $lineNum => $line) {
        if (strpos($line, 'AquaponieController::class') !== false) {
            $inAquaponie = true;
            echo ($lineNum + 1) . ": " . htmlspecialchars($line) . "\n";
            continue;
        }
        
        if ($inAquaponie) {
            echo ($lineNum + 1) . ": " . htmlspecialchars($line) . "\n";
            
            // Compter les accolades pour savoir quand s'arrêter
            $braceCount += substr_count($line, '{') - substr_count($line, '}');
            
            if ($braceCount <= 0 && strpos($line, '},') !== false) {
                break;
            }
        }
    }
    
    echo "</pre>";
    
} catch (\Throwable $e) {
    echo "❌ Erreur: " . $e->getMessage() . "<br>";
    echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
}
?>
