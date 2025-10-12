#!/usr/bin/env php
<?php

/**
 * Script d'installation pour FFP3 Datas
 * 
 * Crée automatiquement les dossiers nécessaires et vérifie la configuration
 */

echo "🚀 Installation de FFP3 Datas\n";
echo str_repeat("=", 50) . "\n\n";

// Fonction utilitaire pour créer un dossier
function createDirectory(string $path): void
{
    if (!is_dir($path)) {
        if (mkdir($path, 0755, true)) {
            echo "✅ Créé: {$path}\n";
        } else {
            echo "❌ Erreur lors de la création de: {$path}\n";
            exit(1);
        }
    } else {
        echo "ℹ️  Existe déjà: {$path}\n";
    }
}

// 1. Création des dossiers de cache
echo "📁 Création des dossiers de cache...\n";
createDirectory(__DIR__ . '/var');
createDirectory(__DIR__ . '/var/cache');
createDirectory(__DIR__ . '/var/cache/di');
createDirectory(__DIR__ . '/var/cache/twig');
echo "\n";

// 2. Vérification du fichier .env
echo "🔐 Vérification de la configuration...\n";
$envPath = __DIR__ . '/.env';
$envDistPath = __DIR__ . '/env.dist';

if (!file_exists($envPath)) {
    if (file_exists($envDistPath)) {
        echo "⚠️  Le fichier .env n'existe pas. Copie de env.dist...\n";
        if (copy($envDistPath, $envPath)) {
            echo "✅ Fichier .env créé depuis env.dist\n";
            echo "⚠️  ATTENTION: Vous devez configurer les variables dans .env\n";
        } else {
            echo "❌ Erreur lors de la copie de env.dist\n";
            exit(1);
        }
    } else {
        echo "❌ Aucun fichier .env ou env.dist trouvé\n";
        exit(1);
    }
} else {
    echo "✅ Fichier .env présent\n";
}
echo "\n";

// 3. Vérification des dépendances Composer
echo "📦 Vérification des dépendances Composer...\n";
if (!is_dir(__DIR__ . '/vendor')) {
    echo "⚠️  Le dossier vendor/ n'existe pas\n";
    echo "   Veuillez exécuter: composer install\n";
} else {
    echo "✅ Dépendances installées\n";
}
echo "\n";

// 4. Vérification du fichier VERSION
echo "🔢 Vérification du versionnage...\n";
$versionPath = __DIR__ . '/VERSION';
if (file_exists($versionPath)) {
    $version = trim(file_get_contents($versionPath));
    echo "✅ Version actuelle: {$version}\n";
} else {
    echo "⚠️  Fichier VERSION absent\n";
}
echo "\n";

// 5. Chargement et validation des variables d'environnement
echo "⚙️  Validation des variables d'environnement...\n";
if (file_exists($envPath) && is_dir(__DIR__ . '/vendor')) {
    require __DIR__ . '/vendor/autoload.php';
    
    try {
        \App\Config\Env::load();
        
        $requiredVars = [
            'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS',
            'API_KEY', 'APP_TIMEZONE', 'ENV'
        ];
        
        $missingVars = [];
        foreach ($requiredVars as $var) {
            if (!isset($_ENV[$var]) || $_ENV[$var] === '') {
                $missingVars[] = $var;
            }
        }
        
        if (empty($missingVars)) {
            echo "✅ Toutes les variables obligatoires sont définies\n";
            
            // Validation de ENV
            $env = $_ENV['ENV'] ?? 'prod';
            if (!in_array($env, ['prod', 'test'], true)) {
                echo "⚠️  Variable ENV invalide: {$env} (doit être 'prod' ou 'test')\n";
            } else {
                echo "✅ Environnement: {$env}\n";
            }
            
            // Vérification API_SIG_SECRET (recommandée mais non obligatoire)
            if (!isset($_ENV['API_SIG_SECRET']) || $_ENV['API_SIG_SECRET'] === '') {
                echo "⚠️  API_SIG_SECRET non défini (sécurité HMAC désactivée)\n";
            } else {
                echo "✅ API_SIG_SECRET défini\n";
            }
        } else {
            echo "❌ Variables manquantes: " . implode(', ', $missingVars) . "\n";
            echo "   Veuillez les ajouter dans .env\n";
        }
    } catch (\Exception $e) {
        echo "❌ Erreur lors du chargement de .env: " . $e->getMessage() . "\n";
    }
} else {
    echo "⏩ Validation ignorée (composer install requis)\n";
}
echo "\n";

// 6. Résumé final
echo str_repeat("=", 50) . "\n";
echo "✨ Installation terminée !\n\n";
echo "📋 Prochaines étapes:\n";
echo "   1. Vérifier la configuration dans .env\n";
echo "   2. Créer la base de données et les tables SQL\n";
echo "   3. Lancer les tests: ./vendor/bin/phpunit\n";
echo "   4. Démarrer le serveur: php -S localhost:8080 -t public\n";
echo "\n";
echo "📚 Documentation: README.md\n";
echo "🔧 Support: Voir docs/README.md\n";
echo "\n";


