<?php

/**
 * Endpoint legacy pour ESP32 de TEST
 * 
 * Redirige vers le contrôleur moderne en mode TEST (utilise ffp3Data2, ffp3Outputs2)
 * 
 * Les ESP32 de test doivent pointer vers ce fichier pour poster leurs données
 * dans l'environnement de test sans impacter la production.
 */

// Force l'environnement de test
$_ENV['ENV'] = 'test';

// Charge l'autoloader et les variables d'environnement
require_once __DIR__ . '/vendor/autoload.php';

// Force le timezone (si pas encore chargé)
\App\Config\Env::load();

// Délègue au contrôleur moderne
$controller = new \App\Controller\PostDataController();
$controller->handle();
