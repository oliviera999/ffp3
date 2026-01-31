<?php

namespace App\Service;

use App\Config\TableConfig;
use App\Util\StateNormalizer;
use App\Service\OutputSyncService;

/**
 * Service de cache pour les états outputs
 * 
 * Réduit la charge serveur en évitant les requêtes SQL répétées
 * pour getOutputsState() qui est appelé toutes les 4-12 secondes par ESP32
 * 
 * Cache en mémoire avec TTL configurable
 */
class OutputCacheService
{
    /**
     * Durée de vie du cache (en secondes)
     */
    private const CACHE_TTL_SECONDS = 5; // 5 secondes (moitié de l'intervalle polling)
    
    /**
     * Cache en mémoire (static pour persister entre requêtes)
     * Séparé par environnement pour éviter les conflits PROD/TEST
     */
    private static array $cache = [];
    private static array $cacheTimestamp = [];
    
    /**
     * Récupère les états outputs depuis le cache ou la base de données
     * 
     * @param \PDO $pdo Connexion PDO
     * @param array $gpioList Liste des GPIOs à récupérer
     * @return array Tableau associatif [gpio => state]
     */
    public function getOutputsState(\PDO $pdo, array $gpioList): array
    {
        $now = time();
        $env = TableConfig::getEnvironment();

        if ($gpioList === []) {
            self::$cache[$env] = [];
            self::$cacheTimestamp[$env] = $now;
            return [];
        }
        
        // Vérifier si cache valide pour cet environnement
        if (isset(self::$cache[$env]) && 
            isset(self::$cacheTimestamp[$env]) && 
            ($now - self::$cacheTimestamp[$env]) < self::CACHE_TTL_SECONDS) {
            // Cache valide, retourner directement
            return self::$cache[$env];
        }
        
        // Cache expiré ou inexistant, requête BDD
        $table = TableConfig::getOutputsTable();
        
        // Construire requête IN sécurisée
        $placeholders = [];
        $params = [];
        foreach ($gpioList as $idx => $gpio) {
            $ph = ":g{$idx}";
            $placeholders[] = $ph;
            $params[$ph] = $gpio;
        }
        
        // Valider le nom de table pour sécurité
        $allowedTables = ['ffp3Outputs', 'ffp3Outputs2'];
        if (!in_array($table, $allowedTables, true)) {
            throw new \InvalidArgumentException("Table name not allowed: {$table}");
        }
        
        $sql = "SELECT gpio, state FROM `{$table}` WHERE gpio IN (" . implode(',', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Indexer par gpio pour accès rapide
        $byGpio = [];
        foreach ($rows as $row) {
            $byGpio[(int)$row['gpio']] = $row['state'];
        }
        
        // Normalisation via StateNormalizer
        $result = [];
        foreach ($gpioList as $gpio) {
            if (!array_key_exists($gpio, $byGpio)) {
                // Si absent en BDD, ne pas inventer une valeur; passer sous silence
                continue;
            }
            
            $state = $byGpio[$gpio];
            
            // Normaliser via StateNormalizer
            $state = StateNormalizer::normalize($gpio, $state);
            
            $result[(string)$gpio] = $state;
        }
        
        // v11.172: Ajouter noms symboliques (double format rétrocompatible)
        // Permet au firmware d'utiliser les clés numériques OU symboliques
        $gpioToSymbol = OutputSyncService::getGpioMapping();
        foreach ($result as $gpioStr => $state) {
            $gpio = (int)$gpioStr;
            if (isset($gpioToSymbol[$gpio])) {
                $result[$gpioToSymbol[$gpio]] = $state;
            }
        }
        
        // Mettre à jour le cache pour cet environnement
        self::$cache[$env] = $result;
        self::$cacheTimestamp[$env] = $now;
        
        return $result;
    }
    
    /**
     * Invalide le cache (appelé après modification d'un output)
     * Invalide le cache pour l'environnement actuel
     */
    public function invalidateCache(): void
    {
        $env = TableConfig::getEnvironment();
        unset(self::$cache[$env]);
        unset(self::$cacheTimestamp[$env]);
    }
    
    /**
     * Obtient les statistiques du cache
     * 
     * @return array Statistiques (age, valid, etc.)
     */
    public function getCacheStats(): array
    {
        $now = time();
        $env = TableConfig::getEnvironment();
        $isValid = (isset(self::$cache[$env]) && 
                   isset(self::$cacheTimestamp[$env]) && 
                   ($now - self::$cacheTimestamp[$env]) < self::CACHE_TTL_SECONDS);
        
        return [
            'valid' => $isValid,
            'environment' => $env,
            'age_seconds' => isset(self::$cacheTimestamp[$env]) ? ($now - self::$cacheTimestamp[$env]) : null,
            'ttl_seconds' => self::CACHE_TTL_SECONDS,
            'cached_items' => isset(self::$cache[$env]) ? count(self::$cache[$env]) : 0,
        ];
    }
}
