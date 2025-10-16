<?php
/**
 * Script pour corriger directement le fichier dependencies.php
 */

echo "🔧 CORRECTION DU FICHIER DEPENDENCIES.PHP\n";
echo "=========================================\n";

$dependenciesFile = 'config/dependencies.php';

// Sauvegarder le fichier actuel
$backupFile = $dependenciesFile . '.backup.' . date('Y-m-d-H-i-s');
copy($dependenciesFile, $backupFile);
echo "✅ Sauvegarde créée: $backupFile\n";

// Lire le contenu actuel
$content = file_get_contents($dependenciesFile);

// Ajouter les alias nécessaires après la définition de PDO
$additionalContent = '
    // ====================================================================
    // ALIASES POUR COMPATIBILITÉ (services attendus par le diagnostic)
    // ====================================================================
    "pdo" => function (ContainerInterface $c) {
        return $c->get(PDO::class);
    },

    "logger" => function (ContainerInterface $c) {
        return $c->get(LogService::class);
    },

    "twig" => function (ContainerInterface $c) {
        return $c->get(TemplateRenderer::class);
    },

';

// Trouver la fin de la définition de PDO et insérer les nouveaux services
$pdoEndPattern = '/(PDO::class => function \(ContainerInterface \$c\) \{\s*return Database::getConnection\(\);\s*\},\s*\n)/';
$replacement = '$1' . $additionalContent;

$newContent = preg_replace($pdoEndPattern, $replacement, $content);

if ($newContent !== $content) {
    file_put_contents($dependenciesFile, $newContent);
    echo "✅ Services alias ajoutés avec succès\n";
} else {
    echo "❌ Impossible de modifier le fichier\n";
}

// Nettoyer le cache DI
$cacheDir = 'var/cache/di';
if (is_dir($cacheDir)) {
    array_map('unlink', glob($cacheDir . '/*'));
    echo "✅ Cache DI nettoyé\n";
}

echo "\n🎯 Correction terminée !\n";
echo "Testez maintenant: php diagnostic-complet.php\n";
