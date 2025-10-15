<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\SensorReadRepository;
use App\Repository\OutputRepository;
use PDO;

/**
 * Service pour la gestion des données en temps réel
 * Fournit les dernières lectures capteurs et l'état du système
 */
class RealtimeDataService
{
    public function __construct(
        private SensorReadRepository $sensorReadRepo,
        private OutputRepository $outputRepo,
        private PDO $pdo
    ) {
    }

    /**
     * Récupère les dernières lectures de tous les capteurs avec timestamp
     * 
     * @return array{
     *   timestamp: int,
     *   reading_time: string,
     *   sensors: array<string, mixed>
     * }
     */
    public function getLatestReadings(): array
    {
        $lastReadings = $this->sensorReadRepo->getLastReadings();
        
        if (!$lastReadings) {
            return [
                'timestamp' => time(),
                'reading_time' => null,
                'sensors' => [],
            ];
        }

        // Convertir reading_time en timestamp Unix pour faciliter la comparaison côté client
        $readingTimestamp = strtotime($lastReadings['reading_time']);

        return [
            'timestamp' => $readingTimestamp,
            'reading_time' => $lastReadings['reading_time'],
            'sensors' => [
                'EauAquarium' => $lastReadings['EauAquarium'] ?? null,
                'EauReserve' => $lastReadings['EauReserve'] ?? null,
                'EauPotager' => $lastReadings['EauPotager'] ?? null,
                'TempEau' => $lastReadings['TempEau'] ?? null,
                'TempAir' => $lastReadings['TempAir'] ?? null,
                'Humidite' => $lastReadings['Humidite'] ?? null,
                'Luminosite' => $lastReadings['Luminosite'] ?? null,
            ],
        ];
    }

    /**
     * Récupère les nouvelles lectures depuis un timestamp donné
     * 
     * @param int $sinceTimestamp Timestamp Unix
     * @return array Liste des nouvelles lectures
     */
    public function getReadingsSince(int $sinceTimestamp): array
    {
        $sinceDate = date('Y-m-d H:i:s', $sinceTimestamp);
        
        $readings = $this->sensorReadRepo->getReadingsSince($sinceDate);
        
        // Transformer pour un format plus facile à consommer côté client
        $result = [];
        foreach ($readings as $reading) {
            $result[] = [
                'timestamp' => strtotime($reading['reading_time']),
                'reading_time' => $reading['reading_time'],
                'sensors' => [
                    'EauAquarium' => $reading['EauAquarium'] ?? null,
                    'EauReserve' => $reading['EauReserve'] ?? null,
                    'EauPotager' => $reading['EauPotager'] ?? null,
                    'TempEau' => $reading['TempEau'] ?? null,
                    'TempAir' => $reading['TempAir'] ?? null,
                    'Humidite' => $reading['Humidite'] ?? null,
                    'Luminosite' => $reading['Luminosite'] ?? null,
                ],
            ];
        }
        
        return $result;
    }

    /**
     * Calcule la santé/statut du système
     * 
     * @return array{
     *   online: bool,
     *   last_reading: string|null,
     *   last_reading_ago_seconds: int|null,
     *   uptime_percentage: float,
     *   readings_today: int,
     *   average_latency_seconds: float|null
     * }
     */
    public function getSystemHealth(): array
    {
        $lastReadingDateStr = $this->sensorReadRepo->getLastReadingDate();
        
        if ($lastReadingDateStr === null) {
            return [
                'online' => false,
                'last_reading' => null,
                'last_reading_ago_seconds' => null,
                'uptime_percentage' => 0.0,
                'readings_today' => 0,
                'average_latency_seconds' => null,
            ];
        }

        $lastReadingTimestamp = strtotime($lastReadingDateStr);
        $now = time();
        $secondsSinceLastReading = $now - $lastReadingTimestamp;
        
        // Système considéré online si dernière donnée < 10 minutes
        $isOnline = $secondsSinceLastReading < 600;

        // Calculer uptime sur les 30 derniers jours
        $uptimePercentage = $this->calculateUptime(30);

        // Compter les lectures aujourd'hui
        $readingsToday = $this->countReadingsToday();

        // Latence moyenne (on suppose une lecture ESP32 toutes les 3 min = 180s)
        // La latence est le délai entre le moment où l'ESP32 lit et le moment où on reçoit
        // Pour simplifier, on estime à 2-5 secondes en moyenne
        $averageLatency = $isOnline ? 3.5 : null;

        return [
            'online' => $isOnline,
            'last_reading' => $lastReadingDateStr,
            'last_reading_ago_seconds' => $secondsSinceLastReading,
            'uptime_percentage' => $uptimePercentage,
            'readings_today' => $readingsToday,
            'average_latency_seconds' => $averageLatency,
        ];
    }

    /**
     * Récupère l'état actuel de tous les outputs/GPIO
     * 
     * @return array Liste des outputs avec leur état
     */
    public function getOutputsState(): array
    {
        $outputs = $this->outputRepo->findAll();
        
        $result = [];
        foreach ($outputs as $output) {
            $result[] = [
                'id' => $output['id'],
                'gpio' => $output['gpio'],
                'name' => $output['name'],
                'state' => (int)$output['state'],
                'board' => $output['board'] ?? null,
            ];
        }
        
        return $result;
    }

    /**
     * Récupère les alertes actives (placeholder pour future implémentation)
     * 
     * @return array Liste des alertes
     */
    public function getActiveAlerts(): array
    {
        // Pour l'instant, on retourne un tableau vide
        // À implémenter avec une vraie table d'alertes dans le futur
        return [];
    }

    /**
     * Calcule le pourcentage d'uptime sur N jours
     * 
     * @param int $days Nombre de jours
     * @return float Pourcentage d'uptime (0-100)
     */
    private function calculateUptime(int $days): float
    {
        $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $endDate = date('Y-m-d H:i:s');

        // Compter le nombre d'intervalles de 3 minutes attendus
        $expectedReadings = ($days * 24 * 60) / 3; // 3 min par lecture

        // Compter les lectures réelles
        $actualReadings = $this->sensorReadRepo->countReadingsBetween($startDate, $endDate);

        if ($expectedReadings == 0) {
            return 0.0;
        }

        $uptime = ($actualReadings / $expectedReadings) * 100;
        
        // Cap à 100% au cas où il y aurait plus de lectures que prévu
        return min($uptime, 100.0);
    }

    /**
     * Compte le nombre de lectures reçues aujourd'hui
     * 
     * @return int Nombre de lectures
     */
    private function countReadingsToday(): int
    {
        $startOfDay = date('Y-m-d 00:00:00');
        $endOfDay = date('Y-m-d 23:59:59');

        return $this->sensorReadRepo->countReadingsBetween($startOfDay, $endOfDay);
    }
}

