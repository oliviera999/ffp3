<?php

/**
 * Endpoint legacy pour ESP32 de PRODUCTION
 * 
 * Utilise le contrôleur moderne en mode PROD (utilise ffp3Data, ffp3Outputs)
 * 
 * Les ESP32 de production doivent pointer vers ce fichier pour poster leurs données.
 */

// Force l'environnement de production (par défaut mais on s'assure)
$_ENV['ENV'] = 'prod';

// Charge l'autoloader et les variables d'environnement
require_once __DIR__ . '/vendor/autoload.php';

// Force le timezone (si pas encore chargé)
\App\Config\Env::load();

// Délègue au contrôleur moderne
$controller = new \App\Controller\PostDataController();
$controller->handle();
