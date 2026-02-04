# 🌐 Endpoints ESP32 ↔ Serveur - Configuration Complète

**Version ESP32**: 11.35  
**Version Serveur**: 11.36  
**Date**: 3 Février 2026  

---

## 📍 Endpoints Utilisés par ESP32

### Environnement Actif: **TEST** (`wroom-test`)

**Configuration**: `platformio.ini` ligne 90
```ini
[env:wroom-test]
build_flags = 
    -DPROFILE_TEST  ← Environnement TEST actif
```

**Endpoints** (`include/project_config.h` lignes 60-68):

#### 1️⃣ POST Data (Envoi données capteurs + états)
```cpp
POST_DATA_ENDPOINT = "/ffp3/post-data-test"
```

**URL Complète**:
```
http://iot.olution.info/ffp3/post-data-test
```

**Route serveur (Slim)**:
```
/ffp3/post-data-test → PostDataController::handle()
```

#### 2️⃣ GET Outputs State (Récupération états distants)
```cpp
OUTPUT_ENDPOINT = "/ffp3/api/outputs-test/state"
```

**URL Complète**:
```
http://iot.olution.info/ffp3/api/outputs-test/state
```

**Fichier serveur**:
```
/path/to/ffp3/public/index.php  ← Route Slim Framework
  └─> OutputController::getOutputsState()
```

---

## 🔄 Comparaison Environnements

| Aspect | TEST (wroom-test) | PROD (wroom-prod) |
|--------|-------------------|-------------------|
| **Profil** | `PROFILE_TEST` | `PROFILE_PROD` |
| **Endpoint POST** | `/ffp3/post-data-test` | `/ffp3/post-data` |
| **Endpoint GET** | `/ffp3/api/outputs-test/state` | `/ffp3/api/outputs/state` |
| **Table Data** | `ffp3Data2` | `ffp3Data` |
| **Table Outputs** | `ffp3Outputs2` | `ffp3Outputs` |

---

## ⏱ Timeouts côté serveur (POST ≤ 8 s)

Le client ESP32 utilise un **timeout POST de 8 s** (dérogation à la règle projet 5 s, documentée dans `include/config.h` — `HTTP_POST_TIMEOUT_MS`). Le serveur doit répondre dans ce délai.

- **PHP** : `PostDataController::handle()` appelle `set_time_limit(10)` au début de la requête (marge par rapport aux 8 s client).
- **À vérifier sur l'hébergement** :
  - `max_execution_time` (php.ini) ≥ 10 s pour les requêtes POST `/ffp3/post-data` et `/ffp3/post-data-test`.
  - Nginx : `proxy_read_timeout` (et éventuellement `fastcgi_read_timeout`) ≥ 10 s.
  - Apache : `Timeout` et `ProxyTimeout` ≥ 10 s si reverse proxy vers PHP.
- **Réduction de latence** : la route POST fait 1 INSERT (données capteurs) + 1 UPDATE groupé (états GPIO via `CASE gpio WHEN … THEN … END`) + 1 UPDATE (dernière requête board) + invalidation cache en mémoire, pour limiter le nombre d'allers-retours BDD.

---

## 🔧 Diagnostic : « Les commandes distantes n’ont pas d’effet sur l’ESP32 »

**Contrat côté serveur** : les commandes envoyées depuis la page de contrôle (toggle, paramètres) sont enregistrées dans la table **correspondant à l’environnement de la page** :

- **Page `/control` (PROD)** → toggle/paramètres → table **ffp3Outputs** (PROD).
- **Page `/control-test` (TEST)** → toggle/paramètres → table **ffp3Outputs2** (TEST).

**Pour que l’ESP32 applique ces commandes**, il doit **lire la même table** en faisant un GET sur le **même environnement** :

- Si vous pilotez depuis **control-test** : l’ESP32 doit faire `GET /ffp3/api/outputs-test/state` (table ffp3Outputs2).
- Si vous pilotez depuis **control** (prod) : l’ESP32 doit faire `GET /ffp3/api/outputs/state` (table ffp3Outputs).

**À vérifier en priorité (côté ESP32)** :

1. **URL de poll** : l’ESP32 utilise-t-il `/api/outputs-test/state` quand vous êtes en env test, et `/api/outputs/state` en prod ?
2. **Application des valeurs** : le firmware applique-t-il bien les champs reçus (GPIO, state) aux relais/paramètres après chaque GET réussi ?

Si l’URL de poll et la page de contrôle sont sur le même environnement (test↔test ou prod↔prod), le serveur renvoie bien les dernières valeurs écrites par la page. Si l’effet n’apparaît pas sur l’ESP32, la cause est alors côté firmware (poll, parsing ou application des états).

---

## 🔍 Diagnostic Erreur HTTP 500

Si `/ffp3/post-data-test` ou `/ffp3/post-data` renvoie 500 :

### Possibilité 1: **Erreur PHP côté Slim**
```
Consulter les logs PHP/serveur web (ex: /var/log/apache2/error.log)
```

### Possibilité 2: **Erreur SQL (INSERT/UPDATE)**
Vérifier que **tous les GPIO existent** dans `ffp3Outputs2` :

```sql
SELECT gpio, name, state 
FROM ffp3Outputs2 
WHERE gpio IN (2, 15, 16, 18, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116)
ORDER BY gpio;

-- Doit retourner 21 lignes
-- Si lignes manquantes, exécuter: ffp3/migrations/INIT_GPIO_BASE_ROWS.sql
```

