# Correction Cycle Infini Pompe Réservoir - Version 4.5.19

**Date** : 13 octobre 2025  
**Ticket** : Pompe réservoir qui se répète sans s'arrêter lorsqu'activée depuis le serveur distant  
**Statut** : ✅ CORRIGÉ

---

## 🔍 Problème identifié

### Symptômes
- ✅ Lorsque la pompe réservoir (refill) est **activée depuis le serveur distant**, elle se déclenche en **boucle infinie**
- ✅ L'ESP32 reçoit en continu des commandes **contradictoires**
- ✅ La pompe démarre/arrête de façon répétée **sans respecter la durée configurée** (`refillDurationMs`)
- ✅ Le problème ne se manifeste **PAS** lors d'une activation depuis l'interface web **locale** de l'ESP32
- ✅ Le problème ne se manifeste **PAS** pour les autres actionneurs (pompe aquarium, lumière, chauffage)

### Impact utilisateur
- ❌ Impossible d'utiliser le refill depuis le serveur distant
- ❌ Risque de sur-remplissage de l'aquarium
- ❌ Usure prématurée de la pompe
- ❌ Logs ESP32 saturés de messages contradictoires

---

## 🔬 Analyse de la cause racine

### Désaccord de logique inversée

Le problème vient d'un **désaccord de logique** entre le serveur distant (PHP) et l'ESP32 (C++) concernant la représentation de l'état de la pompe réservoir.

#### Côté serveur distant (PHP)

**Fichier** : `src/Controller/OutputController.php` (lignes 113-159, ancienne version)  
**Hardware** : GPIO 18 contrôle un relais avec logique inversée

```php
// Dans PumpService.php
public function runPompeTank(): void {
    $this->setState($this->gpioPompeTank, 0);  // GPIO 18 = 0 → pompe ON
}

public function stopPompeTank(): void {
    $this->setState($this->gpioPompeTank, 1);  // GPIO 18 = 1 → pompe OFF
}
```

**Problème** : Le serveur renvoie directement la valeur **brute du GPIO** sans inversion :
```php
// OutputController.php (AVANT correction)
$result['pump_tank'] = $state;  // Si GPIO 18 = 0 → pump_tank=0
```

#### Côté ESP32 (C++)

**Fichier** : `src/automatism/automatism_network.cpp` (lignes 544-553)

```cpp
// L'ESP32 attend une logique NORMALE (pas inversée)
if (doc.containsKey("pump_tank")) {
    auto v = doc["pump_tank"];
    if (isTrue(v)) {                      // pump_tank=1 → pompe ON
        autoCtrl.startTankPumpManual();
    } else if (isFalse(v)) {              // pump_tank=0 → pompe OFF
        autoCtrl.stopTankPumpManual();
    }
}
```

### Scénario du bug (cycle infini)

```
┌─────────────────────────────────────────────────────────────────────┐
│  CYCLE INFINI (AVANT CORRECTION)                                      │
└─────────────────────────────────────────────────────────────────────┘

1. Utilisateur clique "Activer pompe réservoir" sur interface web distante
   ↓
2. Serveur distant écrit : GPIO 18 = 0 (pompe ON selon logique hardware)
   ↓
3. Serveur distant répond à l'ESP32 avec : { "pump_tank": 0 }
   ↓
4. ESP32 lit pump_tank=0 (false) → appelle stopTankPumpManual()
   ↓
5. ESP32 arrête la pompe et envoie : etatPompeTank=0 au serveur
   ↓
6. Serveur garde GPIO 18 = 0 en BDD (pas de changement)
   ↓
7. À la prochaine synchronisation (toutes les 30s) → retour à l'étape 3
   ↓
   ∞ BOUCLE INFINIE ∞
```

### Pourquoi ça ne se produit pas en local ?

Lorsque l'utilisateur active la pompe depuis l'**interface web locale** de l'ESP32 :
- L'ESP32 démarre la pompe directement (sans passer par le serveur)
- Il envoie `etatPompeTank=1` au serveur
- Le serveur écrit GPIO 18 = 0 (logique inversée)
- Mais l'ESP32 **ignore** les commandes distantes tant que `_manualTankOverride = true`
- La pompe fonctionne normalement jusqu'à la fin de la durée configurée

---

## ✅ Solution appliquée

### 1. Inversion de la logique dans OutputController

**Fichier modifié** : `src/Controller/OutputController.php` (lignes 148-154)

**Changement** : Inversion de l'état du GPIO 18 **avant de l'envoyer** à l'ESP32

