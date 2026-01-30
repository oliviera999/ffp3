<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\SensorData;
use App\Repository\OutputRepository;

/**
 * Service de synchronisation des états GPIO depuis les données capteurs.
 * 
 * Extrait la logique métier de synchronisation de OutputRepository
 * pour respecter le principe de responsabilité unique.
 * 
 * LOGIQUE DE PRIORITÉ : Les modifications faites via l'interface web ont 
 * priorité pendant 10 secondes. L'ESP32 ne peut écraser un état web que 
 * si la dernière modification web date de plus de 10 secondes.
 */
class OutputSyncService
{
    /**
     * Durée de priorité des changements web (en secondes)
     */
    private const WEB_PRIORITY_SECONDS = 10;

    /**
     * Mapping des champs SensorData vers les GPIO
     */
    private const GPIO_MAPPING = [
        // Actionneurs physiques
        2 => 'etatHeat',           // Chauffage
        15 => 'etatUV',            // Lumière
        16 => 'etatPompeAqua',     // Pompe aquarium
        18 => 'etatPompeTank',     // Pompe réservoir (logique inversée)
        
        // Configuration
        100 => 'mail',             // Email (string)
        101 => 'mailNotif',        // Notifications (string)
        102 => 'aqThreshold',      // Seuil aquarium
        103 => 'tankThreshold',    // Seuil réservoir
        104 => 'chauffageThreshold', // Seuil chauffage
        105 => 'bouffeMatin',      // Heure nourrissage matin
        106 => 'bouffeMidi',       // Heure nourrissage midi
        107 => 'bouffeSoir',       // Heure nourrissage soir
        
        // Commandes nourrissage (flags remis à 0 par ESP32 après exécution)
        108 => 'bouffePetits',     // Flag nourrissage petits poissons
        109 => 'bouffeGros',       // Flag nourrissage gros poissons
        
        // Paramètres timing
        111 => 'tempsGros',        // Temps nourrissage gros
        112 => 'tempsPetits',      // Temps nourrissage petits
        113 => 'tempsRemplissageSec', // Temps remplissage
        114 => 'limFlood',         // Limite débordement
        115 => 'wakeUp',           // WakeUp forcé
        116 => 'freqWakeUp',       // Fréquence réveil
    ];

    public function __construct(
        private OutputRepository $outputRepository,
        private LogService $logger
    ) {}

    /**
     * Extrait les valeurs à synchroniser depuis SensorData
     * 
     * @param SensorData $data Données capteurs
     * @return array<int, string|int|null> [gpio => valeur]
     */
    public function extractGpioValues(SensorData $data): array
    {
        $values = [];
        
        foreach (self::GPIO_MAPPING as $gpio => $property) {
            $value = $data->$property ?? null;
            if ($value !== null) {
                $values[$gpio] = $value;
            }
        }
        
        return $values;
    }

    /**
     * Synchronise les états des GPIO depuis les données capteurs.
     * 
     * Délègue la mise à jour atomique au repository après préparation des données.
     * 
     * @param SensorData $data Données capteurs contenant les états à synchroniser
     * @return array Statistiques de synchronisation
     */
    public function syncFromSensorData(SensorData $data): array
    {
        $gpioValues = $this->extractGpioValues($data);
        
        if (empty($gpioValues)) {
            return ['updated' => 0, 'skipped' => 0];
        }

        $result = $this->outputRepository->batchUpdateWithPriority(
            $gpioValues, 
            'esp32', 
            self::WEB_PRIORITY_SECONDS
        );

        $this->logger->info(sprintf(
            'OutputSyncService: %d GPIO mis à jour, %d ignorés',
            $result['updated'],
            $result['skipped']
        ));

        return $result;
    }

    /**
     * Retourne le mapping GPIO pour référence
     * 
     * @return array<int, string>
     */
    public static function getGpioMapping(): array
    {
        return self::GPIO_MAPPING;
    }

    /**
     * Retourne la durée de priorité web
     */
    public static function getWebPrioritySeconds(): int
    {
        return self::WEB_PRIORITY_SECONDS;
    }
}
