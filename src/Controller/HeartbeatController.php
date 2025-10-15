<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Database;
use App\Config\TableConfig;
use App\Service\LogService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Contrôleur pour le heartbeat ESP32
 * Réception des battements de coeur (uptime, mémoire, reboot count)
 */
class HeartbeatController
{
    public function __construct(
        private LogService $logger
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
            $response->getBody()->write('Méthode non autorisée');
            return $response->withStatus(405)->withHeader('Content-Type', 'text/plain');
        }

        $params = $request->getParsedBody();
        
        // Récupération des paramètres
        $uptime = $this->sanitize($params['uptime'] ?? '');
        $free = $this->sanitize($params['free'] ?? '');
        $min = $this->sanitize($params['min'] ?? '');
        $reboots = $this->sanitize($params['reboots'] ?? '');
        $crc = strtoupper($this->sanitize($params['crc'] ?? ''));

        // Vérification des champs requis
        if (empty($uptime) || empty($free) || empty($min) || empty($reboots) || empty($crc)) {
            $this->logger->warning('Heartbeat: Champs manquants', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'n/a']);
            $response->getBody()->write('Champs manquants');
            return $response->withStatus(400)->withHeader('Content-Type', 'text/plain');
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
            $response->getBody()->write("CRC mismatch (calc={$calcCrc}, posted={$crc})");
            return $response->withStatus(400)->withHeader('Content-Type', 'text/plain');
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

            $response->getBody()->write('OK');
            return $response->withStatus(200)->withHeader('Content-Type', 'text/plain');

        } catch (\Throwable $e) {
            $this->logger->error('Heartbeat: Erreur insertion', ['error' => $e->getMessage()]);
            $response->getBody()->write('Erreur serveur');
            return $response->withStatus(500)->withHeader('Content-Type', 'text/plain');
        }
    }

    /**
     * Nettoie et sécurise une valeur POST
     */
    private function sanitize(string $data): string
    {
        return htmlspecialchars(trim(stripslashes($data)), ENT_QUOTES, 'UTF-8');
    }
}