```php
$result = [];
foreach ($outputs as $output) {
    $gpio = (int)$output['gpio'];
    $state = $output['state'];
    
    // ✅ CORRECTION : Inverser la logique pour la pompe réservoir (GPIO 18)
    // car elle utilise une logique inversée côté hardware
    // GPIO 18 = 0 → pompe ON → on envoie pump_tank=1 à l'ESP32
    // GPIO 18 = 1 → pompe OFF → on envoie pump_tank=0 à l'ESP32
    if ($gpio === 18) {
        $state = $state === 0 ? 1 : 0;  // ← INVERSION ICI
    }
    
    // Ajouter par numéro GPIO (rétrocompatibilité)
    $result[(string)$gpio] = $state;
    
    // Ajouter par nom si mapping existe (nouveau format)
    if (isset($gpioMapping[$gpio])) {
        $result[$gpioMapping[$gpio]] = $state;
    }
}
```

### 2. Tableau comparatif AVANT/APRÈS

| État hardware | GPIO 18 (BDD) | `pump_tank` AVANT | `pump_tank` APRÈS | ESP32 comprend |
|---------------|---------------|-------------------|-------------------|----------------|
| Pompe ON      | 0             | 0 ❌              | 1 ✅              | Pompe ON       |
| Pompe OFF     | 1             | 1 ❌              | 0 ✅              | Pompe OFF      |

### 3. Avantages de cette solution

✅ **Pas de migration BDD nécessaire** : On garde la logique inversée dans `ffp3Outputs`  
✅ **Pas d'impact sur PumpService** : Les méthodes `runPompeTank()` / `stopPompeTank()` restent identiques  
✅ **Pas d'impact sur l'interface web distante** : Les boutons de contrôle restent fonctionnels  
✅ **Transparent pour l'utilisateur** : Aucun changement visible de comportement  
✅ **Correction ciblée** : Seul GPIO 18 est affecté, pas les autres actionneurs  
✅ **Compatible avec l'existant** : Pas de rupture de compatibilité  

---

## 📊 Tests à effectuer après déploiement

### Test 1 : Activation depuis serveur distant
1. ✅ Ouvrir l'interface web distante (aquaponie.twig)
2. ✅ Cliquer sur le bouton "Activer pompe réservoir"
3. ✅ **Attendre** : La pompe doit s'arrêter automatiquement après `refillDurationMs`
4. ✅ **Vérifier logs ESP32** : Pas de messages répétés de démarrage/arrêt
5. ✅ **Vérifier BDD** : GPIO 18 doit passer de 1 à 0 puis revenir à 1

### Test 2 : Arrêt manuel depuis serveur distant
1. ✅ Activer la pompe depuis le serveur distant
2. ✅ **Avant la fin de la durée**, cliquer sur "Arrêter pompe réservoir"
3. ✅ **Vérifier** : La pompe s'arrête immédiatement
4. ✅ **Vérifier logs ESP32** : Message d'arrêt manuel

