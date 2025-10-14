<?php



// Point d'entrée pour la réception des données capteurs (POST)

// Ce script reçoit les données envoyées par l'ESP32 ou autre client via HTTP POST

// Il vérifie la clé API, loggue les opérations, et insère les données dans la base



require __DIR__ . '/../vendor/autoload.php';



use App\Config\Database;

use App\Domain\SensorData;

use App\Repository\SensorRepository;

use Monolog\Handler\StreamHandler;

use Monolog\Logger;



// --------------------------------------------------------------

// Initialisation du logger (journalisation des opérations)

// --------------------------------------------------------------

$logPath = __DIR__ . '/../var/logs';

if (!is_dir($logPath)) {

    mkdir($logPath, 0775, true);

}



$logger = new Logger('post-data');

$logger->pushHandler(new StreamHandler($logPath . '/post-data.log', Logger::INFO));



// Réponse en texte brut (utile pour debug ou client HTTP simple)

header('Content-Type: text/plain; charset=utf-8');



// Refuse toute requête autre que POST

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo "Méthode non autorisée";

    exit;

}



// --------------------------------------------------------------

// Sécurité : vérification de la clé API

// --------------------------------------------------------------

$apiKeyProvided = $_POST['api_key'] ?? '';

$apiKeyConfig   = $_ENV['API_KEY'] ?? null;

if ($apiKeyConfig === null) {

    $logger->error('La variable API_KEY est absente du .env');

    http_response_code(500);

    echo 'Configuration serveur manquante';

    exit;

}



if ($apiKeyProvided !== $apiKeyConfig) {

    $logger->warning('Tentative d\'appel avec une clé API invalide');

    http_response_code(401);

    echo 'Clé API incorrecte';

    exit;

}



// --------------------------------------------------------------

// Fonction utilitaire pour sécuriser les entrées POST

// --------------------------------------------------------------

$sanitize = static fn(string $key) => isset($_POST[$key]) ? htmlspecialchars(trim($_POST[$key])) : null;



// --------------------------------------------------------------

// Construction de l'objet métier SensorData à partir des données POST

// --------------------------------------------------------------

$data = new SensorData(

    sensor: $sanitize('sensor'),

    version: $sanitize('version'),

    // v11.37: Valeurs NULL si absentes (pas de cast 0)
    tempAir: $sanitize('TempAir') !== null ? (float)$sanitize('TempAir') : null,

    humidite: $sanitize('Humidite') !== null ? (float)$sanitize('Humidite') : null,

    tempEau: $sanitize('TempEau') !== null ? (float)$sanitize('TempEau') : null,

    eauPotager: $sanitize('EauPotager') !== null ? (float)$sanitize('EauPotager') : null,

    eauAquarium: $sanitize('EauAquarium') !== null ? (float)$sanitize('EauAquarium') : null,

    eauReserve: $sanitize('EauReserve') !== null ? (float)$sanitize('EauReserve') : null,

    diffMaree: $sanitize('diffMaree') !== null ? (float)$sanitize('diffMaree') : null,

    luminosite: $sanitize('Luminosite') !== null ? (float)$sanitize('Luminosite') : null,

    etatPompeAqua: $sanitize('etatPompeAqua') !== null ? (int)$sanitize('etatPompeAqua') : null,

    etatPompeTank: $sanitize('etatPompeTank') !== null ? (int)$sanitize('etatPompeTank') : null,

    etatHeat: $sanitize('etatHeat') !== null ? (int)$sanitize('etatHeat') : null,

    etatUV: $sanitize('etatUV') !== null ? (int)$sanitize('etatUV') : null,

    bouffeMatin: $sanitize('bouffeMatin') !== null ? (int)$sanitize('bouffeMatin') : null,

    bouffeMidi: $sanitize('bouffeMidi') !== null ? (int)$sanitize('bouffeMidi') : null,

    bouffePetits: $sanitize('bouffePetits') !== null ? (int)$sanitize('bouffePetits') : null,

    bouffeGros: $sanitize('bouffeGros') !== null ? (int)$sanitize('bouffeGros') : null,

    aqThreshold: $sanitize('aqThreshold') !== null ? (int)$sanitize('aqThreshold') : null,

    tankThreshold: $sanitize('tankThreshold') !== null ? (int)$sanitize('tankThreshold') : null,

    chauffageThreshold: $sanitize('chauffageThreshold') !== null ? (int)$sanitize('chauffageThreshold') : null,

    mail: $sanitize('mail'),

    mailNotif: $sanitize('mailNotif'),

    resetMode: $sanitize('resetMode') !== null ? (int)$sanitize('resetMode') : null,

    bouffeSoir: $sanitize('bouffeSoir') !== null ? (int)$sanitize('bouffeSoir') : null,

    tempsGros: $sanitize('tempsGros') !== null ? (int)$sanitize('tempsGros') : null,

    tempsPetits: $sanitize('tempsPetits') !== null ? (int)$sanitize('tempsPetits') : null,

    tempsRemplissageSec: $sanitize('tempsRemplissageSec') !== null ? (int)$sanitize('tempsRemplissageSec') : null,

    limFlood: $sanitize('limFlood') !== null ? (int)$sanitize('limFlood') : null,

    wakeUp: $sanitize('WakeUp') !== null ? (int)$sanitize('WakeUp') : null,

    freqWakeUp: $sanitize('FreqWakeUp') !== null ? (int)$sanitize('FreqWakeUp') : null

);



