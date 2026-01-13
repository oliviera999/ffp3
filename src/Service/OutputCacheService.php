<?php

namespace App\Service;

use App\Config\TableConfig;

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
     */
    private static ?array $cache = null;
    private static ?int $cacheTimestamp = null;
    
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
        
        // Vérifier si cache valide
        if (self::$cache !== null && 
            self::$cacheTimestamp !== null && 
            ($now - self::$cacheTimestamp) < self::CACHE_TTL_SECONDS) {
            // Cache valide, retourner directement
            return self::$cache;
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
        
        // Normalisation: booléens en 0/1, conservation des strings pour email, strings numériques pour configs
        $result = [];
        
        // Définir ensemble des GPIOs booléens à normaliser (cohérent avec OutputRepository)
        // GPIOs booléens: 0-99, 101, 108, 109, 110, 115
        foreach ($gpioList as $gpio) {
            if (!array_key_exists($gpio, $byGpio)) {
                // Si absent en BDD, ne pas inventer une valeur; passer sous silence
                continue;
            }
            
            $state = $byGpio[$gpio];
            
            // Normaliser les GPIOs booléens (cohérent avec OutputRepository)
            if ($gpio < 100 || in_array($gpio, [101, 108, 109, 110, 115], true)) {
                // GPIOs booléens : convertir en entier
                if (is_string($state)) {
                    // Gérer les cas comme 'checked', 'true', 'on', etc. (même logique que OutputRepository)
                    $normalizedState = match (strtolower(trim($state))) {
                        'checked', 'true', 'on', '1', 'yes' => 1,
                        'unchecked', 'false', 'off', '0', 'no' => 0,
                        default => is_numeric($state) ? (int)$state : 0
                    };
                    $state = $normalizedState;
                } else {
                    $state = (int)$state;
                }
            }
            // Sinon, laisser tel quel (string numérique ou texte)
            // Email (100) reste string, paramètres (102-107,111-116) souvent strings numériques
            
            $result[(string)$gpio] = $state;
        }
        
        // Mettre à jour le cache
        self::$cache = $result;
        self::$cacheTimestamp = $now;
        
        return $result;
    }
    
    /**
     * Invalide le cache (appelé après modification d'un output)
     */
    public function invalidateCache(): void
    {
        self::$cache = null;
        self::$cacheTimestamp = null;
    }
    
    /**
     * Obtient les statistiques du cache
     * 
     * @return array Statistiques (age, valid, etc.)
     */
    public function getCacheStats(): array
    {
        $now = time();
        $isValid = (self::$cache !== null && 
                   self::$cacheTimestamp !== null && 
                   ($now - self::$cacheTimestamp) < self::CACHE_TTL_SECONDS);
        
        return [
            'valid' => $isValid,
            'age_seconds' => self::$cacheTimestamp !== null ? ($now - self::$cacheTimestamp) : null,
            'ttl_seconds' => self::CACHE_TTL_SECONDS,
            'cached_items' => self::$cache !== null ? count(self::$cache) : 0,
        ];
    }
}
