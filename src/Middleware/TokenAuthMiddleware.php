<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Security\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Middleware d'authentification par token.
 * 
 * Vérifie si l'utilisateur est authentifié via token (cookie ou paramètre URL).
 * Redirige vers /login si non authentifié.
 */
class TokenAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuthService $authService
    ) {
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        // Récupérer les paramètres de requête
        $queryParams = $request->getQueryParams();
        
        // Vérifier si l'utilisateur est authentifié par token
        if (!$this->authService->isAuthenticatedByToken($queryParams)) {
            // Récupérer l'URI demandée pour redirection après login
            $uri = $request->getUri();
            $path = $uri->getPath();
            $query = $uri->getQuery();
            
            // Construire l'URL de redirection
            $redirectUrl = '/login';
            if ($path !== '/login' && $path !== '/') {
                $redirectUrl .= '?redirect=' . urlencode($path . ($query ? '?' . $query : ''));
            }
            
            // Rediriger vers la page de login
            $response = new \Slim\Psr7\Response();
            return $response
                ->withStatus(302)
                ->withHeader('Location', $redirectUrl);
        }
        
        // Utilisateur authentifié, continuer le traitement
        return $handler->handle($request);
    }
}