### Test 3 : Activation depuis interface locale ESP32
1. ✅ Se connecter à l'interface web **locale** de l'ESP32 (http://IP_ESP32)
2. ✅ Activer la pompe réservoir
3. ✅ **Vérifier** : Comportement inchangé (doit fonctionner comme avant)

### Test 4 : Synchronisation état
1. ✅ Activer la pompe depuis le serveur distant
2. ✅ Ouvrir l'interface web **locale** de l'ESP32
3. ✅ **Vérifier** : L'interface locale affiche bien la pompe comme "active"
4. ✅ Attendre la fin de la durée configurée
5. ✅ **Vérifier** : Les deux interfaces (locale et distante) affichent "pompe inactive"

### Test 5 : Autres actionneurs (non régression)
1. ✅ Tester la pompe aquarium (ON/OFF depuis serveur distant)
2. ✅ Tester la lumière (ON/OFF depuis serveur distant)
3. ✅ Tester le chauffage (ON/OFF depuis serveur distant)
4. ✅ **Vérifier** : Tous fonctionnent normalement

---

## 🚀 Procédure de déploiement

### 1. Prérequis
- Accès SSH au serveur distant
- Droits d'écriture sur le répertoire du projet
- Accès à la console de logs de l'ESP32 (facultatif mais recommandé)

### 2. Déploiement

```bash
# 1. Se connecter au serveur distant
ssh user@serveur-distant

# 2. Naviguer vers le répertoire du projet
cd /chemin/vers/ffp3

# 3. Sauvegarder l'ancienne version (IMPORTANT)
cp src/Controller/OutputController.php src/Controller/OutputController.php.backup_v4.5.18

# 4. Récupérer les modifications depuis Git
git pull origin main

# 5. Vérifier la version
cat VERSION
# Doit afficher : 4.5.19

# 6. Vérifier que le code est bien appliqué
grep -A 5 "GPIO 18" src/Controller/OutputController.php
# Doit afficher l'inversion de la logique

# 7. Aucune commande supplémentaire nécessaire (pas de composer install, pas de migration BDD)
```

### 3. Vérification immédiate

```bash
# Vérifier les logs en temps réel (depuis un autre terminal)
tail -f /var/log/nginx/ffp3_access.log

# Déclencher un test depuis l'interface web distante
# → Activer la pompe réservoir
# → Vérifier dans les logs ESP32 qu'il n'y a plus de cycle infini
```

### 4. Rollback (en cas de problème)

```bash
# Restaurer l'ancienne version
cd /chemin/vers/ffp3
cp src/Controller/OutputController.php.backup_v4.5.18 src/Controller/OutputController.php

# Forcer le rechargement PHP-FPM (si nécessaire)
sudo systemctl reload php8.2-fpm
```

---

## 📋 Checklist d'application

### 1. Code (✅ FAIT)
- [x] Modifier `OutputController.php` (inversion logique GPIO 18)
- [x] Incrémenter VERSION (4.5.18 → 4.5.19)
- [x] Mettre à jour CHANGELOG.md
- [x] Créer documentation (ce fichier)

### 2. Git (⏳ À FAIRE)
- [ ] `git add .`
- [ ] `git commit -m "fix: correction cycle infini pompe réservoir v4.5.19"`
- [ ] `git push origin main`

### 3. Déploiement serveur (⏳ À FAIRE)
- [ ] Sauvegarder `OutputController.php` (backup)
- [ ] `git pull` sur le serveur distant
- [ ] Vérifier VERSION (doit afficher 4.5.19)
- [ ] (Facultatif) Recharger PHP-FPM : `sudo systemctl reload php8.2-fpm`

### 4. Tests (⏳ À FAIRE)
- [ ] Test 1 : Activation depuis serveur distant + attente fin durée
- [ ] Test 2 : Arrêt manuel depuis serveur distant
- [ ] Test 3 : Activation depuis interface locale (non régression)
- [ ] Test 4 : Synchronisation état entre local et distant
- [ ] Test 5 : Autres actionneurs (pompe aqua, lumière, chauffage)

### 5. Validation finale (⏳ À FAIRE)
- [ ] Vérifier logs ESP32 : pas de messages répétés
- [ ] Vérifier BDD : GPIO 18 respecte la logique inversée
- [ ] Vérifier interface web : affichage cohérent
- [ ] Documenter résultats des tests (ci-dessous)

---

## 📝 Résultats des tests

### Test 1 : Activation depuis serveur distant
- **Date** : _____________________
- **Durée configurée** : _____ secondes
- **Durée réelle** : _____ secondes
- **Résultat** : ⬜ OK / ⬜ KO
- **Notes** : 

### Test 2 : Arrêt manuel
- **Date** : _____________________
- **Résultat** : ⬜ OK / ⬜ KO
- **Notes** : 

### Test 3 : Interface locale
- **Date** : _____________________
- **Résultat** : ⬜ OK / ⬜ KO
- **Notes** : 

### Test 4 : Synchronisation
- **Date** : _____________________
- **Résultat** : ⬜ OK / ⬜ KO
- **Notes** : 

### Test 5 : Autres actionneurs
- **Date** : _____________________
- **Résultat** : ⬜ OK / ⬜ KO
- **Notes** : 

---

## 🎓 Leçons apprises

### Importance de la documentation
- Un même actionneur peut avoir des logiques différentes selon la couche (hardware vs applicative)
- Documenter explicitement les inversions de logique dans le code

### Tests de non-régression
- Toujours tester les autres actionneurs après une modification même ciblée
- Vérifier la synchronisation entre interfaces locale et distante

### Débogage
- Les cycles infinis sont souvent causés par des désaccords de protocole/logique entre systèmes
- Examiner les logs des deux côtés (ESP32 et serveur) pour identifier le point de divergence

---

## 📚 Références

- `src/Controller/OutputController.php` : Endpoint `/outputs/state`
- `src/Service/PumpService.php` : Logique hardware des pompes
- `src/automatism/automatism_network.cpp` (ESP32) : Gestion des commandes distantes
- `CHANGELOG.md` : Historique complet des versions

---

**Auteur** : Assistant IA - Expert ESP32  
**Version document** : 1.0  
**Dernière mise à jour** : 13 octobre 2025

