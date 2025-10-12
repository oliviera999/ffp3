/**
 * StatsUpdater - Mise à jour dynamique des cartes de statistiques en temps réel
 * 
 * Met à jour les valeurs affichées, les barres de progression, et les statistiques
 * sans recharger la page.
 * 
 * @version 1.0.0
 * @date 2025-10-12
 */

class StatsUpdater {
    constructor(options = {}) {
        // Configuration
        this.sensors = options.sensors || [
            'EauAquarium', 'EauReserve', 'EauPotager',
            'TempEau', 'TempAir', 'Humidite', 'Luminosite'
        ];
        
        // Cache des statistiques actuelles (pour calcul incrémental)
        this.stats = new Map();
        
        // Mapping des capteurs vers leurs éléments DOM
        this.sensorElements = new Map();
        
        // État
        this.isInitialized = false;
        this.readingCount = 0;
        
        this.log('StatsUpdater initialized');
    }
    
    /**
     * Initialise les références aux éléments DOM
     */
    init() {
        // Scanner les éléments de cartes de statistiques
        this.sensors.forEach(sensor => {
            const sensorKey = sensor.toLowerCase();
            
            // Éléments spécifiques aux niveaux d'eau
            if (sensor.startsWith('Eau')) {
                const displayEl = document.getElementById(`${sensorKey}-display`);
                const progressEl = document.getElementById(`${sensorKey}-progress`);
                
                if (displayEl || progressEl) {
                    this.sensorElements.set(sensor, {
                        display: displayEl,
                        progress: progressEl,
                        type: 'water'
                    });
                }
            }
            // Éléments pour température, humidité, luminosité
            else {
                const displayEl = document.getElementById(`${sensorKey}-display`);
                const progressEl = document.getElementById(`${sensorKey}-progress`);
                
                if (displayEl || progressEl) {
                    this.sensorElements.set(sensor, {
                        display: displayEl,
                        progress: progressEl,
                        type: this.getSensorType(sensor)
                    });
                }
            }
            
            // Initialiser les stats à zéro
            this.stats.set(sensor, {
                last: 0,
                min: Infinity,
                max: -Infinity,
                sum: 0,
                count: 0,
                avg: 0
            });
        });
        
        this.isInitialized = this.sensorElements.size > 0;
        this.log(`Initialized with ${this.sensorElements.size} sensor element(s)`);
        
        return this.isInitialized;
    }
    
    /**
     * Détermine le type de capteur
     */
    getSensorType(sensor) {
        if (sensor.startsWith('Temp')) return 'temperature';
        if (sensor === 'Humidite') return 'humidity';
        if (sensor === 'Luminosite') return 'light';
        if (sensor.startsWith('Eau')) return 'water';
        return 'generic';
    }
    
    /**
     * Met à jour toutes les statistiques avec de nouvelles valeurs
     * 
     * @param {Object} sensors - Objet contenant les valeurs des capteurs
     */
    updateAllStats(sensors) {
        if (!this.isInitialized) {
            this.log('Not initialized, skipping update', 'warn');
            return;
        }
        
        for (const [sensorName, value] of Object.entries(sensors)) {
            if (value !== null && value !== undefined) {
                this.updateStat(sensorName, parseFloat(value));
            }
        }
        
        this.readingCount++;
    }
    
    /**
     * Met à jour une statistique individuelle
     * 
     * @param {string} sensorName - Nom du capteur
     * @param {number} value - Nouvelle valeur
     */
    updateStat(sensorName, value) {
        // Mettre à jour les stats en cache
        const stat = this.stats.get(sensorName);
        if (stat) {
            stat.last = value;
            stat.min = Math.min(stat.min, value);
            stat.max = Math.max(stat.max, value);
            stat.sum += value;
            stat.count++;
            stat.avg = stat.sum / stat.count;
        }
        
        // Mettre à jour l'affichage
        this.updateStatCard(sensorName, value);
    }
    
