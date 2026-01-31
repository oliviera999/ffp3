<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use App\Config\TableConfig;
use App\Service\ErrorAlertService;
use App\Service\LogService;
use App\Util\ResponseHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Contrôleur pour le heartbeat ESP32
 * Réception des battements de coeur (uptime, mémoire, reboot count)
 */
class HeartbeatController
{
    public function __construct(
        private LogService $logger,
        private ErrorAlertService $errorAlert
    ) {
    }

    /**
     * POST /heartbeat (PROD) ou /heartbeat-test (TEST)
     * Réception battement de coeur de l'ESP32
     * 
     * Champs attendus: uptime, free, min, reboots, crc
     * CRC32 calculé sur "uptime={uptime}&free={free}&min={min}&reboots={reboots}"
     */
    public function handle(Request $request, Response $response): Response
    {
        // Vérifier méthode POST
        if ($request->getMethod() !== 'POST') {
            $this->logger->warning('Heartbeat: Méthode non autorisée', ['method' => $request->getMethod()]);
            return ResponseHelper::text($response, 'Méthode non autorisée', 405);
        }

        $params = $request->getParsedBody();
        if (!is_array($params)) {
            $params = [];
        }
        
        // Récupération des paramètres (valeurs numériques)
        $uptime = $this->sanitizeNumeric($params['uptime'] ?? '');
        $free = $this->sanitizeNumeric($params['free'] ?? '');
        $min = $this->sanitizeNumeric($params['min'] ?? '');
        $reboots = $this->sanitizeNumeric($params['reboots'] ?? '');
        $crc = strtoupper($this->sanitizeString($params['crc'] ?? ''));

        // Vérification des champs requis
        if ($uptime === '' || $free === '' || $min === '' || $reboots === '' || $crc === '') {
            $this->logger->warning('Heartbeat: Champs manquants', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'n/a']);
            return ResponseHelper::text($response, 'Champs manquants', 400);
        }

        // Vérification CRC32
        $raw = "uptime={$uptime}&free={$free}&min={$min}&reboots={$reboots}";
        $calcCrc = strtoupper(hash('crc32b', $raw));

        if ($crc !== $calcCrc) {
            $this->logger->warning('Heartbeat: CRC mismatch', [
                'calculated' => $calcCrc,
                'received' => $crc,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'n/a'
            ]);
            return ResponseHelper::text($response, "CRC mismatch (calc={$calcCrc}, posted={$crc})", 400);
        }

        // Insertion en base de données
        try {
            $pdo = Database::getConnection();
            
            // Déterminer la table selon l'environnement
            $table = TableConfig::getHeartbeatTable();
            $env = TableConfig::getEnvironment();
            
            $stmt = $pdo->prepare("
                INSERT INTO {$table} (uptime, freeHeap, minHeap, reboots) 
                VALUES (:uptime, :free, :min, :reboots)
            ");
            
            $stmt->execute([
                ':uptime' => (int)$uptime,
                ':free' => (int)$free,
                ':min' => (int)$min,
                ':reboots' => (int)$reboots,
            ]);

            $this->logger->info('Heartbeat reçu', [
                'env' => $env,
                'uptime' => $uptime,
                'reboots' => $reboots
            ]);

            return ResponseHelper::text($response, 'OK', 200);

        } catch (\Throwable $e) {
            $errorMessage = 'Heartbeat: Erreur insertion';
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

    /**
     * Nettoie et sécurise une valeur POST numérique
     * 
     * @param string $data Valeur à nettoyer
     * @return string Valeur nettoyée (chiffres uniquement)
     */
    private function sanitizeNumeric(string $data): string
    {
        $filtered = filter_var(trim($data), FILTER_SANITIZE_NUMBER_INT);
        return $filtered !== false ? $filtered : '';
    }

    /**
     * Nettoie et sécurise une valeur POST string (pour CRC)
     * 
     * @param string $data Valeur à nettoyer
     * @return string Valeur nettoyée
     */
    private function sanitizeString(string $data): string
    {
        return htmlspecialchars(trim(stripslashes($data)), ENT_QUOTES, 'UTF-8');
    }
}
