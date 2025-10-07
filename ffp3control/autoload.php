<?php
/**
 * Autoloader pour ffp3control
 * Utilise le vendor de ffp3datas pour les dépendances Composer
 * et charge les classes de config localement
 */

// Charge le vendor de ffp3datas (contient Dotenv et autres libs)
require_once __DIR__ . '/../ffp3datas/vendor/autoload.php';

// Autoloader PSR-4 simple pour les classes locales
spl_autoload_register(function ($class) {
    // Namespace de base
    $prefix = 'FFP3Control\\Config\\';
    $baseDir = __DIR__ . '/config/';

    // Vérifie si la classe utilise le namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Récupère le nom de classe relatif
    $relativeClass = substr($class, $len);

    // Construit le chemin du fichier
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    // Si le fichier existe, le charger
    if (file_exists($file)) {
        require $file;
    }
});

