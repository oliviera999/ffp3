<?php
/**
 * Script de diagnostic simplifié pour FFP3
 * Fonctionne sans dépendances externes
 */

echo "🔍 DIAGNOSTIC SIMPLIFIÉ FFP3\n";
echo "============================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

$errors = [];

echo "📋 1. Vérification de l'environnement PHP\n";
echo "----------------------------------------\n";
echo "Version PHP: " . PHP_VERSION . "\n";
echo "Extensions PHP:\n";
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'curl'];
foreach ($requiredExtensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "  " . ($loaded ? "✅" : "❌") . " $ext\n";
    if (!$loaded) {
        $errors[] = "Extension PHP manquante: $ext";
    }
}

echo "\n📁 2. Vérification des fichiers critiques\n";
echo "----------------------------------------\n";
$criticalFiles = [
    'composer.json' => 'Fichier de configuration Composer',
    'vendor/autoload.php' => 'Autoloader Composer',
    'config/container.php' => 'Configuration du container DI',
    'public/index.php' => 'Point d\'entrée de l\'application',
    '.env' => 'Variables d\'environnement'
];

foreach ($criticalFiles as $file => $description) {
    $exists = file_exists($file);
    echo "  " . ($exists ? "✅" : "❌") . " $file ($description)\n";
    if (!$exists) {
        $errors[] = "Fichier manquant: $file";
    }
}

echo "\n📦 3. Vérification des dépendances Composer\n";
echo "-------------------------------------------\n";
if (file_exists('vendor/autoload.php')) {
    echo "✅ Autoloader Composer trouvé\n";
    
    // Test de chargement de l'autoloader
    try {
        require_once 'vendor/autoload.php';
        echo "✅ Autoloader chargé avec succès\n";
        
        // Test des classes critiques
        $requiredClasses = [
            'DI\ContainerBuilder' => 'Container Builder (php-di)',
            'Slim\App' => 'Framework Slim',
            'Twig\Environment' => 'Moteur de template Twig',
            'Monolog\Logger' => 'Système de logs Monolog'
        ];
        
        foreach ($requiredClasses as $class => $description) {
            $exists = class_exists($class);
            echo "  " . ($exists ? "✅" : "❌") . " $class ($description)\n";
            if (!$exists) {
                $errors[] = "Classe manquante: $class";
            }
        }
    } catch (Exception $e) {
        $errors[] = "Erreur chargement autoloader: " . $e->getMessage();
        echo "❌ Erreur chargement autoloader: " . $e->getMessage() . "\n";
    }
} else {
    $errors[] = "Autoloader Composer non trouvé";
    echo "❌ Autoloader Composer non trouvé\n";
}

echo "\n⚙️ 4. Test de configuration .env\n";
echo "--------------------------------\n";
if (file_exists('.env')) {
    echo "✅ Fichier .env trouvé\n";
    
    // Chargement manuel du .env
    $envContent = file_get_contents('.env');
    $lines = explode("\n", $envContent);
    $envVars = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && substr($line, 0, 1) !== '#') {
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $envVars[trim($parts[0])] = trim($parts[1]);
            }
        }
    }
    
    echo "✅ Variables d'environnement chargées: " . count($envVars) . " variables\n";
    
    // Vérifier les variables critiques
    $requiredVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
    foreach ($requiredVars as $var) {
        $value = $envVars[$var] ?? null;
        echo "  " . ($value ? "✅" : "❌") . " $var\n";
        if (!$value) {
            $errors[] = "Variable d'environnement manquante: $var";
        }
    }
} else {
    $errors[] = "Fichier .env manquant";
    echo "❌ Fichier .env manquant\n";
}

echo "\n🗄️ 5. Test de connexion base de données\n";
echo "--------------------------------------\n";
if (isset($envVars['DB_HOST'])) {
    try {
        $dsn = "mysql:host={$envVars['DB_HOST']};dbname={$envVars['DB_NAME']};charset=utf8mb4";
        $pdo = new PDO($dsn, $envVars['DB_USER'], $envVars['DB_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        echo "✅ Connexion base de données réussie\n";
        
        // Test des tables
        $tables = ['ffp3Data', 'ffp3Outputs'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->rowCount() > 0;
            echo "  " . ($exists ? "✅" : "❌") . " Table $table\n";
            if (!$exists) {
                $errors[] = "Table manquante: $table";
            }
        }
    } catch (Exception $e) {
        $errors[] = "Erreur base de données: " . $e->getMessage();
        echo "❌ Erreur base de données: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️ Variables de base de données non configurées\n";
}

echo "\n🔧 6. Test de chargement de l'application\n";
echo "---------------------------------------\n";
if (file_exists('config/container.php')) {
    echo "✅ Fichier container.php trouvé\n";
    
    try {
        // Test simple du fichier container
        $containerContent = file_get_contents('config/container.php');
        if (strpos($containerContent, 'ContainerBuilder') !== false) {
            echo "✅ ContainerBuilder détecté dans le fichier\n";
        } else {
            echo "❌ ContainerBuilder non trouvé dans le fichier\n";
            $errors[] = "ContainerBuilder non configuré";
        }
    } catch (Exception $e) {
        $errors[] = "Erreur lecture container.php: " . $e->getMessage();
        echo "❌ Erreur lecture container.php: " . $e->getMessage() . "\n";
    }
} else {
    $errors[] = "Fichier config/container.php manquant";
}

echo "\n📊 7. Résumé des erreurs\n";
echo "------------------------\n";
if (empty($errors)) {
    echo "✅ Aucune erreur détectée !\n";
    echo "\n🎯 Le système semble en bon état. Si les erreurs HTTP 500 persistent:\n";
    echo "1. Vérifier les logs Apache: tail -f /var/log/apache2/error.log\n";
    echo "2. Redémarrer Apache: sudo systemctl restart apache2\n";
    echo "3. Nettoyer le cache: rm -rf var/cache/*\n";
} else {
    echo "❌ " . count($errors) . " erreur(s) détectée(s):\n";
    foreach ($errors as $i => $error) {
        echo "  " . ($i + 1) . ". $error\n";
    }
    
    echo "\n🎯 ACTIONS RECOMMANDÉES:\n";
    echo "1. Installer les dépendances manquantes: composer install --no-dev --optimize-autoloader\n";
    echo "2. Créer le fichier .env avec les bonnes variables\n";
    echo "3. Vérifier les permissions: chmod -R 755 public/ && chmod -R 644 config/\n";
    echo "4. Redémarrer Apache: sudo systemctl restart apache2\n";
}

echo "\n📝 Logs à consulter:\n";
echo "- /var/log/apache2/error.log\n";
echo "- var/log/php_errors.log\n";
echo "- var/cache/ (nettoyer si nécessaire)\n";
