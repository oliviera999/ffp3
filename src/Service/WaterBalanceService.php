<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\SensorReadRepository;
use DateTimeInterface;

/**
 * Service d'analyse du bilan hydrique du système aquaponique.
 * 
 * Calcule les statistiques avancées sur la consommation, le ravitaillement,
 * les marées et le marnage avec filtrage des incertitudes de mesure.
 */
class WaterBalanceService
{
    private const UNCERTAINTY_THRESHOLD = 1.0; // Variations ≤1 cm considérées comme incertitudes

    public function __construct(private SensorReadRepository $repo) {}

    /**
     * Calcule le bilan hydrique complet sur une période donnée.
     * 
     * @param DateTimeInterface|string $start Date de début
     * @param DateTimeInterface|string $end Date de fin
     * @return array Statistiques complètes du bilan hydrique
     */
    public function computeBalance(DateTimeInterface|string $start, DateTimeInterface|string $end): array
    {
        $rows = $this->repo->fetchBetween($start, $end);
        if ($rows === []) {
            return $this->getEmptyBalance();
        }

        // fetchBetween renvoie DESC → inverser pour ASC (chronologique)
        $rows = array_reverse($rows);

        // ------------------------------------------------------------
        // RÉSERVE : Consommation et Ravitaillement
        // ------------------------------------------------------------
        $reserveStats = $this->computeReserveStats($rows);

        // ------------------------------------------------------------
        // AQUARIUM : Marées (fréquence et marnage)
        // ------------------------------------------------------------
        $tideStats = $this->computeTideStats($rows);

        // ------------------------------------------------------------
        // AQUARIUM : Consommation moyenne (différence)
        // ------------------------------------------------------------
        $aquariumConsumption = $this->computeAquariumConsumption($rows);

        return [
            // Réserve
            'reserve_consumption' => $reserveStats['consumption'],
            'reserve_refill' => $reserveStats['refill'],
            'reserve_balance' => $reserveStats['balance'],
            
            // Aquarium - Marées
            'tide_frequency' => $tideStats['frequency'],
            'tide_frequency_stddev' => $tideStats['frequency_stddev'],
            'tide_marnage' => $tideStats['marnage'],
            'tide_marnage_stddev' => $tideStats['marnage_stddev'],
            'tide_cycles' => $tideStats['cycles'],
            
            // Aquarium - Consommation
            'aquarium_consumption' => $aquariumConsumption,
        ];
    }

    /**
     * Calcule les statistiques de la réserve (consommation, ravitaillement, bilan)
     */
    private function computeReserveStats(array $rows): array
    {
        $reserveLevels = array_column($rows, 'EauReserve');
        $consumption = 0.0; // Consommation (eau qui sort = niveau qui baisse)
        $refill = 0.0;      // Ravitaillement (eau qui entre = niveau qui monte)

        for ($i = 1, $len = count($reserveLevels); $i < $len; $i++) {
            $delta = $reserveLevels[$i] - $reserveLevels[$i - 1];
            
            // Ignorer les variations d'incertitude (≤ 1 cm)
            if (abs($delta) <= self::UNCERTAINTY_THRESHOLD) {
                continue;
            }

            if ($delta > 0) {
                // Niveau monte = ravitaillement
                $refill += $delta;
            } elseif ($delta < 0) {
                // Niveau baisse = consommation
                $consumption += abs($delta);
            }
        }

        $balance = $refill - $consumption;

        return [
            'consumption' => $consumption,
            'refill' => $refill,
            'balance' => $balance,
        ];
    }

