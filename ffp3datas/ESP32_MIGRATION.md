# Migration ESP32 - Endpoints Modernes

## 📡 Changement d'URL pour ESP32

Les endpoints pour ESP32 ont été modernisés. Voici le guide de migration.

## 🔄 URLs Anciennes vs Nouvelles

### 1. Récupération des états GPIO (utilisé par ESP32)

#### ANCIEN (legacy)
```
GET /ffp3/ffp3control/ffp3-outputs-action.php?action=outputs_state&board=1
```

#### NOUVEAU (moderne)
```
GET /ffp3/ffp3datas/public/api/outputs/states/1
```

**Format de réponse** : Identique
```json
{"2":"1","3":"0","4":"1","5":"0","6":"1","7":"0","8":"1"}
```

---

### 2. Mise à jour d'un output

#### ANCIEN (legacy)
```
GET /ffp3/ffp3control/ffp3-outputs-action.php?action=output_update&id=5&state=1
```

#### NOUVEAU (moderne)
```
POST /ffp3/ffp3datas/public/api/outputs/5/state
Content-Type: application/json

{"state": 1}
```

**Note** : Passe de GET à POST avec body JSON

---

## 📋 Environnements PROD/TEST

### Production (tables ffp3Outputs)
```
GET /ffp3/ffp3datas/public/api/outputs/states/{board}
POST /ffp3/ffp3datas/public/api/outputs/{id}/state
```

### Test (tables ffp3Outputs2)
```
GET /ffp3/ffp3datas/public/api/outputs-test/states/{board}
POST /ffp3/ffp3datas/public/api/outputs-test/{id}/state
```

---

## 🔧 Code Arduino/ESP32 - Exemple

### Ancien code (à remplacer)
```cpp
// ANCIEN
String serverPath = "http://iot.olution.info/ffp3/ffp3control/ffp3-outputs-action.php?action=outputs_state&board=1";
```

### Nouveau code (recommandé)
```cpp
// NOUVEAU
String serverPath = "http://iot.olution.info/ffp3/ffp3datas/public/api/outputs/states/1";
```

### Fonction complète de récupération des états

```cpp
#include <HTTPClient.h>
#include <ArduinoJson.h>

// Configuration
const char* serverName = "http://iot.olution.info";
const char* boardId = "1";

void getOutputStates() {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    
    // Construire l'URL
    String serverPath = String(serverName) + "/ffp3/ffp3datas/public/api/outputs/states/" + String(boardId);
    
    http.begin(serverPath.c_str());
    int httpResponseCode = http.GET();
    
    if (httpResponseCode > 0) {
      String payload = http.getString();
      
      // Parser le JSON
      StaticJsonDocument<512> doc;
      DeserializationError error = deserializeJson(doc, payload);
      
      if (!error) {
        // Accéder aux états GPIO
        int gpio2State = doc["2"];
        int gpio3State = doc["3"];
        // etc.
        
        Serial.println("GPIO 2: " + String(gpio2State));
        Serial.println("GPIO 3: " + String(gpio3State));
      } else {
        Serial.println("JSON parsing failed");
      }
    } else {
      Serial.println("HTTP Error: " + String(httpResponseCode));
    }
    
    http.end();
  } else {
    Serial.println("WiFi Disconnected");
  }
}
```

---

## 🔄 Compatibilité Temporaire

Un **proxy de compatibilité** est disponible pour faciliter la transition :

### Utilisation du proxy
```
GET /ffp3/ffp3datas/public/esp32-compat.php?action=outputs_state&board=1
```

Ce proxy redirige automatiquement vers la nouvelle API.

**⚠️ Attention** : Ce proxy est temporaire et sera supprimé dans une version future.
Migrez votre code ESP32 vers les nouvelles URLs dès que possible !

---

## ✅ Avantages de la nouvelle API

1. **URLs RESTful** : Plus propres et standards
2. **JSON structuré** : Meilleure gestion d'erreurs
3. **Codes HTTP** : Utilisation correcte des statuts HTTP
4. **Sécurité** : Validation complète des entrées
5. **Logs** : Traçabilité des actions
6. **Environnements** : Séparation PROD/TEST propre

---

## 🧪 Tests

### Test avec curl (Linux/Mac)
```bash
# Récupérer les états
curl http://iot.olution.info/ffp3/ffp3datas/public/api/outputs/states/1

# Mettre à jour un output (POST JSON)
curl -X POST http://iot.olution.info/ffp3/ffp3datas/public/api/outputs/5/state \
  -H "Content-Type: application/json" \
  -d '{"state": 1}'
```

### Test avec PowerShell (Windows)
```powershell
# Récupérer les états
Invoke-RestMethod -Uri "http://iot.olution.info/ffp3/ffp3datas/public/api/outputs/states/1"

# Mettre à jour un output
$body = @{ state = 1 } | ConvertTo-Json
Invoke-RestMethod -Uri "http://iot.olution.info/ffp3/ffp3datas/public/api/outputs/5/state" `
  -Method Post `
  -Body $body `
  -ContentType "application/json"
```

---

## 📞 Support

En cas de problème lors de la migration :

1. Vérifier que l'ESP32 peut accéder aux nouvelles URLs
2. Vérifier le format JSON de la réponse
3. Consulter les logs serveur : `ffp3datas/actions.log`
4. Utiliser temporairement le proxy de compatibilité

---

**Date de migration** : Décembre 2024
**Support legacy** : Jusqu'à Mars 2025 (proxy de compatibilité)