try {

    $pdo  = Database::getConnection();

    $repo = new SensorRepository($pdo);

    $repo->insert($data);



    // CRITIQUE (v11.36): Mise à jour COMPLÈTE des OUTPUTS pour synchronisation ESP32
    // TOUTES les entrées de la table outputs doivent être mises à jour
    $outputRepo = new \App\Repository\OutputRepository($pdo);
    
    // Mapper TOUS les GPIO (physiques ET virtuels) vers les données reçues
    $outputsToUpdate = [
        // === GPIO PHYSIQUES (actionneurs matériels) ===
        16 => $data->etatPompeAqua,     // Pompe aquarium
        18 => $data->etatPompeTank,     // Pompe réservoir  
        2  => $data->etatHeat,           // Chauffage
        15 => $data->etatUV,             // Lumière
        
        // === GPIO VIRTUELS 100-116 (configuration) ===
        100 => $data->mail,              // Mail (texte - stocké dans state comme varchar)
        101 => $data->mailNotif === 'checked' ? 1 : 0,  // Notif mail
        102 => $data->aqThreshold,       // Seuil aquarium
        103 => $data->tankThreshold,     // Seuil réservoir
        104 => $data->chauffageThreshold, // Seuil chauffage
        105 => $data->bouffeMatin,       // Heure bouffe matin
        106 => $data->bouffeMidi,        // Heure bouffe midi
        107 => $data->bouffeSoir,        // Heure bouffe soir
        108 => $data->bouffePetits,      // Flag bouffe petits
        109 => $data->bouffeGros,        // Flag bouffe gros
        110 => $data->resetMode,         // Reset mode
        111 => $data->tempsGros,         // Durée gros poissons
        112 => $data->tempsPetits,       // Durée petits poissons
        113 => $data->tempsRemplissageSec, // Durée remplissage
        114 => $data->limFlood,          // Limite inondation
        115 => $data->wakeUp,            // Réveil forcé
        116 => $data->freqWakeUp         // Fréquence réveil
    ];
    
    $updatedCount = 0;
    foreach ($outputsToUpdate as $gpio => $state) {
        if ($state !== null && $state !== '') {
            // GPIO 100 (mail) est un VARCHAR, les autres sont INT
            if ($gpio === 100) {
                $outputRepo->updateState($gpio, $state); // Texte pour email
            } else {
                $outputRepo->updateState($gpio, (int)$state); // Entier pour autres
            }
            $updatedCount++;
        }
    }

    $logger->info('Insertion OK + Outputs mis à jour', ['sensor' => $data->sensor, 'version' => $data->version]);

    echo "Données enregistrées avec succès";

} catch (Throwable $e) {

    $logger->error('Erreur lors de l\'insertion', ['error' => $e->getMessage()]);

    http_response_code(500);

    echo 'Erreur serveur';

} 