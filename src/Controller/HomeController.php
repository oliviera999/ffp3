<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\TemplateRenderer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HomeController
{
    /**
     * Affiche la page d'accueil N3 IoT Datas
     */
    public function show(Request $request, Response $response): Response
    {
        $html = TemplateRenderer::render('home.twig', [
            'page_title' => 'n3 iot datas - olution',
            'active_page' => 'home'
        ]);

        $response->getBody()->write($html);
        return $response;
    }
}

