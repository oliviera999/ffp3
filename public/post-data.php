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

    tempAir: (float)$sanitize('TempAir'),

    humidite: (float)$sanitize('Humidite'),

    tempEau: (float)$sanitize('TempEau'),

    eauPotager: (float)$sanitize('EauPotager'),

    eauAquarium: (float)$sanitize('EauAquarium'),

    eauReserve: (float)$sanitize('EauReserve'),

    diffMaree: (float)$sanitize('diffMaree'),

    luminosite: (float)$sanitize('Luminosite'),

    etatPompeAqua: (int)$sanitize('etatPompeAqua'),

    etatPompeTank: (int)$sanitize('etatPompeTank'),

    etatHeat: (int)$sanitize('etatHeat'),

    etatUV: (int)$sanitize('etatUV'),

    bouffeMatin: (int)$sanitize('bouffeMatin'),

    bouffeMidi: (int)$sanitize('bouffeMidi'),

    bouffePetits: (int)$sanitize('bouffePetits'),

    bouffeGros: (int)$sanitize('bouffeGros'),

    aqThreshold: (int)$sanitize('aqThreshold'),

    tankThreshold: (int)$sanitize('tankThreshold'),

    chauffageThreshold: (int)$sanitize('chauffageThreshold'),

    mail: $sanitize('mail'),

    mailNotif: $sanitize('mailNotif'),

    resetMode: (int)$sanitize('resetMode'),

    bouffeSoir: (int)$sanitize('bouffeSoir'),

    tempsGros: (int)$sanitize('tempsGros'),

    tempsPetits: (int)$sanitize('tempsPetits'),

    tempsRemplissageSec: (int)$sanitize('tempsRemplissageSec'),

    limFlood: (int)$sanitize('limFlood'),

    wakeUp: (int)$sanitize('WakeUp'),

    freqWakeUp: (int)$sanitize('FreqWakeUp')

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
        100 => null,                     // Mail (texte, géré séparément)
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
        if ($state !== null) {
            $outputRepo->updateState($gpio, (int)$state);
            $updatedCount++;
        }
    }
    
    // Gestion spéciale GPIO 100 (email - texte)
    if ($data->mail) {
        // TODO: Implémenter updateTextValue() dans OutputRepository si nécessaire
        $logger->debug("Email config: {$data->mail} (texte non mis à jour dans outputs)");
    }

    $logger->info('Insertion OK + Outputs mis à jour', ['sensor' => $data->sensor, 'version' => $data->version]);

    echo "Données enregistrées avec succès";

} catch (Throwable $e) {

    $logger->error('Erreur lors de l\'insertion', ['error' => $e->getMessage()]);

    http_response_code(500);

    echo 'Erreur serveur';

} 