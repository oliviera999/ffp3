<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\SensorData;
use App\Repository\BoardRepository;
use App\Repository\OutputRepository;
use App\Repository\SensorRepository;
use App\Service\ErrorAlertService;
use App\Service\LogService;
use App\Service\OutputCacheService;
use App\Security\SignatureValidator;
use App\Util\ResponseHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

class PostDataController
{
    public function __construct(
        private LogService $logger,
        private ErrorAlertService $errorAlert,
        private OutputCacheService $outputCache,
        private SensorRepository $sensorRepo,
        private OutputRepository $outputRepo,
        private BoardRepository $boardRepo
    ) {
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
            return ResponseHelper::text($response, 'Méthode non autorisée', 405);
        }

        $params = $request->getParsedBody();
        if (!is_array($params)) {
            $params = [];
        }

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
                return ResponseHelper::text($response, 'Signature incomplète', 401);
            }

            $sigSecret = $_ENV['API_SIG_SECRET'] ?? null;
            if ($sigSecret === null) {
                $errorMessage = 'Variable API_SIG_SECRET manquante dans .env';
                $this->logger->error($errorMessage);
                $this->errorAlert->recordError($errorMessage);
                return ResponseHelper::text($response, 'Configuration serveur manquante', 500);
            }

            $sigWindow = (int) ($_ENV['SIG_VALID_WINDOW'] ?? 300);

            if (!SignatureValidator::isValid((string) $timestamp, (string) $signature, $sigSecret, $sigWindow)) {
                $this->logger->warning('Signature HMAC invalide', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'n/a']);
                return ResponseHelper::text($response, 'Signature incorrecte', 401);
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
            $errorMessage = 'Variable API_KEY manquante dans .env';
            $this->logger->error($errorMessage);
            $this->errorAlert->recordError($errorMessage);
            return ResponseHelper::text($response, 'Configuration serveur manquante', 500);
        }

        if ($apiKeyProvided !== $apiKeyExpected) {
            $this->logger->warning("Clé API invalide depuis {ip}", ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'n/a']);
            return ResponseHelper::text($response, 'Clé API incorrecte', 401);
        }

        // Fonctions utilitaires de lecture POST --------------------------------
        // Valeur brute (chaîne) ou null si absente / vide / non-scalaire
        $sanitize = static function (string $key) use ($params): ?string {
            if (!isset($params[$key]) || !is_scalar($params[$key])) {
                return null;
            }
            $v = trim((string) $params[$key]);
            return $v !== '' ? $v : null;
        };
        // Conversions typées sûres (retournent null si champ manquant ou invalide)
        $toFloat = static function (string $key) use ($params): ?float {
            if (!isset($params[$key]) || !is_scalar($params[$key]) || $params[$key] === '') {
                return null;
            }
            $f = (float) $params[$key];
            return is_finite($f) ? $f : null;
        };
        $toInt = static fn(string $key) => isset($params[$key]) && is_scalar($params[$key]) && $params[$key] !== ''
            ? (int) $params[$key] : null;

        // Validation des champs requis (BDD : sensor et version NOT NULL)
        $sensor = $sanitize('sensor');
        $version = $sanitize('version');
        if ($sensor === null || $version === null) {
            $missing = array_filter([
                $sensor === null ? 'sensor' : null,
                $version === null ? 'version' : null,
            ]);
            $msg = 'Champs requis manquants: ' . implode(', ', $missing);
            $this->logger->warning('PostData: ' . $msg, ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'n/a']);
            return ResponseHelper::text($response, $msg, 400);
        }
        // Limiter à 30 caractères (taille colonne BDD)
        $sensor = substr($sensor, 0, 30);
        $version = substr($version, 0, 30);

        // Construction de l'objet transférant les données capteurs -------------
        $data = new SensorData(
            sensor: $sensor,
            version: $version,
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
            bouffeSoir: $toInt('bouffeSoir'),
            tempsGros: $toInt('tempsGros'),
            tempsPetits: $toInt('tempsPetits'),
            tempsRemplissageSec: $toInt('tempsRemplissageSec'),
            limFlood: $toInt('limFlood'),
            wakeUp: $toInt('WakeUp'),
            freqWakeUp: $toInt('FreqWakeUp'),
            // v11.168: Flag configSynced pour éviter écrasement config par valeurs par défaut
            configSynced: $toInt('configSynced')
        );

        try {
            // Insertion des données capteurs via le repository injecté
            $this->sensorRepo->insert($data);

            // Synchroniser les états dans ffp3Outputs/ffp3Outputs2
            $this->outputRepo->syncStatesFromSensorData($data);
            
            // Invalider le cache après synchronisation ESP32
            $this->outputCache->invalidateCache();

            // Mettre à jour le timestamp de la dernière requête de la board
            $this->boardRepo->updateLastRequest('1'); // Board 1 par défaut

            $this->logger->info('Données capteurs insérées et outputs synchronisés', ['sensor' => $data->sensor, 'version' => $data->version]);
            
            return ResponseHelper::text($response, 'Données enregistrées avec succès', 200);
            
        } catch (Throwable $e) {
            $errorMessage = 'Erreur insertion données';
            $this->logger->error($errorMessage, ['error' => $e->getMessage()]);
            
            // Enregistrer l'erreur pour détection répétée
            $this->errorAlert->recordError($errorMessage, [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return ResponseHelper::text($response, 'Erreur serveur', 500);
        }
    }
}
