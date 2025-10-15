<?php
/**
 * Script de diagnostic pour le serveur FFP3
 * Placé dans public/ pour être accessible via Slim
 */

echo "<h1>🔍 Diagnostic FFP3 - " . date('Y-m-d H:i:s') . "</h1>\n";

// 1. Informations PHP
echo "<h2>1. Version PHP</h2>\n";
echo "Version PHP: " . PHP_VERSION . "<br>\n";
echo "Extensions chargées: " . implode(', ', get_loaded_extensions()) . "<br>\n";

// 2. Vérification des fichiers critiques
echo "<h2>2. Fichiers critiques</h2>\n";
$critical_files = [
    'vendor/autoload.php',
    'public/index.php',
    '../.env',
    '../config/container.php',
    '../config/dependencies.php'
];

foreach ($critical_files as $file) {
    $exists = file_exists($file);
    $readable = $exists ? is_readable($file) : false;
    echo "✅ $file: " . ($exists ? 'EXISTS' : '❌ MISSING') . 
         ($exists && $readable ? ' (READABLE)' : ($exists ? ' (NOT READABLE)' : '')) . "<br>\n";
}

// 3. Test de chargement des dépendances
echo "<h2>3. Test des dépendances</h2>\n";
try {
    if (file_exists('../vendor/autoload.php')) {
        require_once '../vendor/autoload.php';
        echo "✅ Autoloader chargé<br>\n";
    } else {
        echo "❌ Autoloader manquant<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur autoloader: " . $e->getMessage() . "<br>\n";
}

// 4. Test des classes principales
echo "<h2>4. Test des classes</h2>\n";
$classes_to_test = [
    'App\\Config\\Env',
    'App\\Config\\Database',
    'App\\Config\\TableConfig',
    'Slim\\Factory\\AppFactory',
    'DI\\ContainerBuilder'
];

foreach ($classes_to_test as $class) {
    try {
        if (class_exists($class)) {
            echo "✅ $class: OK<br>\n";
        } else {
            echo "❌ $class: CLASS NOT FOUND<br>\n";
        }
    } catch (Exception $e) {
        echo "❌ $class: ERROR - " . $e->getMessage() . "<br>\n";
    }
}

// 5. Test de configuration .env
echo "<h2>5. Configuration .env</h2>\n";
try {
    if (file_exists('../.env')) {
        $env_content = file_get_contents('../.env');
        $required_vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'API_KEY'];
        
        foreach ($required_vars as $var) {
            if (strpos($env_content, $var) !== false) {
                echo "✅ $var: DÉFINIE<br>\n";
            } else {
                echo "❌ $var: MANQUANTE<br>\n";
            }
        }
    } else {
        echo "❌ Fichier .env manquant<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur lecture .env: " . $e->getMessage() . "<br>\n";
}

// 6. Test de connexion base de données
echo "<h2>6. Test base de données</h2>\n";
try {
    if (class_exists('App\\Config\\Env')) {
        App\Config\Env::load();
        
        $required_db_vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
        $all_vars_present = true;
        
        foreach ($required_db_vars as $var) {
            if (!isset($_ENV[$var]) || $_ENV[$var] === '') {
                echo "❌ Variable $var manquante<br>\n";
                $all_vars_present = false;
            }
        }
        
        if ($all_vars_present) {
            $dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4";
            $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS']);
            echo "✅ Connexion DB réussie<br>\n";
            
            // Test des tables
            $tables = ['ffp3Data', 'ffp3Data2', 'ffp3Outputs', 'ffp3Outputs2', 'ffp3Heartbeat', 'ffp3Heartbeat2'];
            foreach ($tables as $table) {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    echo "✅ Table $table: EXISTS<br>\n";
                } else {
                    echo "❌ Table $table: MISSING<br>\n";
                }
            }
        }
    } else {
        echo "❌ Classe Env non trouvée<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur DB: " . $e->getMessage() . "<br>\n";
}

// 7. Test de démarrage Slim
echo "<h2>7. Test Slim Framework</h2>\n";
try {
    if (class_exists('Slim\\Factory\\AppFactory')) {
        $app = Slim\Factory\AppFactory::create();
        echo "✅ Slim App créée<br>\n";
        
        // Test d'une route simple
        $app->get('/test', function ($request, $response) {
            $response->getBody()->write('Test OK');
            return $response;
        });
        echo "✅ Route test ajoutée<br>\n";
        
    } else {
        echo "❌ Slim Framework non trouvé<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur Slim: " . $e->getMessage() . "<br>\n";
}

// 8. Test PHP-DI
echo "<h2>8. Test PHP-DI</h2>\n";
try {
    if (class_exists('DI\\ContainerBuilder')) {
        $builder = new DI\ContainerBuilder();
        echo "✅ ContainerBuilder créé<br>\n";
    } else {
        echo "❌ PHP-DI non trouvé<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur PHP-DI: " . $e->getMessage() . "<br>\n";
}

// 9. Test des permissions
echo "<h2>9. Permissions des dossiers</h2>\n";
$dirs_to_check = ['.', '..', '../config', '../src', '../vendor', '../var'];
foreach ($dirs_to_check as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $readable = is_readable($dir);
        $writable = is_writable($dir);
        echo "📁 $dir: $perms " . ($readable ? 'R' : '-') . ($writable ? 'W' : '-') . "<br>\n";
    } else {
        echo "❌ $dir: N'EXISTE PAS<br>\n";
    }
}

echo "<h2>✅ Diagnostic terminé</h2>\n";
echo "<p><strong>Instructions:</strong></p>\n";
echo "<ol>\n";
echo "<li>Ce fichier est maintenant accessible via Slim Framework</li>\n";
echo "<li>Analyser les résultats et identifier les problèmes</li>\n";
echo "<li>Supprimer ce fichier après diagnostic</li>\n";
echo "</ol>\n";
?>
