<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Service\LogService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Throwable;

/**
 * Middleware de gestion centralisée des erreurs
 * 
 * Capture toutes les exceptions non gérées, les log et retourne une réponse HTTP appropriée
 */
class ErrorHandlerMiddleware implements MiddlewareInterface
{
    private LogService $logger;

    public function __construct()
    {
        $this->logger = new LogService();
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $e) {
            // Logger l'erreur avec contexte
            $this->logger->error('Exception non gérée', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'url' => (string) $request->getUri(),
                'method' => $request->getMethod(),
            ]);

            // Créer une réponse d'erreur
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write('Une erreur serveur est survenue. Veuillez réessayer ultérieurement.');
            
            return $response->withStatus(500)
                           ->withHeader('Content-Type', 'text/plain; charset=utf-8');
        }
    }
}

