<?php



// Point d'entrée pour la réception des données capteurs (POST)

// Ce script reçoit les données envoyées par l'ESP32 ou autre client via HTTP POST

// Il vérifie la clé API, loggue les opérations, et insère les données dans la base



require __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use App\Config\TableConfig;
use App\Config\Env;
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

// Charger les variables d'environnement
Env::load();

// CORRECTION ENVIRONNEMENT TEST (v11.37)
// Détecter si l'endpoint est /post-data-test et forcer l'environnement TEST
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($requestUri, '/post-data-test') !== false) {
    TableConfig::setEnvironment('test');
    $logger->info('🔧 ENVIRONNEMENT FORCÉ À TEST', [
        'uri' => $requestUri,
        'environment' => TableConfig::getEnvironment(),
        'dataTable' => TableConfig::getDataTable(),
        'outputsTable' => TableConfig::getOutputsTable()
    ]);
}

// Logs de diagnostic détaillés (v11.37)
$logger->info('=== DÉBUT REQUÊTE POST-DATA ===', [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN',
    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
]);

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
    $logger->warning('Tentative d\'appel avec une clé API invalide', [
        'provided' => $apiKeyProvided,
        'expected' => $apiKeyConfig
    ]);
    http_response_code(401);
    echo 'Clé API incorrecte';
    exit;
}

// Logs de diagnostic environnement (v11.37)
$logger->info('Configuration environnement', [
    'ENV' => $_ENV['ENV'] ?? 'NON DÉFINI',
    'TableConfig::getEnvironment()' => TableConfig::getEnvironment(),
    'TableConfig::isTest()' => TableConfig::isTest(),
    'dataTable' => TableConfig::getDataTable(),
    'outputsTable' => TableConfig::getOutputsTable()
]);



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
    // Logs de diagnostic données reçues (v11.37)
    $logger->info('Données POST reçues', [
        'sensor' => $data->sensor,
        'version' => $data->version,
        'tempAir' => $data->tempAir,
        'humidite' => $data->humidite,
        'tempEau' => $data->tempEau,
        'eauPotager' => $data->eauPotager,
        'eauAquarium' => $data->eauAquarium,
        'eauReserve' => $data->eauReserve,
        'diffMaree' => $data->diffMaree,
        'luminosite' => $data->luminosite,
        'etatPompeAqua' => $data->etatPompeAqua,
        'etatPompeTank' => $data->etatPompeTank,
        'etatHeat' => $data->etatHeat,
        'etatUV' => $data->etatUV
    ]);

    $pdo  = Database::getConnection();
    $logger->info('Connexion DB réussie');

    $repo = new SensorRepository($pdo);
    $logger->info('Repository créé, début insertion', [
        'table' => TableConfig::getDataTable()
    ]);

    $repo->insert($data);
    $logger->info('Insertion SensorData réussie');



    // CRITIQUE (v11.37): Mise à jour SÉLECTIVE des OUTPUTS pour synchronisation ESP32
    // Seulement les valeurs PRÉSENTES dans POST sont mises à jour (pas d'écrasement)
    $outputRepo = new \App\Repository\OutputRepository($pdo);
    
    // Mapper SEULEMENT les GPIO présents dans POST (isset)
    $outputsToUpdate = [
        // === GPIO PHYSIQUES (actionneurs matériels) ===
        16 => isset($_POST['etatPompeAqua']) ? $data->etatPompeAqua : null,
        18 => isset($_POST['etatPompeTank']) ? $data->etatPompeTank : null,
        2  => isset($_POST['etatHeat']) ? $data->etatHeat : null,
        15 => isset($_POST['etatUV']) ? $data->etatUV : null,
        
        // === GPIO VIRTUELS 100-116 (configuration) ===
        100 => isset($_POST['mail']) ? $data->mail : null,
        101 => isset($_POST['mailNotif']) ? ($data->mailNotif === 'checked' ? 1 : 0) : null,
        102 => isset($_POST['aqThreshold']) ? $data->aqThreshold : null,
        103 => isset($_POST['tankThreshold']) ? $data->tankThreshold : null,
        104 => isset($_POST['chauffageThreshold']) ? $data->chauffageThreshold : null,
        105 => isset($_POST['bouffeMatin']) ? $data->bouffeMatin : null,
        106 => isset($_POST['bouffeMidi']) ? $data->bouffeMidi : null,
        107 => isset($_POST['bouffeSoir']) ? $data->bouffeSoir : null,
        108 => isset($_POST['bouffePetits']) ? $data->bouffePetits : null,
        109 => isset($_POST['bouffeGros']) ? $data->bouffeGros : null,
        110 => isset($_POST['resetMode']) ? $data->resetMode : null,
        111 => isset($_POST['tempsGros']) ? $data->tempsGros : null,
        112 => isset($_POST['tempsPetits']) ? $data->tempsPetits : null,
        113 => isset($_POST['tempsRemplissageSec']) ? $data->tempsRemplissageSec : null,
        114 => isset($_POST['limFlood']) ? $data->limFlood : null,
        115 => isset($_POST['WakeUp']) ? $data->wakeUp : null,
        116 => isset($_POST['FreqWakeUp']) ? $data->freqWakeUp : null,
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

    $logger->info('Insertion OK + Outputs mis à jour', [
        'sensor' => $data->sensor, 
        'version' => $data->version,
        'dataTable' => TableConfig::getDataTable(),
        'outputsTable' => TableConfig::getOutputsTable(),
        'outputsUpdated' => $updatedCount
    ]);

    echo "Données enregistrées avec succès";

} catch (Throwable $e) {
    // Logs d'erreur détaillés (v11.37)
    $logger->error('Erreur lors de l\'insertion', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'environment' => TableConfig::getEnvironment(),
        'dataTable' => TableConfig::getDataTable(),
        'outputsTable' => TableConfig::getOutputsTable(),
        'sensor' => $data->sensor ?? 'UNKNOWN',
        'version' => $data->version ?? 'UNKNOWN'
    ]);

    http_response_code(500);
    echo 'Erreur serveur';
} 