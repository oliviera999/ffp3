<?php
/**
 * fix-cache.php
 * Script de diagnostic et correction des caches
 * Accessible via: https://iot.olution.info/ffp3/public/fix-cache.php
 * 
 * ⚠️ À SUPPRIMER APRÈS UTILISATION pour des raisons de sécurité
 */

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Simple protection (à améliorer si nécessaire)
$ADMIN_TOKEN = 'fix2025ffp3';
$provided_token = $_GET['token'] ?? '';

if ($provided_token !== $ADMIN_TOKEN) {
    http_response_code(403);
    die('❌ Accès refusé. Token requis: ?token=XXXXX');
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FFP3 - Fix Cache</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        h2 {
            color: #34495e;
            margin-top: 30px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #dc3545;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #ffc107;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #17a2b8;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .test-result {
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
        }
        .test-ok {
            background: #d4edda;
            color: #155724;
        }
        .test-fail {
            background: #f8d7da;
            color: #721c24;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 5px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #2980b9;
        }
        .btn-danger {
            background: #e74c3c;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 FFP3 - Diagnostic et Correction des Caches</h1>
        <p><strong>Date:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>

<?php

$action = $_GET['action'] ?? 'diagnostic';

if ($action === 'clear_cache') {
    echo "<h2>🧹 Nettoyage des caches</h2>";
    
    // 1. Nettoyer cache PHP-DI
    $cache_dir = __DIR__ . '/../var/cache';
    if (is_dir($cache_dir)) {
        $files = glob($cache_dir . '/*');
        $deleted = 0;
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $deleted++;
            }
        }
        echo "<div class='success'>✅ Cache PHP-DI nettoyé ($deleted fichiers supprimés)</div>";
    } else {
        echo "<div class='warning'>⚠️ Dossier var/cache non trouvé</div>";
    }
    
    // 2. Nettoyer OPCache
    if (function_exists('opcache_reset')) {
        opcache_reset();
        echo "<div class='success'>✅ OPCache nettoyé</div>";
    } else {
        echo "<div class='warning'>⚠️ OPCache non disponible</div>";
    }
    
    echo "<div class='info'>✨ Nettoyage terminé. Testez maintenant les endpoints.</div>";
    echo "<a href='?token=$ADMIN_TOKEN&action=test' class='btn'>Tester les endpoints</a>";
    echo "<a href='?token=$ADMIN_TOKEN' class='btn'>Retour diagnostic</a>";
}

elseif ($action === 'test') {
    echo "<h2>🧪 Test des endpoints</h2>";
    
    $base_url = 'https://iot.olution.info/ffp3';
    $tests = [
        ['/control', 200, 'Control PROD'],
        ['/control-test', 200, 'Control TEST'],
        ['/api/realtime/sensors/latest', 200, 'API Sensors'],
        ['/api/realtime/outputs/state', 200, 'API Outputs'],
        ['/api/realtime/system/health', 200, 'API System Health'],
    ];
    
    $errors = 0;
    
    foreach ($tests as list($endpoint, $expected, $description)) {
        $url = $base_url . $endpoint;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == $expected) {
            echo "<div class='test-result test-ok'>✅ $description: OK (HTTP $http_code)</div>";
        } else {
            echo "<div class='test-result test-fail'>❌ $description: ERREUR (HTTP $http_code, attendu: $expected)</div>";
            $errors++;
        }
    }
    
    echo "<div style='margin-top: 20px;'>";
    if ($errors === 0) {
        echo "<div class='success'><strong>✅ SUCCÈS:</strong> Tous les endpoints fonctionnent !</div>";
    } else {
        echo "<div class='error'><strong>❌ $errors erreur(s) détectée(s)</strong></div>";
        echo "<div class='warning'>Actions recommandées:<br>";
        echo "1. Consulter les logs: <code>var/log/php_errors.log</code><br>";
        echo "2. Redémarrer Apache (SSH requis): <code>sudo systemctl restart apache2</code><br>";
        echo "3. Vérifier les logs Apache: <code>/var/log/apache2/error.log</code>";
        echo "</div>";
    }
    echo "</div>";
    
    echo "<a href='?token=$ADMIN_TOKEN' class='btn'>Retour diagnostic</a>";
}

else {
    // Diagnostic par défaut
    echo "<h2>📊 Diagnostic</h2>";
    
    // 1. Version PHP
    echo "<div class='info'><strong>PHP Version:</strong> " . PHP_VERSION . "</div>";
    
    // 2. Vérifier autoloader
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        echo "<div class='success'>✅ Autoloader présent</div>";
        require_once __DIR__ . '/../vendor/autoload.php';
    } else {
        echo "<div class='error'>❌ Autoloader manquant</div>";
    }
    
    // 3. Vérifier .env
    if (file_exists(__DIR__ . '/../.env')) {
        echo "<div class='success'>✅ Fichier .env présent</div>";
    } else {
        echo "<div class='error'>❌ Fichier .env manquant</div>";
    }
    
    // 4. Vérifier cache directory
    $cache_dir = __DIR__ . '/../var/cache';
    if (is_dir($cache_dir)) {
        $cache_files = count(glob($cache_dir . '/*'));
        if ($cache_files > 0) {
            echo "<div class='warning'>⚠️ Cache PHP-DI contient $cache_files fichier(s) - Nettoyage recommandé</div>";
        } else {
            echo "<div class='success'>✅ Cache PHP-DI vide</div>";
        }
    } else {
        echo "<div class='error'>❌ Dossier var/cache manquant</div>";
    }
    
    // 5. OPCache status
    if (function_exists('opcache_get_status')) {
        $opcache = opcache_get_status();
        if ($opcache && $opcache['opcache_enabled']) {
            echo "<div class='info'>ℹ️ OPCache activé - Utilisation mémoire: " . 
                 round($opcache['memory_usage']['used_memory'] / 1024 / 1024, 2) . " MB</div>";
        }
    }
    
    // 6. Test des classes
    echo "<h2>🔍 Test des classes</h2>";
    
    $classes = [
        'App\\Config\\Env',
        'App\\Config\\Database',
        'App\\Config\\TableConfig',
        'App\\Service\\OutputService',
        'App\\Repository\\OutputRepository',
        'App\\Controller\\OutputController',
    ];
    
    foreach ($classes as $class) {
        if (class_exists($class)) {
            echo "<div class='test-result test-ok'>✅ $class</div>";
        } else {
            echo "<div class='test-result test-fail'>❌ $class</div>";
        }
    }
    
    // Actions disponibles
    echo "<h2>🛠️ Actions disponibles</h2>";
    echo "<a href='?token=$ADMIN_TOKEN&action=clear_cache' class='btn'>Nettoyer les caches</a>";
    echo "<a href='?token=$ADMIN_TOKEN&action=test' class='btn'>Tester les endpoints</a>";
    
    echo "<div class='warning' style='margin-top: 30px;'>";
    echo "<strong>⚠️ IMPORTANT:</strong> Supprimez ce fichier après utilisation pour des raisons de sécurité:<br>";
    echo "<code>rm public/fix-cache.php</code>";
    echo "</div>";
}

?>

    </div>
</body>
</html>

