<?php

declare(strict_types=1);

namespace App\Repository;

use App\Config\TableConfig;
use App\Domain\SensorData;
use PDO;

/**
 * Repository pour gérer les outputs (GPIO/relais) en base de données
 * 
 * Gère la table ffp3Outputs (PROD) ou ffp3Outputs2 (TEST)
 */
class OutputRepository
{
    public function __construct(private PDO $pdo) {}

    /**
     * Récupère tous les outputs avec leurs états actuels
     * 
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        $table = TableConfig::getOutputsTable();
        // Filtrer : name NOT NULL et name != '' pour éviter les doublons vides
        // Ordre personnalisé : pompe aquarium, pompe réserve, radiateurs, lumière, nourrissage, reset
        $sql = "SELECT id, board, gpio, name, state 
                FROM {$table} 
                WHERE name IS NOT NULL AND name != ''
                ORDER BY 
                    CASE 
                        WHEN name LIKE '%Pompe aquarium%' OR name LIKE '%pompe aquarium%' THEN 1
                        WHEN name LIKE '%Pompe r%serve%' OR name LIKE '%pompe r%serve%' THEN 2
                        WHEN name LIKE '%Radiateur%' OR name LIKE '%radiateur%' THEN 3
                        WHEN name LIKE '%Lumi%re%' OR name LIKE '%lumi%re%' THEN 4
                        WHEN gpio = 101 THEN 5  -- Notifications (switch)
                        WHEN gpio = 115 THEN 6  -- Forçage réveil (switch)
                        WHEN name LIKE '%petits poissons%' THEN 7
                        WHEN name LIKE '%gros poissons%' THEN 8
                        WHEN name LIKE '%reset%' OR name LIKE '%Reset%' THEN 9
                        ELSE 99
                    END,
                    gpio ASC";
        
        $stmt = $this->pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Normaliser les valeurs booléennes pour les GPIOs spéciaux
        foreach ($results as &$result) {
            $gpio = (int)$result['gpio'];
            if ($gpio < 100 || in_array($gpio, [101, 108, 109, 110, 115])) {
                // GPIOs booléens : convertir en entier
                $state = $result['state'];
                if (is_string($state)) {
                    // Gérer les cas comme 'checked', 'true', 'on', etc.
                    $normalizedState = match (strtolower($state)) {
                        'checked', 'true', 'on', '1', 'yes' => 1,
                        'unchecked', 'false', 'off', '0', 'no' => 0,
                        default => is_numeric($state) ? (int)$state : 0
                    };
                    $result['state'] = $normalizedState;
                } else {
                    $result['state'] = (int)$state;
                }
            }
        }
        
        return $results;
    }

    /**
     * Récupère un output spécifique par son GPIO
     * 
     * @param int $gpio Numéro GPIO
     * @return array<string, mixed>|null
     */
    public function findByGpio(int $gpio): ?array
    {
        $table = TableConfig::getOutputsTable();
        $sql = "SELECT id, board, gpio, name, state FROM {$table} WHERE gpio = :gpio";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':gpio' => $gpio]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result === false) {
            return null;
        }
        
        // Normaliser les valeurs booléennes pour les GPIOs spéciaux
        if ($gpio < 100 || in_array($gpio, [101, 108, 109, 110, 115])) {
            // GPIOs booléens : convertir en entier
            $state = $result['state'];
            if (is_string($state)) {
                // Gérer les cas comme 'checked', 'true', 'on', etc.
                $normalizedState = match (strtolower($state)) {
                    'checked', 'true', 'on', '1', 'yes' => 1,
                    'unchecked', 'false', 'off', '0', 'no' => 0,
                    default => is_numeric($state) ? (int)$state : 0
                };
                $result['state'] = $normalizedState;
            } else {
                $result['state'] = (int)$state;
            }
        }
        
        return $result;
    }

    /**
     * Met à jour l'état d'un output
     * 
     * @param int $gpio Numéro GPIO
     * @param int $state Nouvel état (0 ou 1)
     * @return bool Succès de l'opération
     */
    public function updateState(int $gpio, int $state): bool
    {
        $table = TableConfig::getOutputsTable();
        $sql = "UPDATE {$table} SET state = :state WHERE gpio = :gpio";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':gpio' => $gpio,
            ':state' => $state
        ]);
    }

    /**
     * Récupère tous les GPIO d'une board spécifique avec leurs noms et états
     * 
     * @param string $board Numéro de la board
     * @return array<int, array<string, mixed>>
     */
    public function findByBoard(string $board): array
    {
        $table = TableConfig::getOutputsTable();
        $sql = "SELECT id, board, gpio, name, state 
                FROM {$table} 
                WHERE board = :board AND name IS NOT NULL AND name != ''
                ORDER BY gpio ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':board' => $board]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Normaliser les valeurs booléennes pour les GPIOs spéciaux
        foreach ($results as &$result) {
            $gpio = (int)$result['gpio'];
            if ($gpio < 100 || in_array($gpio, [101, 108, 109, 110, 115])) {
                // GPIOs booléens : convertir en entier
                $state = $result['state'];
                if (is_string($state)) {
                    // Gérer les cas comme 'checked', 'true', 'on', etc.
                    $normalizedState = match (strtolower($state)) {
                        'checked', 'true', 'on', '1', 'yes' => 1,
                        'unchecked', 'false', 'off', '0', 'no' => 0,
                        default => is_numeric($state) ? (int)$state : 0
                    };
                    $result['state'] = $normalizedState;
                } else {
                    $result['state'] = (int)$state;
                }
            }
        }
        
        return $results;
    }

    /**
     * Récupère la dernière GPIO modifiée d'une board spécifique
     * 
     * @param string $board Numéro de la board
     * @return array<string, mixed>|null
     */
    public function findLastModifiedGpio(string $board): ?array
    {
        $table = TableConfig::getOutputsTable();
        
        // Vérifier si la colonne requestTime existe
        $checkColumnSql = "SHOW COLUMNS FROM {$table} LIKE 'requestTime'";
        $checkStmt = $this->pdo->query($checkColumnSql);
        $hasRequestTime = $checkStmt->fetch() !== false;
        
        if ($hasRequestTime) {
            // Conversion de l'heure européenne vers l'heure marocaine (retrancher 1h)
            $sql = "SELECT id, board, gpio, name, state, 
                           DATE_FORMAT(DATE_SUB(requestTime, INTERVAL 1 HOUR), '%d/%m/%Y %H:%i:%s') as last_modified_time
                    FROM {$table} 
                    WHERE board = :board AND name IS NOT NULL AND name != '' AND requestTime IS NOT NULL
                    ORDER BY requestTime DESC 
                    LIMIT 1";
        } else {
            // Fallback: utiliser la première GPIO trouvée avec l'heure actuelle
            $sql = "SELECT id, board, gpio, name, state, 
                           DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 HOUR), '%d/%m/%Y %H:%i:%s') as last_modified_time
                    FROM {$table} 
                    WHERE board = :board AND name IS NOT NULL AND name != ''
                    ORDER BY gpio ASC 
                    LIMIT 1";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':board' => $board]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result === false) {
            return null;
        }
        
        // Normaliser les valeurs booléennes pour les GPIOs spéciaux
        $gpio = (int)$result['gpio'];
        if ($gpio < 100 || in_array($gpio, [101, 108, 109, 110, 115])) {
            // GPIOs booléens : convertir en entier
            $state = $result['state'];
            if (is_string($state)) {
                // Gérer les cas comme 'checked', 'true', 'on', etc.
                $normalizedState = match (strtolower($state)) {
                    'checked', 'true', 'on', '1', 'yes' => 1,
                    'unchecked', 'false', 'off', '0', 'no' => 0,
                    default => is_numeric($state) ? (int)$state : 0
                };
                $result['state'] = $normalizedState;
            } else {
                $result['state'] = (int)$state;
            }
        }
        
        return $result;
    }

    /**
     * Synchronise les états des GPIO depuis les données capteurs
     * Met à jour ffp3Outputs ou ffp3Outputs2 selon l'environnement
     * 
     * LOGIQUE DE PRIORITÉ : Les modifications faites via l'interface web ont 
     * priorité pendant 5 minutes. L'ESP32 ne peut écraser un état web que 
     * si la dernière modification web date de plus de 5 minutes.
     * 
     * CORRECTION v11.38 : Ne met à jour QUE les GPIO qui ont des noms définis
     * pour éviter la création de lignes NULL inutiles.
     * 
     * @param SensorData $data Données capteurs contenant les états à synchroniser
     */
    public function syncStatesFromSensorData(SensorData $data): void
    {
        $table = TableConfig::getOutputsTable();
        
        // Mapping des champs SensorData vers les GPIO
        $gpioUpdates = [
            // Actionneurs physiques
            2 => $data->etatHeat,           // Chauffage
            15 => $data->etatUV,            // Lumière
            16 => $data->etatPompeAqua,     // Pompe aquarium
            18 => $data->etatPompeTank,     // Pompe réservoir (logique inversée)
            
            // Configuration
            100 => $data->mail,             // Email (string)
            101 => $data->mailNotif,        // Notifications (string)
            102 => $data->aqThreshold,       // Seuil aquarium
            103 => $data->tankThreshold,     // Seuil réservoir
            104 => $data->chauffageThreshold, // Seuil chauffage
            105 => $data->bouffeMatin,      // Heure nourrissage matin
            106 => $data->bouffeMidi,       // Heure nourrissage midi
            107 => $data->bouffeSoir,       // Heure nourrissage soir
            
            // Paramètres timing
            111 => $data->tempsGros,        // Temps nourrissage gros
            112 => $data->tempsPetits,      // Temps nourrissage petits
            113 => $data->tempsRemplissageSec, // Temps remplissage
            114 => $data->limFlood,         // Limite débordement
            115 => $data->wakeUp,           // WakeUp forcé
            116 => $data->freqWakeUp,       // Fréquence réveil
        ];
        
        // Transaction pour garantir la cohérence
        $this->pdo->beginTransaction();
        
        try {
            foreach ($gpioUpdates as $gpio => $value) {
                if ($value !== null) {
                    // Conversion en string pour compatibilité avec le type varchar(64)
                    $stateValue = (string)$value;
                    
                    // CORRECTION GPIO 18 : Inverser la logique pour cohérence avec getOutputsState()
                    // L'ESP32 envoie : 0 = pompe ON, 1 = pompe OFF
                    // On stocke en BDD : 0 = pompe ON, 1 = pompe OFF (cohérent avec l'affichage web)
                    if ($gpio === 18) {
                        // Pas d'inversion nécessaire ici car on veut garder la même logique
                        // que celle reçue de l'ESP32 pour cohérence avec getOutputsState()
                    }
                    
                    // CORRECTION v11.38 : Ne mettre à jour QUE les GPIO qui ont des noms définis
                    // Cela évite la création de lignes NULL inutiles
                    $sql = "UPDATE {$table} 
                            SET state = :state, 
                                requestTime = NOW(), 
                                lastModifiedBy = 'esp32'
                            WHERE gpio = :gpio 
                              AND name IS NOT NULL 
                              AND name != ''
                              AND (lastModifiedBy != 'web' 
                                   OR lastModifiedBy IS NULL 
                                   OR requestTime < NOW() - INTERVAL 5 MINUTE)";
                    
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        ':gpio' => $gpio,
                        ':state' => $stateValue
                    ]);
                    
                    // Log si un GPIO a été protégé (modification web récente) ou n'existe pas
                    if ($stmt->rowCount() === 0) {
                        // Vérifier si le GPIO existe avec un nom
                        $checkSql = "SELECT COUNT(*) FROM {$table} WHERE gpio = :gpio AND name IS NOT NULL AND name != ''";
                        $checkStmt = $this->pdo->prepare($checkSql);
                        $checkStmt->execute([':gpio' => $gpio]);
                        $exists = $checkStmt->fetchColumn();
                        
                        if ($exists > 0) {
                            error_log("GPIO {$gpio} protégé : modification web récente (< 5 min)");
                        } else {
                            error_log("GPIO {$gpio} ignoré : pas de nom défini dans la table");
                        }
                    }
                }
            }
            
            $this->pdo->commit();
            
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
