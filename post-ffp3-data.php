<?php

/**
 * Bridge legacy pour ESP32 de PRODUCTION
 * 
 * Ce fichier permet aux ESP32 configurés sur l'ancien endpoint
 * de continuer à fonctionner en déléguant au contrôleur moderne.
 * 
 * Utilise le contrôleur moderne en mode PROD (utilise ffp3Data, ffp3Outputs)
 */

// Force l'environnement de production (par défaut mais on s'assure)
$_ENV['ENV'] = 'prod';

// Charge l'autoloader et les variables d'environnement
require_once __DIR__ . '/vendor/autoload.php';

// Force le timezone (si pas encore chargé)
\App\Config\Env::load();

// Créer des objets Request et Response factices pour Slim
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

try {
    // Créer une requête factice basée sur les données POST
    $requestFactory = new ServerRequestFactory();
    $responseFactory = new ResponseFactory();
    
    // Créer la requête avec les données POST
    $request = $requestFactory->createServerRequest('POST', '/post-data', $_SERVER);
    $request = $request->withParsedBody($_POST);
    
    // Créer une réponse
    $response = $responseFactory->createResponse();
    
    // Déléguer au contrôleur moderne
    $controller = new \App\Controller\PostDataController();
    $result = $controller->handle($request, $response);
    
    // Envoyer la réponse
    http_response_code($result->getStatusCode());
    foreach ($result->getHeaders() as $name => $values) {
        foreach ($values as $value) {
            header("$name: $value");
        }
    }
    echo $result->getBody();
    
} catch (\Throwable $e) {
    // En cas d'erreur, logger et retourner une erreur simple
    error_log("Bridge legacy error: " . $e->getMessage());
    http_response_code(500);
    echo "Erreur serveur";
}