---

## 📊 Résumé Endpoints

### ESP32 → Serveur (POST)

**Environnement TEST** (wroom-test actuel):
```
URL: http://iot.olution.info/ffp3/post-data-test
Route: /ffp3/post-data-test (Slim → PostDataController::handle)
Méthode: POST
Content-Type: application/x-www-form-urlencoded

Payload (31 paramètres):
api_key=fdGTMoptd5CD2ert3
&sensor=esp32-wroom
&version=11.35
&TempAir=28.0
&Humidite=60.0
&TempEau=28.0
&EauPotager=209
&EauAquarium=209
&EauReserve=209
&diffMaree=0
&Luminosite=813
&etatPompeAqua=0
&etatPompeTank=0
&etatHeat=0          ← État chauffage
&etatUV=1
&bouffeMatin=8
&bouffeMidi=12
&bouffeSoir=19
&tempsGros=2
&tempsPetits=2
&aqThreshold=18
&tankThreshold=80
&chauffageThreshold=18
&mail=oliv.arn.lau@gmail.com
&mailNotif=checked
&resetMode=0
&tempsRemplissageSec=5
&limFlood=8
&WakeUp=0
&FreqWakeUp=6
&bouffePetits=0
&bouffeGros=0

Actions serveur:
1. INSERT INTO ffp3Data2 (sans tempsGros/tempsPetits/tempsRemplissageSec/limFlood/WakeUp/FreqWakeUp)
2. UPDATE ffp3Outputs2 (17 GPIO) ← CRITIQUE pour chauffage
Note: les durées/limites/wake-up sont stockées uniquement dans ffp3Outputs2.
```

### Serveur → ESP32 (GET)

**Environnement TEST** (wroom-test actuel):
```
URL: http://iot.olution.info/ffp3/api/outputs-test/state
Fichier: /path/to/ffp3/public/index.php
Route: Slim Framework → OutputController::getOutputsState()
Méthode: GET

Réponse JSON (17 paramètres):
{
  "16": "0",           // pump_aqua
  "pump_aqua": "0",
  "18": 0,             // pump_tank
  "pump_tank": 0,
  "2": "0",            // heat ← État chauffage lu
  "heat": "0",
  "15": "1",           // light
  "light": "1",
  "101": "1",          // mailNotif
  "mailNotif": "1",
  "115": "0",          // WakeUp
  "WakeUp": "0",
  "108": "1",          // bouffePetits
  "109": "1",          // bouffeGros
  "110": "0",          // resetMode
  "100": "oliv.arn.lau@gmail.com",  // mail
  "mail": "oliv.arn.lau@gmail.com",
  "102": "18",         // aqThr
  "aqThr": "18",
  "103": "80",         // taThr
  "taThr": "80",
  "104": "18",         // chauff
  "chauff": "18",
  "105": "8",          // bouffeMat
  "bouffeMat": "8",
  "106": "12",         // bouffeMid
  "bouffeMid": "12",
  "107": "19",         // bouffeSoir
  "bouffeSoir": "19",
  "111": "2",          // tempsGros
  "tempsGros": "2",
  "112": "2",          // tempsPetits
  "tempsPetits": "2",
  "113": "5",          // tempsRemplissageSec
  "tempsRemplissageSec": "5",
  "114": "8",          // limFlood
  "limFlood": "8",
  "116": "6",          // FreqWakeUp
  "FreqWakeUp": "6"
}

Source: SELECT gpio, state FROM ffp3Outputs2
```

---

## 🎯 Conclusion

### **L'ancien fichier legacy fait DÉJÀ tout correctement !**

✅ Il met à jour **TOUS les GPIO** nécessaires (17)  
✅ Le chauffage **DEVRAIT** rester allumé

### **Donc pourquoi HTTP 500 ?**

Possibilités :
1. ❌ Erreur PHP dans `PostDataController::handle`
2. ❌ Erreur SQL (GPIO manquant dans ffp3Outputs2)
3. ❌ Problème permissions MySQL
4. ❌ Erreur PHP (variables undefined, payload inattendu)

---

## 🔧 Action Immédiate

**Vérifier les logs serveur PHP** pour voir l'erreur exacte :

```bash
ssh user@iot.olution.info
tail -f /var/log/apache2/error.log
# OU
tail -f /path/to/ffp3/error_log
```

Ou créer un fichier de test pour diagnostiquer :
```php
// test-post.php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Tester connexion BDD
$conn = new mysqli("localhost", "oliviera_iot", "Iot#Olution1", "oliviera_iot");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "BDD OK\n";

// Tester table existe
$result = $conn->query("SHOW TABLES LIKE 'ffp3Data2'");
echo "Table ffp3Data2: " . ($result->num_rows > 0 ? "EXISTS" : "NOT FOUND") . "\n";

// Tester GPIO existe
$result = $conn->query("SELECT COUNT(*) as c FROM ffp3Outputs2 WHERE gpio IN (2,15,16,18,100,101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116)");
$row = $result->fetch_assoc();
echo "GPIO count: " . $row['c'] . " (attendu: 21)\n";
?>
```

Veux-tu que je crée un script de diagnostic complet pour identifier l'erreur exacte ?