    /**
     * Calcule les statistiques de marée de l'aquarium (fréquence, marnage, écarts-types)
     */
    private function computeTideStats(array $rows): array
    {
        $levels = array_column($rows, 'EauAquarium');
        $times = array_column($rows, 'reading_time');

        if (count($levels) < 2) {
            return [
                'frequency' => null,
                'frequency_stddev' => null,
                'marnage' => null,
                'marnage_stddev' => null,
                'cycles' => 0,
            ];
        }

        $cycleMin = $levels[0];
        $cycleMax = $levels[0];
        $direction = null; // 1 = montée, -1 = descente
        $amplitudes = []; // Marnages de chaque cycle
        $cycleDurations = []; // Durées de chaque cycle (en heures)
        $cycleStartTime = $times[0];

        for ($i = 1, $len = count($levels); $i < $len; $i++) {
            $delta = $levels[$i] - $levels[$i - 1];
            
            // Ignorer les variations d'incertitude
            if (abs($delta) <= self::UNCERTAINTY_THRESHOLD) {
                continue;
            }

            $currentDir = $delta > 0 ? 1 : -1;

            if ($direction === null) {
                $direction = $currentDir;
            }

            // Changement de direction => cycle complet
            if ($direction !== $currentDir) {
                // Enregistrer l'amplitude du cycle
                $amplitude = $cycleMax - $cycleMin;
                $amplitudes[] = $amplitude;

                // Enregistrer la durée du cycle
                $cycleEndTime = $times[$i - 1];
                $cycleDuration = (strtotime($cycleEndTime) - strtotime($cycleStartTime)) / 3600; // en heures
                if ($cycleDuration > 0) {
                    $cycleDurations[] = $cycleDuration;
                }

                // Reset pour nouveau cycle
                $cycleMin = $levels[$i - 1];
                $cycleMax = $levels[$i - 1];
                $direction = $currentDir;
                $cycleStartTime = $times[$i - 1];
            }

            // Mettre à jour min/max du cycle courant
            $cycleMin = min($cycleMin, $levels[$i]);
            $cycleMax = max($cycleMax, $levels[$i]);
        }

        $cycles = count($amplitudes);

        // Marnage moyen et écart-type
        $marnage = $cycles > 0 ? array_sum($amplitudes) / $cycles : null;
        $marnageStddev = $cycles > 1 ? $this->calculateStdDev($amplitudes) : null;

        // Fréquence des marées (nombre par heure)
        $durationSeconds = strtotime(end($times)) - strtotime($times[0]);
        $totalHours = $durationSeconds / 3600;
        $frequency = ($totalHours > 0 && $cycles > 0) ? $cycles / $totalHours : null;

        // Écart-type de la fréquence (basé sur les durées de cycles)
        $frequencyStddev = null;
        if (count($cycleDurations) > 1) {
            // Calculer la fréquence pour chaque cycle (1/durée)
            $cycleFrequencies = array_map(function($duration) {
                return $duration > 0 ? 1 / $duration : 0;
            }, $cycleDurations);
            $frequencyStddev = $this->calculateStdDev($cycleFrequencies);
        }

        return [
            'frequency' => $frequency,
            'frequency_stddev' => $frequencyStddev,
            'marnage' => $marnage,
            'marnage_stddev' => $marnageStddev,
            'cycles' => $cycles,
        ];
    }

    /**
     * Calcule la consommation moyenne de l'aquarium (différence de niveau)
     */
    private function computeAquariumConsumption(array $rows): ?float
    {
        $levels = array_column($rows, 'EauAquarium');
        
        if (count($levels) < 2) {
            return null;
        }

        $consumption = 0.0;
        $countSignificantChanges = 0;

        for ($i = 1, $len = count($levels); $i < $len; $i++) {
            $delta = $levels[$i] - $levels[$i - 1];
            
            // Ignorer les variations d'incertitude
            if (abs($delta) <= self::UNCERTAINTY_THRESHOLD) {
                continue;
            }

            // Compter uniquement les baisses de niveau (consommation)
            if ($delta < 0) {
                $consumption += abs($delta);
                $countSignificantChanges++;
            }
        }

        return $countSignificantChanges > 0 ? $consumption / $countSignificantChanges : null;
    }

    /**
     * Calcule l'écart-type d'un tableau de valeurs
     */
    private function calculateStdDev(array $values): ?float
    {
        $count = count($values);
        if ($count < 2) {
            return null;
        }

        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / $count;

        return sqrt($variance);
    }

    /**
     * Retourne un bilan vide (aucune donnée disponible)
     */
    private function getEmptyBalance(): array
    {
        return [
            'reserve_consumption' => null,
            'reserve_refill' => null,
            'reserve_balance' => null,
            'tide_frequency' => null,
            'tide_frequency_stddev' => null,
            'tide_marnage' => null,
            'tide_marnage_stddev' => null,
            'tide_cycles' => 0,
            'aquarium_consumption' => null,
        ];
    }
}

