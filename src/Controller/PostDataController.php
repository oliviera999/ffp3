<?php

namespace App\Controller;

use App\Config\Database;
use App\Domain\SensorData;
use App\Repository\SensorRepository;
use App\Service\LogService;
use App\Security\SignatureValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

class PostDataController
{
    private LogService $logger;

    public function __construct()
    {
        $this->logger = new LogService();
    }

    /**
     * Point d'entrée HTTP : /post-data (méthode POST)
     * Vérifie la clé API, construit l'objet SensorData et insère la ligne.
     */
    public function handle(Request $request, Response $response): Response
    {
        // Vérifier méthode POST
        if ($request->getMethod() !== 'POST') {
            $this->logger->warning('PostData: Méthode non autorisée', ['method' => $request->getMethod()]);
            $response->getBody()->write('Méthode non autorisée');
            return $response->withStatus(405)->withHeader('Content-Type', 'text/plain; charset=utf-8');
        }

        $params = $request->getParsedBody();

        // ---------------------------------------------------------------------
        // Validation de la signature HMAC : facultative.
        // Si timestamp ET signature sont fournis => on valide.
        // Sinon, on laisse passer mais on loggue l'absence.
        // ---------------------------------------------------------------------
        $timestamp = $params['timestamp'] ?? null;
        $signature = $params['signature'] ?? null;

        if ($timestamp !== null || $signature !== null) {
            // Au moins un des deux champs est présent : on exige les deux + validation
            if ($timestamp === null || $signature === null) {
                $this->logger->warning('Signature partielle reçue mais incomplète', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'n/a']);
                $response->getBody()->write('Signature incomplète');
                return $response->withStatus(401)->withHeader('Content-Type', 'text/plain; charset=utf-8');
            }

            $sigSecret = $_ENV['API_SIG_SECRET'] ?? null;
            if ($sigSecret === null) {
                $this->logger->error('Variable API_SIG_SECRET manquante dans .env');
                $response->getBody()->write('Configuration serveur manquante');
                return $response->withStatus(500)->withHeader('Content-Type', 'text/plain; charset=utf-8');
            }

            $sigWindow = (int) ($_ENV['SIG_VALID_WINDOW'] ?? 300);

            if (!SignatureValidator::isValid((string) $timestamp, (string) $signature, $sigSecret, $sigWindow)) {
                $this->logger->warning('Signature HMAC invalide', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'n/a']);
                $response->getBody()->write('Signature incorrecte');
                return $response->withStatus(401)->withHeader('Content-Type', 'text/plain; charset=utf-8');
            }
            // Signature OK
        } else {
            // Pas de signature → mode compatibilité
            $this->logger->info('Aucune signature fournie – fallback API_KEY');
        }

        // ---------------------------------------------------------------------
        // Validation de la clé API (mécanisme legacy)
        // ---------------------------------------------------------------------
        $apiKeyProvided = $params['api_key'] ?? '';
        $apiKeyExpected = $_ENV['API_KEY'] ?? null;

        if ($apiKeyExpected === null) {
            $this->logger->error('Variable API_KEY manquante dans .env');
            $response->getBody()->write('Configuration serveur manquante');
            return $response->withStatus(500)->withHeader('Content-Type', 'text/plain; charset=utf-8');
        }

        if ($apiKeyProvided !== $apiKeyExpected) {
            $this->logger->warning("Clé API invalide depuis {ip}", ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'n/a']);
            $response->getBody()->write('Clé API incorrecte');
            return $response->withStatus(401)->withHeader('Content-Type', 'text/plain; charset=utf-8');
        }

        // Fonctions utilitaires de lecture POST --------------------------------
        // Valeur brute (chaîne) ou null si absente / vide
        $sanitize = static fn(string $key) => isset($params[$key]) && $params[$key] !== '' ? trim($params[$key]) : null;
        // Conversions typées sûres (retournent null si champ manquant)
        $toFloat = static fn(string $key) => isset($params[$key]) && $params[$key] !== '' ? (float) $params[$key] : null;
        $toInt   = static fn(string $key) => isset($params[$key]) && $params[$key] !== '' ? (int) $params[$key] : null;

        // Construction de l'objet transférant les données capteurs -------------
        $data = new SensorData(
            sensor: $sanitize('sensor'),
            version: $sanitize('version'),
            tempAir: $toFloat('TempAir'),
            humidite: $toFloat('Humidite'),
            tempEau: $toFloat('TempEau'),
            eauPotager: $toFloat('EauPotager'),
            eauAquarium: $toFloat('EauAquarium'),
            eauReserve: $toFloat('EauReserve'),
            diffMaree: $toFloat('diffMaree'),
            luminosite: $toFloat('Luminosite'),
            etatPompeAqua: $toInt('etatPompeAqua'),
            etatPompeTank: $toInt('etatPompeTank'),
            etatHeat: $toInt('etatHeat'),
            etatUV: $toInt('etatUV'),
            bouffeMatin: $toInt('bouffeMatin'),
            bouffeMidi: $toInt('bouffeMidi'),
            bouffePetits: $toInt('bouffePetits'),
            bouffeGros: $toInt('bouffeGros'),
            aqThreshold: $toInt('aqThreshold'),
            tankThreshold: $toInt('tankThreshold'),
            chauffageThreshold: $toInt('chauffageThreshold'),
            mail: $sanitize('mail'),
            mailNotif: $sanitize('mailNotif'),
            resetMode: $toInt('resetMode'),
            bouffeSoir: $toInt('bouffeSoir')
        );

        try {
            $pdo  = Database::getConnection();
            $repo = new SensorRepository($pdo);
            $repo->insert($data);

            $this->logger->info('Données capteurs insérées', ['sensor' => $data->sensor, 'version' => $data->version]);
            
            $response->getBody()->write('Données enregistrées avec succès');
            return $response->withStatus(200)->withHeader('Content-Type', 'text/plain; charset=utf-8');
            
        } catch (Throwable $e) {
            $this->logger->error('Erreur insertion données', ['error' => $e->getMessage()]);
            
            $response->getBody()->write('Erreur serveur');
            return $response->withStatus(500)->withHeader('Content-Type', 'text/plain; charset=utf-8');
        }
    }
} 