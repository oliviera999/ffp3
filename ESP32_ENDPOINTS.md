# 📡 Endpoints ESP32 - FFP3 Aquaponie

Documentation complète des endpoints disponibles pour les microcontrôleurs ESP32.

**Date de mise à jour** : 2025-10-11  
**Version** : 4.4.0

---

## 🌍 Environnements

Le système dispose de deux environnements séparés avec leurs propres tables de base de données :

- **PRODUCTION** (`ENV=prod`) : Tables `ffp3Data`, `ffp3Outputs`, `ffp3Heartbeat`
- **TEST** (`ENV=test`) : Tables `ffp3Data2`, `ffp3Outputs2`, `ffp3Heartbeat2`

---

## 📤 PRODUCTION - Envoi de données capteurs

### POST `/post-data`
**Alias** : `POST /post-ffp3-data.php`

Point d'ingestion principal pour les données des capteurs.

#### Authentification
- **API Key** (legacy) : Paramètre `api_key` dans le body
- **Signature HMAC-SHA256** (recommandé) : 
  - `timestamp` : Timestamp Unix actuel
  - `signature` : HMAC-SHA256 calculé avec `API_SIG_SECRET`
  - Fenêtre de validité : `SIG_VALID_WINDOW` secondes (défaut: 300s)

#### Paramètres (application/x-www-form-urlencoded)
```
api_key=VOTRE_CLE_API
timestamp=1234567890
signature=HMAC_SIGNATURE
sensor=ESP32_ID
version=1.0.0
TempAir=22.5
Humidite=65.3
TempEau=24.1
EauPotager=45.2
EauAquarium=62.8
EauReserve=78.4
diffMaree=5.2
Luminosite=850
etatPompeAqua=1
etatPompeTank=0
etatHeat=1
etatUV=1
bouffeMatin=1
bouffeMidi=0
bouffePetits=1
bouffeGros=0
aqThreshold=40
tankThreshold=30
chauffageThreshold=22
mail=user@example.com
mailNotif=1
resetMode=0
bouffeSoir=1
```

#### Réponses
- **200 OK** : `Données enregistrées avec succès`
- **401 Unauthorized** : Clé API ou signature invalide
- **400 Bad Request** : Données manquantes ou invalides
- **500 Internal Server Error** : Erreur serveur

---

## 📥 PRODUCTION - Récupération de configuration

### GET `/api/outputs/state`

Récupère l'état actuel de tous les GPIO/outputs configurés.

#### Réponse (JSON)
```json
{
  "4": 1,
  "5": 0,
  "12": 1,
  "13": 0,
  "100": "user@example.com",
  "101": 1,
  "102": 40,
  "103": 30,
  "104": 22,
  "105": 8,
  "106": 13,
  "107": 20,
  "108": 0,
  "109": 0,
  "110": 0,
  "111": 5,
  "112": 3,
  "113": 120,
  "114": 95,
  "115": 0,
  "116": 900
}
```

**Format** : `{ gpio: state }`

#### GPIO Principaux
- **GPIO 4** : Pompe aquarium (0=OFF, 1=ON)
- **GPIO 5** : Pompe réserve (0=OFF, 1=ON)
- **GPIO 12** : Chauffage (0=OFF, 1=ON)
- **GPIO 13** : Lumière UV (0=OFF, 1=ON)
- **GPIO 100** : Email de notification (string)
- **GPIO 101** : Notifications activées (0=OFF, 1=ON)
- **GPIO 102** : Seuil aquarium bas (cm)
- **GPIO 103** : Seuil réserve basse (cm)
- **GPIO 104** : Température chauffage min (°C)
- **GPIO 105** : Heure nourrissage matin (0-23)
- **GPIO 106** : Heure nourrissage midi (0-23)
- **GPIO 107** : Heure nourrissage soir (0-23)
- **GPIO 108** : Nourrir petits poissons (action ponctuelle)
- **GPIO 109** : Nourrir gros poissons (action ponctuelle)
- **GPIO 110** : Reset ESP (action ponctuelle)
- **GPIO 111** : Durée nourrissage gros (secondes)
- **GPIO 112** : Durée nourrissage petits (secondes)
- **GPIO 113** : Durée remplissage réserve (secondes)
- **GPIO 114** : Seuil débordement (cm)
- **GPIO 115** : Forçage réveil (0=OFF, 1=ON)
- **GPIO 116** : Fréquence WakeUp (secondes)

---

## 💓 PRODUCTION - Heartbeat

### POST `/heartbeat`
**Alias** : `POST /heartbeat.php`

Envoie régulier du statut système de l'ESP32.

#### Paramètres (application/x-www-form-urlencoded)
```
uptime=3600
free=191600
min=178404
reboots=2
crc=ABCD1234
```

- **uptime** : Temps de fonctionnement en secondes
- **free** : Mémoire libre actuelle (bytes)
- **min** : Mémoire libre minimale depuis démarrage (bytes)
- **reboots** : Nombre de redémarrages
- **crc** : CRC32 calculé sur `uptime={uptime}&free={free}&min={min}&reboots={reboots}`

#### Calcul du CRC32
```c
// Utiliser le polynôme 0xEDB88320
String payload = "uptime=" + String(uptime) + 
                 "&free=" + String(freeHeap) + 
                 "&min=" + String(minHeap) + 
                 "&reboots=" + String(rebootCount);
uint32_t crc = calculateCRC32(payload);
String crcHex = String(crc, HEX);
crcHex.toUpperCase();
```

#### Réponses
- **200 OK** : `OK`
- **400 Bad Request** : CRC invalide ou champs manquants
- **500 Internal Server Error** : Erreur serveur

---

## 🧪 TEST - Envoi de données capteurs

### POST `/post-data-test`

