# Gestion des Fuseaux Horaires - FFP3 Datas

## 📍 Situation Géographique

Le projet FFP3 présente une particularité géographique importante :

- 🌍 **Projet physique (aquaponie, ESP32)** : Situé à **Casablanca, Maroc** (`Africa/Casablanca`)
- 🖥️ **Serveur web** : Hébergé à **Paris, France** (`Europe/Paris`)

Cette différence crée des implications pour la gestion des timestamps et l'affichage des données.

---

## ⚙️ Configuration Actuelle

### Timezone du Projet

Le projet utilise actuellement **`Europe/Paris`** comme timezone unique :

```env
# .env
APP_TIMEZONE=Europe/Paris
```

Cette configuration est appliquée globalement dans `src/Config/Env.php` :

```php
private static function configureTimezone(): void
{
    $timezone = $_ENV['APP_TIMEZONE'] ?? 'Europe/Paris';
    date_default_timezone_set($timezone);
}
```

### Impact sur les Timestamps

- ✅ **Tous les timestamps PHP** sont en heure de Paris
- ✅ **Base de données** : Les `reading_time` sont stockés en heure de Paris
- ✅ **Graphiques Highcharts** : Configurés pour afficher l'heure de Paris via `moment-timezone`

---

## 🕐 Différence Horaire

### Heure d'été (mars à octobre)

- **Paris** : UTC+2 (CEST - Central European Summer Time)
- **Casablanca** : UTC+1 (WEST - Western European Summer Time)
- **Différence** : **+1 heure** (Paris en avance)

### Heure d'hiver (octobre à mars)

- **Paris** : UTC+1 (CET - Central European Time)
- **Casablanca** : UTC+1 (WET avec DST - Western European Time)
- **Différence** : **0 heure** (même heure)

> ⚠️ **Note importante** : Le Maroc a des règles DST (Daylight Saving Time) spécifiques qui peuvent différer de l'Europe, notamment pendant le Ramadan où le DST peut être suspendu.

---

## 🎯 Implications Pratiques

### 1. Affichage Web

Les données affichées sur le dashboard web sont en **heure de Paris**.

**Exemple :**
- L'ESP32 à Casablanca envoie une mesure à **14h00 locale** (Casablanca)
- Le serveur l'enregistre et l'affiche comme **15h00** (Paris, en été)

### 2. ESP32 et Envoi des Données

Deux approches possibles pour l'ESP32 :

#### Option A : ESP32 en heure locale de Casablanca (recommandé)

```cpp
// ESP32 configuré sur Africa/Casablanca
configTime(0, 0, "pool.ntp.org");
setenv("TZ", "Africa/Casablanca", 1);
tzset();
```

✅ **Avantages** :
- Cohérent avec la réalité physique du système
- Les logs ESP32 sont en heure locale
- Debugging plus facile sur site

⚠️ **Attention** : Le serveur affichera les données avec +1h en été

#### Option B : ESP32 synchronisé sur Paris

```cpp
// ESP32 configuré sur Europe/Paris
configTime(0, 0, "pool.ntp.org");
setenv("TZ", "Europe/Paris", 1);
tzset();
```

✅ **Avantages** :
- Cohérence parfaite avec les timestamps affichés
- Pas de conversion nécessaire

❌ **Inconvénients** :
- Les logs locaux de l'ESP32 sont en heure de Paris (confusion sur site)

### 3. Graphiques et Visualisations

Les graphiques Highcharts utilisent `moment-timezone` pour afficher l'heure de Paris :

```javascript
moment.tz.setDefault('Europe/Paris');
```

Si vous souhaitez afficher l'heure de Casablanca dans les graphiques, modifiez dans `aquaponie.twig` :

```javascript
moment.tz.setDefault('Africa/Casablanca');
```

---

## 🔄 Changement de Timezone

### Pour passer à l'heure de Casablanca

Si vous souhaitez que tout le système utilise l'heure de Casablanca :

#### 1. Modifier `.env`

```env
APP_TIMEZONE=Africa/Casablanca
```

#### 2. Modifier les templates Twig

Dans `aquaponie.twig`, `dashboard.twig`, `tide_stats.twig` :

```javascript
// Anciennement
moment.tz.setDefault('Europe/Paris');

// Nouveau
moment.tz.setDefault('Africa/Casablanca');
```

#### 3. ⚠️ Migration des données existantes

**ATTENTION** : Les données déjà en base sont en heure de Paris. Vous avez deux options :

**Option A : Ne rien faire (recommandé)**
- Les anciennes données restent en heure de Paris
- Les nouvelles données seront en heure de Casablanca
- Discontinuité d'1h dans les graphiques historiques

**Option B : Convertir les timestamps (complexe)**
```sql
-- BACKUP D'ABORD !
-- Convertir tous les timestamps de Paris vers Casablanca (-1h en été)
-- NE PAS EXÉCUTER sans validation complète
```

---

## 🎓 Recommandations

### Pour l'Utilisateur Final

Si l'utilisateur principal est à **Casablanca** :
- ✅ **Recommandé** : Passer à `Africa/Casablanca`
- L'affichage sera cohérent avec l'heure locale physique
- Les logs et debugs seront plus intuitifs

### Pour le Développeur

Si le développeur est à **Paris** ou pour éviter la complexité :
- ✅ **Recommandé** : Garder `Europe/Paris`
- Configuration actuelle stable et testée
- Évite les problèmes de migration de données

### Approche Hybride (avancé)

Pour afficher les deux timezones :

```twig
{# Template Twig #}
<p>
    Heure serveur (Paris) : {{ reading_time }}
    <br>
    Heure locale (Casablanca) : 
    <span class="local-time" data-timestamp="{{ reading_time|date('U') }}"></span>
</p>

<script>
// Convertir en JS côté client
document.querySelectorAll('.local-time').forEach(el => {
    const timestamp = el.dataset.timestamp * 1000;
    const casablancaTime = moment(timestamp).tz('Africa/Casablanca').format('YYYY-MM-DD HH:mm:ss');
    el.textContent = casablancaTime;
});
</script>
```

---

## 📝 Résumé

| Élément | Timezone Actuelle | Recommandation |
|---------|-------------------|----------------|
| **Serveur PHP** | Europe/Paris | Garder ou passer à Africa/Casablanca selon besoin |
| **Base de données** | Europe/Paris (timestamps stockés) | Conserver tel quel |
| **Graphiques Highcharts** | Europe/Paris | Synchroniser avec APP_TIMEZONE |
| **ESP32** | À configurer | Africa/Casablanca (heure locale physique) |
| **Affichage utilisateur** | Europe/Paris | Cohérent avec APP_TIMEZONE |

---

## 🔗 Références

- [PHP Timezones](https://www.php.net/manual/en/timezones.php)
- [Moment Timezone](https://momentjs.com/timezone/)
- [Morocco DST Rules](https://www.timeanddate.com/time/zone/morocco)

---

**Dernière mise à jour** : 2025-10-12  
**Version** : 4.4.6