    /**
     * Met à jour une carte de statistique dans l'UI
     * 
     * @param {string} sensorName - Nom du capteur
     * @param {number} value - Valeur à afficher
     */
    updateStatCard(sensorName, value) {
        const elements = this.sensorElements.get(sensorName);
        if (!elements) {
            return;
        }
        
        // Déterminer l'unité et les paramètres
        const config = this.getSensorConfig(sensorName);
        
        // Mettre à jour l'affichage de la valeur
        if (elements.display) {
            const formattedValue = value.toFixed(config.decimals);
            elements.display.innerHTML = `${formattedValue} <span class="stat-card-unit">${config.unit}</span>`;
            
            // Animation flash pour indiquer le changement
            elements.display.classList.add('value-updated');
            setTimeout(() => {
                elements.display.classList.remove('value-updated');
            }, 500);
        }
        
        // Mettre à jour la barre de progression
        if (elements.progress && config.max) {
            const percentage = Math.min(100, Math.max(0, (value / config.max) * 100));
            elements.progress.style.width = `${percentage.toFixed(0)}%`;
            
            // Animation de la barre
            elements.progress.classList.add('progress-updated');
            setTimeout(() => {
                elements.progress.classList.remove('progress-updated');
            }, 500);
        }
    }
    
    /**
     * Obtient la configuration d'affichage pour un capteur
     * 
     * @param {string} sensorName - Nom du capteur
     * @returns {Object} Configuration
     */
    getSensorConfig(sensorName) {
        const configs = {
            'EauAquarium': { unit: 'cm', decimals: 0, max: 100 },
            'EauReserve': { unit: 'cm', decimals: 0, max: 100 },
            'EauPotager': { unit: 'cm', decimals: 0, max: 100 },
            'TempEau': { unit: '°C', decimals: 1, max: 40 },
            'TempAir': { unit: '°C', decimals: 1, max: 40 },
            'Humidite': { unit: '%', decimals: 1, max: 100 },
            'Luminosite': { unit: 'UA', decimals: 0, max: 4000 }
        };
        
        return configs[sensorName] || { unit: '', decimals: 1, max: null };
    }
    
    /**
     * Met à jour les dates de synthèse
     * 
     * @param {Date|string} startDate - Date de début
     * @param {Date|string} endDate - Date de fin
     */
    updateSummaryDates(startDate, endDate) {
        // Cette fonction pourrait être étendue pour mettre à jour
        // dynamiquement les dates affichées dans la synthèse
        // Pour l'instant, on log juste l'information
        this.log(`Summary dates updated: ${startDate} to ${endDate}`);
    }
    
    /**
     * Incrémente le compteur d'enregistrements
     */
    incrementReadingCount() {
        this.readingCount++;
        
        // Mettre à jour l'affichage si l'élément existe
        const countElement = document.querySelector('.period-stat-value');
        if (countElement) {
            countElement.textContent = this.readingCount;
        }
    }
    
    /**
     * Met à jour toutes les barres de progression
     */
    updateProgressBars() {
        this.stats.forEach((stat, sensorName) => {
            if (stat.last > 0) {
                this.updateStatCard(sensorName, stat.last);
            }
        });
    }
    
    /**
     * Réinitialise toutes les statistiques
     */
    resetStats() {
        this.stats.forEach((stat) => {
            stat.min = Infinity;
            stat.max = -Infinity;
            stat.sum = 0;
            stat.count = 0;
            stat.avg = 0;
        });
        this.readingCount = 0;
        this.log('Stats reset');
    }
    
    /**
     * Obtient les statistiques actuelles
     * 
     * @returns {Object} Statistiques
     */
    getStats() {
        const result = {
            readingCount: this.readingCount,
            sensors: {}
        };
        
        this.stats.forEach((stat, sensorName) => {
            if (stat.count > 0) {
                result.sensors[sensorName] = {
                    last: stat.last,
                    min: stat.min === Infinity ? null : stat.min,
                    max: stat.max === -Infinity ? null : stat.max,
                    avg: stat.avg,
                    count: stat.count
                };
            }
        });
        
        return result;
    }
    
    /**
     * Affiche un résumé des statistiques dans la console
     */
    logStats() {
        const stats = this.getStats();
        console.table(stats.sensors);
        this.log(`Total readings: ${stats.readingCount}`);
    }
    
    /**
     * Log avec préfixe
     */
    log(message, level = 'info') {
        const prefix = '[StatsUpdater]';
        if (level === 'error') {
            console.error(`${prefix} ${message}`);
        } else if (level === 'warn') {
            console.warn(`${prefix} ${message}`);
        } else {
            console.log(`${prefix} ${message}`);
        }
    }
}

// Export global pour utilisation dans les templates
window.StatsUpdater = StatsUpdater;