Identique à `/post-data` mais écrit dans les tables TEST (`ffp3Data2`).

Utiliser le même format de paramètres et authentification que la version PROD.

---

## 📥 TEST - Récupération de configuration

### GET `/api/outputs-test/state`

Identique à `/api/outputs/state` mais lit les tables TEST (`ffp3Outputs2`).

Format de réponse identique à la version PROD.

---

## 💓 TEST - Heartbeat

### POST `/heartbeat-test`
**Alias** : `POST /heartbeat-test.php`

Identique à `/heartbeat` mais écrit dans la table TEST (`ffp3Heartbeat2`).

Utiliser le même format de paramètres et validation CRC que la version PROD.

---

## 🔒 Sécurité

### Authentification Recommandée (HMAC-SHA256)

1. **Côté ESP32** :
```c
#include <mbedtls/md.h>

String calculateHMAC(String timestamp, String secret) {
    byte hmacResult[32];
    mbedtls_md_context_t ctx;
    mbedtls_md_type_t md_type = MBEDTLS_MD_SHA256;
    
    mbedtls_md_init(&ctx);
    mbedtls_md_setup(&ctx, mbedtls_md_info_from_type(md_type), 1);
    mbedtls_md_hmac_starts(&ctx, (const unsigned char*)secret.c_str(), secret.length());
    mbedtls_md_hmac_update(&ctx, (const unsigned char*)timestamp.c_str(), timestamp.length());
    mbedtls_md_hmac_finish(&ctx, hmacResult);
    mbedtls_md_free(&ctx);
    
    String signature = "";
    for(int i = 0; i < 32; i++) {
        char hex[3];
        sprintf(hex, "%02x", hmacResult[i]);
        signature += hex;
    }
    return signature;
}

// Utilisation
unsigned long timestamp = timeClient.getEpochTime();
String signature = calculateHMAC(String(timestamp), API_SIG_SECRET);

// Dans la requête POST
httpPOSTRequest += "&timestamp=" + String(timestamp);
httpPOSTRequest += "&signature=" + signature;
```

2. **Côté Serveur** :
Le serveur valide automatiquement :
- Le timestamp est dans la fenêtre de validité (±300s par défaut)
- La signature HMAC correspond au timestamp reçu

### Validation CRC32 (Heartbeat)

```c
#include <CRC32.h>

uint32_t calculateCRC32(String data) {
    CRC32 crc;
    for(int i = 0; i < data.length(); i++) {
        crc.update(data.charAt(i));
    }
    return crc.finalize();
}
```

---

## 📊 Polling Recommandé

- **Données capteurs** : Toutes les 5-10 minutes
- **Heartbeat** : Toutes les 5 minutes
- **Configuration** : Au démarrage + toutes les heures

---

## 🐛 Débogage

### Codes d'erreur HTTP
- **405** : Méthode non autorisée (utiliser POST)
- **401** : Authentification échouée
- **400** : Données invalides ou manquantes
- **500** : Erreur serveur (contacter admin)

### Logs
Les logs sont écrits dans `/ffp3/cronlog.txt` avec le niveau approprié (INFO, WARNING, ERROR).

---

## 📝 Exemple Complet (Arduino/ESP32)

```cpp
#include <WiFi.h>
#include <HTTPClient.h>
#include <time.h>

// Configuration
const char* API_KEY = "votre_cle_api";
const char* API_SIG_SECRET = "votre_secret_hmac";
const char* SERVER_URL = "https://iot.olution.info/ffp3";

// Envoi des données
void sendSensorData() {
    if (WiFi.status() == WL_CONNECTED) {
        HTTPClient http;
        
        // Endpoint (PROD ou TEST)
        String endpoint = String(SERVER_URL) + "/post-data";
        
        http.begin(endpoint);
        http.addHeader("Content-Type", "application/x-www-form-urlencoded");
        
        // Timestamp actuel
        unsigned long timestamp = getEpochTime();
        String signature = calculateHMAC(String(timestamp), API_SIG_SECRET);
        
        // Construction du payload
        String httpRequestData = "api_key=" + String(API_KEY);
        httpRequestData += "&timestamp=" + String(timestamp);
        httpRequestData += "&signature=" + signature;
        httpRequestData += "&sensor=ESP32_" + WiFi.macAddress();
        httpRequestData += "&version=1.0.0";
        httpRequestData += "&TempAir=" + String(tempAir);
        httpRequestData += "&Humidite=" + String(humidity);
        httpRequestData += "&TempEau=" + String(tempEau);
        // ... autres paramètres
        
        int httpResponseCode = http.POST(httpRequestData);
        
        if (httpResponseCode == 200) {
            Serial.println("✓ Données envoyées avec succès");
        } else {
            Serial.printf("✗ Erreur HTTP: %d\n", httpResponseCode);
            String response = http.getString();
            Serial.println("Réponse: " + response);
        }
        
        http.end();
    }
}

// Récupération de la configuration
void getConfiguration() {
    if (WiFi.status() == WL_CONNECTED) {
        HTTPClient http;
        
        String endpoint = String(SERVER_URL) + "/api/outputs/state";
        http.begin(endpoint);
        
        int httpResponseCode = http.GET();
        
        if (httpResponseCode == 200) {
            String payload = http.getString();
            // Parser le JSON et appliquer la configuration
            parseAndApplyConfig(payload);
        }
        
        http.end();
    }
}
```

---

## 🔗 Ressources

- **Documentation API complète** : `ESP32_API_REFERENCE.md`
- **Guide de démarrage rapide** : `QUICKSTART_V4.md`
- **Résolution de problèmes** : `DIAGNOSTIC_ESP32_TROUBLESHOOTING.md`
- **Changelog** : `CHANGELOG.md`

---

**© 2025 olution | Système d'aquaponie FFP3**

