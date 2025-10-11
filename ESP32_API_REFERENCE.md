# 📡 API ESP32 - Documentation de Référence Complète

**Projet** : FFP3 Aquaponie IoT  
**Version serveur** : 4.0.0  
**Date** : 11 octobre 2025  
**Serveur** : https://iot.olution.info/ffp3/

---

## 📋 Table des Matières

1. [Vue d'ensemble](#vue-densemble)
2. [Endpoints ESP32 → Serveur (Envoi)](#endpoints-esp32--serveur-envoi)
3. [Endpoints ESP32 ← Serveur (Récupération)](#endpoints-esp32--serveur-récupération)
4. [Authentification & Sécurité](#authentification--sécurité)
5. [Codes de Réponse HTTP](#codes-de-réponse-http)
6. [Migration vers v4.0.0](#migration-vers-v40)
7. [Exemples de Code ESP32](#exemples-de-code-esp32)

---

## 🎯 Vue d'ensemble

### Architecture de Communication

```
ESP32 (Capteurs + GPIO)
      ↓ POST toutes les 2-3 min
      ↓ 
Serveur (https://iot.olution.info/ffp3/)
      ↓ Insertion en BDD
      ↓
Base de Données (MySQL)
  ├─ ffp3Data (PROD)
  ├─ ffp3Data2 (TEST)
  ├─ ffp3Outputs (GPIO PROD)
  ├─ ffp3Outputs2 (GPIO TEST)
  └─ ffp3Heartbeat (monitoring)
```

### Cycle de Communication (toutes les 2-3 minutes)

1. **ESP32 lit** tous les capteurs
2. **ESP32 POST** données vers `/public/post-data`
3. **Serveur insère** dans BDD
4. **ESP32 GET** état des GPIO depuis `/ffp3control/ffp3-outputs-action.php`
5. **ESP32 applique** les états GPIO reçus
6. **Attendre** 2-3 minutes → Recommencer

---

## 📤 Endpoints ESP32 → Serveur (Envoi)

### 1️⃣ **Envoi Données Capteurs** ⭐ PRINCIPAL

#### **Endpoint PROD** :
```
POST https://iot.olution.info/ffp3/public/post-data
```

#### **Endpoint TEST** :
```
POST https://iot.olution.info/ffp3/public/post-data-test
```

#### **Alias Legacy** (fonctionnels) :
```
POST https://iot.olution.info/ffp3/public/post-data.php
POST https://iot.olution.info/ffp3/post-ffp3-data.php
```

#### **Fréquence** : Toutes les **2-3 minutes**

#### **Content-Type** : `application/x-www-form-urlencoded`

#### **Paramètres POST Obligatoires** :

```http
api_key=fdGTMoptd5CD2ert3
```

#### **Paramètres POST Optionnels (Capteurs)** :

| Paramètre | Type | Unité | Exemple | Description |
|-----------|------|-------|---------|-------------|
| `TempAir` | float | °C | `22.5` | Température de l'air |
| `Humidite` | float | %RH | `65.0` | Humidité relative |
| `TempEau` | float | °C | `24.0` | Température de l'eau |
| `EauPotager` | float | cm | `45.0` | Niveau d'eau potager |
| `EauAquarium` | float | cm | `32.0` | Niveau d'eau aquarium |
| `EauReserve` | float | cm | `78.0` | Niveau d'eau réserve |
| `diffMaree` | float | cm | `2.5` | Différence de marée |
| `Luminosite` | float | lux | `850` | Luminosité ambiante |

#### **Paramètres POST Optionnels (États Équipements - 0 ou 1)** :

| Paramètre | GPIO | Description |
|-----------|------|-------------|
| `etatPompeAqua` | 16 | Pompe aquarium (ON=1, OFF=0) |
| `etatPompeTank` | 18 | Pompe réserve/tank |
| `etatHeat` | 100 | Chauffage |
| `etatUV` | 101 | Lampe UV |
| `bouffePetits` | 108 | Nourrisseur petits poissons |
| `bouffeGros` | 109 | Nourrisseur gros poissons |
| `bouffeMatin` | - | Nourrissage matin effectué |
| `bouffeMidi` | - | Nourrissage midi effectué |
| `bouffeSoir` | - | Nourrissage soir effectué |

#### **Paramètres POST Optionnels (Métadonnées)** :

| Paramètre | Type | Exemple | Description |
|-----------|------|---------|-------------|
| `sensor` | string | `"ESP32-Main"` | Identifiant du capteur |
| `version` | string | `"10.90"` | Version firmware ESP32 |
| `timestamp` | int | `1697123456` | Unix timestamp (pour HMAC) |
| `signature` | string | `"abc123..."` | HMAC-SHA256 (optionnel) |

#### **Paramètres POST Optionnels (Configuration)** :

| Paramètre | Type | Description |
|-----------|------|-------------|
| `aqThreshold` | int | Seuil aquarium |
| `tankThreshold` | int | Seuil réserve |
| `chauffageThreshold` | int | Seuil chauffage |
| `mail` | string | Email de notification |
| `mailNotif` | string | Active/désactive notif email |
| `resetMode` | int | Mode reset (0 ou 1) |

#### **Exemple de Requête POST** :

```http
POST /ffp3/public/post-data HTTP/1.1
Host: iot.olution.info
Content-Type: application/x-www-form-urlencoded

api_key=fdGTMoptd5CD2ert3&sensor=ESP32-Main&version=10.90&TempAir=22.5&Humidite=65.0&TempEau=24.0&EauPotager=45.0&EauAquarium=32.0&EauReserve=78.0&diffMaree=2.5&Luminosite=850&etatPompeAqua=1&etatPompeTank=0&etatHeat=1&etatUV=0&bouffePetits=0&bouffeGros=1
```

#### **Réponses Serveur** :

| Code | Réponse | Signification |
|------|---------|---------------|
| `200 OK` | `"Données enregistrées avec succès"` | ✅ Insertion réussie |
| `401 Unauthorized` | `"Clé API incorrecte"` | ❌ api_key invalide |
| `401 Unauthorized` | `"Signature incorrecte"` | ❌ HMAC invalide |
| `405 Method Not Allowed` | `"Méthode non autorisée"` | ❌ Pas POST |
| `500 Internal Server Error` | `"Erreur serveur"` | ❌ Erreur BDD/PHP |

---

### 2️⃣ **Heartbeat / Keep-Alive**

#### **Endpoint** :
```
POST https://iot.olution.info/ffp3/heartbeat.php
```

#### **Fréquence** : Toutes les **5-10 minutes**

#### **Paramètres POST** :

| Paramètre | Type | Exemple | Description |
|-----------|------|---------|-------------|
| `uptime` | int | `157` | Uptime ESP32 (minutes) |
| `free` | int | `191600` | RAM libre (bytes) |
| `min` | int | `178404` | RAM minimum atteinte (bytes) |
| `reboots` | int | `12` | Nombre de reboots depuis flash |
| `crc` | string | `"F7AB59BB"` | CRC32 de validation |

#### **Calcul du CRC32** :

```cpp
// Chaîne à hasher (SANS le &crc=...)
String raw = "uptime=" + String(uptime) + 
             "&free=" + String(free) + 
             "&min=" + String(min) + 
             "&reboots=" + String(reboots);

// CRC32 avec polynôme 0xEDB88320
uint32_t crc = CRC32::calculate(raw.c_str(), raw.length());
String crcHex = String(crc, HEX).toUpperCase();

// POST: uptime=157&free=191600&min=178404&reboots=12&crc=F7AB59BB
```

#### **Réponses Serveur** :

| Code | Réponse | Signification |
|------|---------|---------------|
| `200 OK` | `"OK"` | ✅ Heartbeat enregistré |
| `400 Bad Request` | `"CRC mismatch"` | ❌ CRC invalide |
| `500 Internal Server Error` | `"SQL error"` | ❌ Erreur BDD |

---

## 📥 Endpoints ESP32 ← Serveur (Récupération)

### 3️⃣ **Récupération État des GPIO** ⭐ PRINCIPAL

#### **Endpoint PROD** ⭐ ACTUEL :
```
GET https://iot.olution.info/ffp3/api/outputs/state
```

#### **Endpoint TEST** :
```
GET https://iot.olution.info/ffp3/api/outputs-test/state
```

#### **Endpoint Legacy** (ancien, toujours fonctionnel) :
```
GET https://iot.olution.info/ffp3/ffp3control/ffp3-outputs-action.php?action=outputs_state&board=ESP32-Main
```
⚠️ Ancien système, fonctionne mais non recommandé

#### **Fréquence** : Toutes les **2-3 minutes** (synchronisé avec envoi données)

#### **Paramètres GET (Legacy)** :

| Paramètre | Valeur | Requis |
|-----------|--------|--------|
| `action` | `outputs_state` | ✅ Oui |
| `board` | `ESP32-Main` | ✅ Oui |

#### **Réponse JSON** :

```json
{
  "16": 1,    // GPIO 16 (Pompe Aquarium) = ON
  "18": 0,    // GPIO 18 (Pompe Réserve) = OFF
  "100": 1,   // GPIO 100 (Chauffage) = ON
  "101": 0,   // GPIO 101 (UV) = OFF
  "104": 0,   // GPIO 104 (LEDs) = OFF
  "108": 0,   // GPIO 108 (Nourriture petits) = OFF
  "109": 1,   // GPIO 109 (Nourriture gros) = ON
  "110": 0    // GPIO 110 (Reset ESP) = OFF
}
```

#### **Exemple de Parsing ESP32** :

```cpp
HTTPClient http;
http.begin("https://iot.olution.info/ffp3/api/outputs/state");
int httpCode = http.GET();

if (httpCode == 200) {
    String json = http.getString();
    // Parser JSON
    DynamicJsonDocument doc(1024);
    deserializeJson(doc, json);
    
    // Appliquer états
    digitalWrite(16, doc["16"]);  // Pompe Aqua
    digitalWrite(18, doc["18"]);  // Pompe Tank
    digitalWrite(100, doc["100"]); // Chauffage
    // ...
}
http.end();
```

---

### 4️⃣ **OTA - Vérification Version Firmware**

#### **Endpoint Metadata** :
```
GET https://iot.olution.info/ffp3/ota/metadata.json
```

#### **Fréquence** : Au **démarrage** + toutes les **24 heures**

#### **Réponse JSON** :

```json
{
  "version": "9.98",
  "channels": {
    "prod": {
      "esp32-s3": {
        "version": "9.96",
        "bin_url": "http://iot.olution.info/ffp3/ota/esp32-s3/firmware.bin",
        "size": 1761280,
        "md5": "3f786850e387550fdab836ed7e6dc881"
      },
      "esp32-wroom": {
        "version": "10.90",
        "bin_url": "http://iot.olution.info/ffp3/ota/esp32-wroom/firmware.bin",
        "size": 1589248,
        "md5": "9e107d9d372bb6826bd81d3542a419d6"
      }
    },
    "test": {
      "esp32-wroom": {
        "version": "10.20",
        "bin_url": "http://iot.olution.info/ffp3/ota/test/esp32-wroom/firmware.bin",
        "size": 1589248,
        "md5": "ad0234829205b9033196ba818f7a872b"
      }
    }
  }
}
```

#### **Logique ESP32** :

```cpp
String currentVersion = "10.90";
String espType = "esp32-wroom"; // ou "esp32-s3"
String channel = "prod"; // ou "test"

HTTPClient http;
http.begin("https://iot.olution.info/ffp3/ota/metadata.json");
int httpCode = http.GET();

if (httpCode == 200) {
    DynamicJsonDocument doc(4096);
    deserializeJson(doc, http.getString());
    
    String latestVersion = doc["channels"][channel][espType]["version"];
    
    if (latestVersion != currentVersion) {
        // Nouvelle version disponible
        String binUrl = doc["channels"][channel][espType]["bin_url"];
        performOTA(binUrl);
    }
}
```

---

### 5️⃣ **OTA - Téléchargement Firmware**

#### **Endpoints Firmware** (selon type ESP32) :

```
GET https://iot.olution.info/ffp3/ota/esp32-s3/firmware.bin        // Pour ESP32-S3
GET https://iot.olution.info/ffp3/ota/esp32-wroom/firmware.bin     // Pour ESP32-WROOM
GET https://iot.olution.info/ffp3/ota/firmware.bin                 // Par défaut
```

#### **Endpoints TEST** :

```
GET https://iot.olution.info/ffp3/ota/test/esp32-s3/firmware.bin
GET https://iot.olution.info/ffp3/ota/test/esp32-wroom/firmware.bin
```

#### **Fréquence** : Seulement si **nouvelle version détectée**

#### **Validation** : Vérifier **MD5** après téléchargement

---

## 🔐 Authentification & Sécurité

### **Méthode 1 : Clé API (Legacy)** ✅ SIMPLE

**Paramètre POST** : `api_key=fdGTMoptd5CD2ert3`

**Configuration serveur** : `.env` variable `API_KEY`

**Sécurité** : ⚠️ Moyenne (clé en clair)

---

### **Méthode 2 : HMAC-SHA256** ✅ RECOMMANDÉ

**Paramètres POST** :
```
timestamp=1697123456
signature=abc123def456...
```

**Génération de la signature (ESP32)** :

```cpp
#include <mbedtls/md.h>

String apiSecret = "votre_secret_hmac"; // Depuis .env: API_SIG_SECRET
unsigned long timestamp = timeClient.getEpochTime();

// Créer le payload
String payload = "timestamp=" + String(timestamp);

// Calculer HMAC-SHA256
byte hmacResult[32];
mbedtls_md_context_t ctx;
mbedtls_md_init(&ctx);
mbedtls_md_setup(&ctx, mbedtls_md_info_from_type(MBEDTLS_MD_SHA256), 1);
mbedtls_md_hmac_starts(&ctx, (const unsigned char*)apiSecret.c_str(), apiSecret.length());
mbedtls_md_hmac_update(&ctx, (const unsigned char*)payload.c_str(), payload.length());
mbedtls_md_hmac_finish(&ctx, hmacResult);
mbedtls_md_free(&ctx);

// Convertir en HEX
String signature = "";
for (int i = 0; i < 32; i++) {
    char hex[3];
    sprintf(hex, "%02x", hmacResult[i]);
    signature += hex;
}

// POST: api_key=...&timestamp=1697123456&signature=abc123...
```

**Validation serveur** : Fenêtre de 300 secondes (5 min) par défaut

**Sécurité** : ✅ Élevée (protection replay attacks)

---

### **Méthode 3 : Double Authentification** ✅ MAXIMUM

Utiliser **les deux** : `api_key` **ET** `timestamp` + `signature`

Le serveur valide les deux méthodes (OR logique).

---

## 📥 Endpoints de Récupération

### **État GPIO Moderne (v4.0.0)** 🆕

#### **Endpoint Simple** :
```
GET https://iot.olution.info/ffp3/api/outputs/state
```

#### **Endpoint Compatible Ancien** :
```
GET https://iot.olution.info/ffp3/ffp3datas/api/outputs/state
```
⚡ Réécrit automatiquement par `.htaccess`

#### **Réponse JSON** :
```json
{
  "16": 1,
  "18": 0,
  "100": 1,
  "101": 0,
  "104": 0,
  "108": 0,
  "109": 1,
  "110": 0
}
```

---

### **État GPIO avec Métadonnées** 🆕

#### **Endpoint** :
```
GET https://iot.olution.info/ffp3/api/realtime/outputs/state
```

#### **Réponse JSON Enrichie** :
```json
{
  "timestamp": 1697123456,
  "outputs": [
    {
      "id": 1,
      "gpio": 16,
      "name": "Pompe Aquarium",
      "state": 1,
      "board": "ESP32-Main"
    },
    {
      "id": 2,
      "gpio": 18,
      "name": "Pompe Réserve",
      "state": 0,
      "board": "ESP32-Main"
    }
  ]
}
```

**Avantage** : Métadonnées complètes (noms, boards, timestamp)

---

## 🆕 Nouveaux Endpoints v4.0.0 (Optionnels)

Ces endpoints sont disponibles mais **pas obligatoires** pour l'ESP32 :

### **Dernières Lectures Capteurs** :
```
GET https://iot.olution.info/ffp3/api/realtime/sensors/latest
```

**Réponse** :
```json
{
  "timestamp": 1697123456,
  "reading_time": "2025-10-11 14:30:00",
  "sensors": {
    "EauAquarium": 32.0,
    "EauReserve": 78.0,
    "TempEau": 24.0,
    "TempAir": 22.5,
    "Humidite": 65.0,
    "Luminosite": 850
  }
}
```

### **Santé du Système** :
```
GET https://iot.olution.info/ffp3/api/realtime/system/health
```

**Réponse** :
```json
{
  "online": true,
  "last_reading": "2025-10-11 14:30:00",
  "last_reading_ago_seconds": 125,
  "uptime_percentage": 98.7,
  "readings_today": 480,
  "average_latency_seconds": 3.5
}
```

---

## 📊 Mapping GPIO ↔ Fonctions

| GPIO | Nom | Fonction | Type |
|------|-----|----------|------|
| **16** | Pompe Aquarium | Circulation eau aquarium | Relais |
| **18** | Pompe Réserve | Remplissage depuis réserve | Relais |
| **100** | Chauffage | Chauffage eau aquarium | Relais |
| **101** | UV | Stérilisation UV | Relais |
| **104** | LEDs | Éclairage cultures | Relais |
| **108** | Nourriture Petits | Distributeur petits poissons | Relais |
| **109** | Nourriture Gros | Distributeur gros poissons | Relais |
| **110** | Reset ESP | Reset/Reboot ESP32 | Trigger |

---

## 🔄 Workflow Complet ESP32 (Loop Principal)

```cpp
void loop() {
    // Toutes les 2-3 minutes
    if (millis() - lastSendTime >= 180000) {
        
        // 1. LIRE tous les capteurs
        float tempAir = readTempAir();
        float humidite = readHumidity();
        float tempEau = readTempWater();
        float eauAqua = readWaterLevelAquarium();
        float eauReserve = readWaterLevelTank();
        float eauPotager = readWaterLevelGarden();
        float lux = readLuminosity();
        
        // 2. ENVOYER les données au serveur
        HTTPClient http;
        http.begin("https://iot.olution.info/ffp3/public/post-data");
        http.addHeader("Content-Type", "application/x-www-form-urlencoded");
        
        String postData = "api_key=fdGTMoptd5CD2ert3";
        postData += "&sensor=ESP32-Main";
        postData += "&version=10.90";
        postData += "&TempAir=" + String(tempAir);
        postData += "&Humidite=" + String(humidite);
        postData += "&TempEau=" + String(tempEau);
        postData += "&EauAquarium=" + String(eauAqua);
        postData += "&EauReserve=" + String(eauReserve);
        postData += "&EauPotager=" + String(eauPotager);
        postData += "&Luminosite=" + String(lux);
        postData += "&etatPompeAqua=" + String(digitalRead(16));
        postData += "&etatPompeTank=" + String(digitalRead(18));
        postData += "&etatHeat=" + String(digitalRead(100));
        // ...
        
        int httpCode = http.POST(postData);
        
        if (httpCode == 200) {
            Serial.println("✅ Données envoyées");
        } else {
            Serial.println("❌ Erreur: " + String(httpCode));
        }
        http.end();
        
        // 3. RÉCUPÉRER l'état des GPIO depuis le serveur
        http.begin("https://iot.olution.info/ffp3/api/outputs/state");
        httpCode = http.GET();
        
        if (httpCode == 200) {
            String json = http.getString();
            DynamicJsonDocument doc(1024);
            deserializeJson(doc, json);
            
            // Appliquer les états
            digitalWrite(16, doc["16"]);   // Pompe Aqua
            digitalWrite(18, doc["18"]);   // Pompe Tank
            digitalWrite(100, doc["100"]); // Chauffage
            digitalWrite(101, doc["101"]); // UV
            digitalWrite(104, doc["104"]); // LEDs
            digitalWrite(108, doc["108"]); // Nourriture petits
            digitalWrite(109, doc["109"]); // Nourriture gros
            
            Serial.println("✅ GPIO mis à jour");
        }
        http.end();
        
        lastSendTime = millis();
    }
    
    // 4. HEARTBEAT (toutes les 10 minutes)
    if (millis() - lastHeartbeat >= 600000) {
        sendHeartbeat();
        lastHeartbeat = millis();
    }
    
    // 5. CHECK OTA (toutes les 24h)
    if (millis() - lastOTACheck >= 86400000) {
        checkOTAUpdate();
        lastOTACheck = millis();
    }
}
```

---

## 🔄 Migration vers v4.0.0

### **Changements Côté ESP32** : ⚠️ **AUCUN CHANGEMENT REQUIS**

Grâce à la règle `.htaccess` de compatibilité, **tous vos endpoints existants continuent de fonctionner** :

| Endpoint ESP32 Actuel | Statut | Action Requise |
|----------------------|--------|----------------|
| `POST /public/post-data` | ✅ Fonctionne | Aucune |
| `GET /api/outputs/state` | ✅ Fonctionne | Aucune |
| `POST /heartbeat.php` | ✅ Fonctionne | Aucune |
| `GET /ota/metadata.json` | ✅ Fonctionne | Aucune |

---

### **Nouveaux Endpoints Disponibles** (Optionnels)

Si vous mettez à jour le firmware ESP32, vous **pouvez** utiliser :

#### **Endpoint Moderne** (actuellement utilisé) :
```cpp
GET /ffp3/api/outputs/state
```

**Avantages par rapport à l'ancien système** :
- ✅ Plus court et plus clair
- ✅ Architecture REST moderne
- ✅ Compatible PROD/TEST automatiquement
- ✅ Pas besoin du paramètre `board`
- ✅ JSON propre et simple

---

## 🛠️ Tables de Base de Données

### **ffp3Data / ffp3Data2** (Lectures Capteurs)

Colonnes insérées par `/post-data` :

| Colonne | Type | Source POST |
|---------|------|-------------|
| `id` | AUTO_INCREMENT | - |
| `TempAir` | FLOAT | `TempAir` |
| `Humidite` | FLOAT | `Humidite` |
| `TempEau` | FLOAT | `TempEau` |
| `EauPotager` | FLOAT | `EauPotager` |
| `EauAquarium` | FLOAT | `EauAquarium` |
| `EauReserve` | FLOAT | `EauReserve` |
| `diffMaree` | FLOAT | `diffMaree` |
| `Luminosite` | FLOAT | `Luminosite` |
| `etatPompeAqua` | TINYINT | `etatPompeAqua` |
| `etatPompeTank` | TINYINT | `etatPompeTank` |
| `etatHeat` | TINYINT | `etatHeat` |
| `etatUV` | TINYINT | `etatUV` |
| `bouffePetits` | TINYINT | `bouffePetits` |
| `bouffeGros` | TINYINT | `bouffeGros` |
| `bouffeMatin` | TINYINT | `bouffeMatin` |
| `bouffeMidi` | TINYINT | `bouffeMidi` |
| `bouffeSoir` | TINYINT | `bouffeSoir` |
| `reading_time` | DATETIME | AUTO (NOW()) |

### **ffp3Outputs / ffp3Outputs2** (Configuration GPIO)

Colonnes lues par ESP32 :

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT | ID unique |
| `gpio` | INT | Numéro GPIO (16, 18, 100, etc.) |
| `name` | VARCHAR | Nom descriptif |
| `state` | TINYINT | État (0=OFF, 1=ON) |
| `board` | VARCHAR | Identifiant board ("ESP32-Main") |

### **ffp3Heartbeat** (Monitoring)

| Colonne | Type | Source POST |
|---------|------|-------------|
| `id` | AUTO_INCREMENT | - |
| `uptime` | INT | `uptime` (minutes) |
| `freeHeap` | INT | `free` (bytes) |
| `minHeap` | INT | `min` (bytes) |
| `reboots` | INT | `reboots` (count) |
| `timestamp` | DATETIME | AUTO (NOW()) |

---

## 📋 Récapitulatif Global

| Endpoint | Méthode | Fréquence | Authentification | Usage |
|----------|---------|-----------|------------------|-------|
| `/public/post-data` | POST | 2-3 min | api_key OU HMAC | **Envoi données capteurs** |
| `/ffp3datas/api/outputs/state` | GET | 2-3 min | Aucune | **Récupération état GPIO** |
| `/heartbeat.php` | POST | 5-10 min | CRC32 | **Keep-alive système** |
| `/ota/metadata.json` | GET | 24h | Aucune | **Check version firmware** |
| `/ota/esp32-xxx/firmware.bin` | GET | Si update | Aucune | **Téléchargement OTA** |

---

## 🔧 Configuration Serveur Nécessaire

### **Variables .env Requises** :

```env
# Authentification
API_KEY=fdGTMoptd5CD2ert3
API_SIG_SECRET=votre_secret_hmac_256_bits
SIG_VALID_WINDOW=300

# Base de données
DB_HOST=localhost
DB_NAME=oliviera_iot
DB_USER=oliviera_iot
DB_PASS="Iot#Olution1"

# Environnement
ENV=prod    # ou "test"
```

### **Fichiers .htaccess Requis** :

**Racine `/ffp3/.htaccess`** :
```apache
# Compatibilité ESP32
RewriteRule ^ffp3datas/api/(.*)$ api/$1 [L]

# Router général
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ public/index.php [L]
```

---

## 🐛 Résolution de Problèmes

### **Erreur 401 : Clé API incorrecte**

**Causes** :
- api_key manquante dans POST
- api_key ne correspond pas à `.env`
- Espaces dans la clé

**Solution** :
```cpp
String apiKey = "fdGTMoptd5CD2ert3"; // Vérifier exactitude
postData += "&api_key=" + apiKey;
```

### **Erreur 500 : Erreur serveur**

**Causes** :
- Erreur SQL (colonnes manquantes, contraintes)
- PHP fatal error
- Variables .env manquantes

**Diagnostic** :
```bash
# Sur le serveur
tail -f /home4/oliviera/iot.olution.info/ffp3/error_log
tail -f /home4/oliviera/iot.olution.info/ffp3/cronlog.txt
```

### **Timeout / Pas de réponse**

**Causes** :
- Serveur down
- Problème réseau ESP32
- URL incorrecte

**Test manuel** :
```bash
curl -X POST https://iot.olution.info/ffp3/public/post-data \
  -d "api_key=fdGTMoptd5CD2ert3&TempAir=22.5"
```

### **GPIO ne changent pas d'état**

**Vérifications** :
1. ESP32 reçoit bien le JSON (Serial.println)
2. JSON est valide (tester avec curl)
3. Parsing JSON fonctionne
4. digitalWrite() est appelé

**Test manuel** :
```bash
# Voir l'état actuel
curl https://iot.olution.info/ffp3/ffp3datas/api/outputs/state

# Changer un état via interface web
# Puis re-curl pour vérifier
```

---

## 🧪 Tests Manuels

### **Test POST données** :

```bash
curl -X POST "https://iot.olution.info/ffp3/public/post-data" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "api_key=fdGTMoptd5CD2ert3&sensor=ESP32-Test&TempAir=22.5&Humidite=65&TempEau=24&EauAquarium=32&EauReserve=78&EauPotager=45&Luminosite=850&etatPompeAqua=1&etatPompeTank=0"
```

**Attendu** : `"Données enregistrées avec succès"`

### **Test GET GPIO** :

```bash
curl "https://iot.olution.info/ffp3/ffp3datas/api/outputs/state"
```

**Attendu** : `{"16":1,"18":0,"100":1,...}`

### **Test Heartbeat** :

```bash
# Calculer CRC32 de "uptime=100&free=200000&min=190000&reboots=5"
# Résultat exemple: 12AB34CD

curl -X POST "https://iot.olution.info/ffp3/heartbeat.php" \
  -d "uptime=100&free=200000&min=190000&reboots=5&crc=12AB34CD"
```

---

## 📚 Documentation Complémentaire

- **README.md** : Architecture générale du projet
- **IMPLEMENTATION_REALTIME_PWA.md** : Guide technique v4.0.0
- **QUICKSTART_V4.md** : Démarrage rapide serveur
- **SERVEUR_DEPLOY.md** : Instructions de déploiement
- **CHANGELOG.md** : Historique des versions

---

## 🎯 Checklist Développeur ESP32

Avant de modifier le code ESP32, vérifier :

- [ ] URL serveur correcte : `https://iot.olution.info/ffp3/`
- [ ] api_key correcte : `fdGTMoptd5CD2ert3`
- [ ] Timeout HTTP : minimum 5 secondes
- [ ] Retry en cas d'échec : 3 tentatives
- [ ] Tous les capteurs initialisés
- [ ] GPIO correctement configurés (pinMode)
- [ ] Intervalle : 2-3 minutes (180000 ms)
- [ ] NTP configuré (pour timestamp HMAC)

---

**Fin de la Documentation API ESP32**  
**Version** : 4.0.0  
**Dernière mise à jour** : 11 octobre 2025

