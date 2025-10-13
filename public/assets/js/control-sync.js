/**
 * ControlSync - Synchronisation temps réel de l'interface de contrôle
 * 
 * Gère le polling automatique des états GPIO/outputs et met à jour
 * l'interface utilisateur en temps réel.
 * 
 * @version 1.0.0
 * @date 2025-10-11
 */

class ControlSync {
    constructor(options = {}) {
        // Configuration
        this.apiBase = options.apiBase || '/ffp3/api/outputs';
        this.pollInterval = (options.pollInterval || 10) * 1000; // 10 secondes par défaut
        this.maxRetries = options.maxRetries || 5;
        
        // État interne
        this.isRunning = false;
        this.isPaused = false;
        this.retryCount = 0;
        this.pollTimer = null;
        this.lastStates = {}; // Cache des derniers états connus
        
        // Callbacks
        this.onStateChange = options.onStateChange || null;
        this.onStatusChange = options.onStatusChange || null;
        
        // Éléments DOM
        this.switches = new Map();
        this.liveBadge = null;
        
        // Bind methods
        this.poll = this.poll.bind(this);
        this.handleVisibilityChange = this.handleVisibilityChange.bind(this);
        
        this.log('ControlSync initialized');
    }
    
    /**
     * Démarre le polling automatique
     */
    start() {
        if (this.isRunning) {
            this.log('Already running');
            return;
        }
        
        this.isRunning = true;
        this.isPaused = false;
        this.log('Starting control sync...');
        
        // Première mise à jour immédiate
        this.poll();
        
        // Surveiller la visibilité de la page
        document.addEventListener('visibilitychange', this.handleVisibilityChange);
        
        this.updateBadgeStatus('connecting');
    }
    
    /**
     * Arrête le polling
     */
    stop() {
        this.isRunning = false;
        if (this.pollTimer) {
            clearTimeout(this.pollTimer);
            this.pollTimer = null;
        }
        
        document.removeEventListener('visibilitychange', this.handleVisibilityChange);
        this.log('Control sync stopped');
        this.updateBadgeStatus('offline');
    }
    
    /**
     * Met en pause / reprend le polling (changement de visibilité)
     */
    handleVisibilityChange() {
        if (document.hidden) {
            this.isPaused = true;
            this.log('Page hidden - pausing sync');
            if (this.pollTimer) {
                clearTimeout(this.pollTimer);
                this.pollTimer = null;
            }
            this.updateBadgeStatus('paused');
        } else {
            this.isPaused = false;
            this.log('Page visible - resuming sync');
            this.updateBadgeStatus('connecting');
            this.poll(); // Relancer immédiatement
        }
    }
    
    /**
     * Effectue une requête de polling
     */
    async poll() {
        if (!this.isRunning || this.isPaused) {
            return;
        }
        
        try {
            const response = await fetch(`${this.apiBase}/state`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const states = await response.json();
            
            // Traiter les changements d'état
            this.processStates(states);
            
            // Succès - reset retry count
            this.retryCount = 0;
            this.updateBadgeStatus('online');
            
            // Planifier le prochain poll
            this.schedulePoll();
            
        } catch (error) {
            this.handleError(error);
        }
    }
    
    /**
     * Traite les états reçus et détecte les changements
     */
    processStates(states) {
        const changes = [];
        
        for (const [gpio, state] of Object.entries(states)) {
            const gpioNum = parseInt(gpio);
            
            // Ignorer les clés non numériques (ex: "mail", "heat", "light")
            // Ces clés sont des alias ajoutés pour la compatibilité ESP32
            if (isNaN(gpioNum)) {
                continue;
            }
            
            // Pour les GPIOs < 100 et certains GPIOs spéciaux, c'est un entier (état on/off)
            // Pour les GPIOs >= 100, c'est souvent une valeur (texte, nombre, email, etc.)
            // Ne pas convertir systématiquement en parseInt pour éviter NaN sur les chaînes
            let newState;
            if (gpioNum < 100 || gpioNum === 101 || gpioNum === 108 || gpioNum === 109 || gpioNum === 110 || gpioNum === 115) {
                // États binaires (switches): convertir en entier
                newState = parseInt(state);
            } else if (gpioNum === 100) {
                // GPIO 100 = email (chaîne de caractères)
                newState = String(state || '');
            } else {
                // Autres paramètres (nombres ou texte): garder la valeur telle quelle
                // Tenter de convertir en nombre si possible, sinon garder comme chaîne
                const asNumber = parseFloat(state);
                newState = !isNaN(asNumber) ? asNumber : state;
            }
            
            // Vérifier si l'état a changé
            if (this.lastStates[gpioNum] !== undefined && this.lastStates[gpioNum] !== newState) {
                changes.push({
                    gpio: gpioNum,
                    oldState: this.lastStates[gpioNum],
                    newState: newState
                });
                
                this.log(`GPIO ${gpioNum} changed: ${this.lastStates[gpioNum]} → ${newState}`);
            }
            
            // Mettre à jour le cache
            this.lastStates[gpioNum] = newState;
        }
        
        // Si changements détectés, mettre à jour l'interface
        if (changes.length > 0) {
            this.updateSwitches(changes);
            
            // Notifier via callback
            if (this.onStateChange) {
                this.onStateChange(changes);
            }
            
            // Toast notification globale
            if (window.toastManager) {
                const gpioList = changes.map(c => `GPIO ${c.gpio}`).join(', ');
                window.toastManager.showInfo(`Changement détecté: ${gpioList}`, 5000);
            }
        }
    }
    
    /**
     * Met à jour les switches dans l'interface
     */
    updateSwitches(changes) {
        changes.forEach(change => {
            // Trouver le switch correspondant
            const switchElement = document.querySelector(`input[data-gpio="${change.gpio}"]`);
            
            if (switchElement) {
                // Mettre à jour l'état du switch sans déclencher l'événement onchange
                const currentChecked = switchElement.checked;
                const shouldBeChecked = change.newState === 1;
                
                if (currentChecked !== shouldBeChecked) {
                    // Animation flash pour indiquer le changement
                    const container = switchElement.closest('div');
                    if (container) {
                        container.classList.add('state-changed');
                        setTimeout(() => container.classList.remove('state-changed'), 1000);
                    }
                    
                    // Mettre à jour le switch
                    switchElement.checked = shouldBeChecked;
                    
                    this.log(`Updated switch GPIO ${change.gpio} to ${shouldBeChecked}`);
                }
            }
        });
    }
    
    /**
     * Planifie le prochain poll
     */
    schedulePoll() {
        if (this.pollTimer) {
            clearTimeout(this.pollTimer);
        }
        
        if (this.isRunning && !this.isPaused) {
            this.pollTimer = setTimeout(this.poll, this.pollInterval);
        }
    }
    
    /**
     * Gère les erreurs de polling
     */
    handleError(error) {
        this.log(`Polling error: ${error.message}`, 'error');
        
        this.retryCount++;
        
        if (this.retryCount >= this.maxRetries) {
            this.log('Max retries reached - stopping sync', 'error');
            this.updateBadgeStatus('error');
            this.stop();
            
            if (window.toastManager) {
                window.toastManager.showError('Synchronisation interrompue après plusieurs échecs', 10000);
            }
        } else {
            // Retry avec backoff exponentiel
            const retryDelay = Math.min(1000 * Math.pow(2, this.retryCount), 30000);
            this.log(`Retry ${this.retryCount}/${this.maxRetries} in ${retryDelay}ms`);
            
            this.updateBadgeStatus('warning');
            
            if (this.pollTimer) {
                clearTimeout(this.pollTimer);
            }
            
            this.pollTimer = setTimeout(this.poll, retryDelay);
        }
    }
    
    /**
     * Met à jour le badge LIVE
     */
    updateBadgeStatus(status) {
        if (!this.liveBadge) {
            this.liveBadge = document.getElementById('control-sync-badge');
        }
        
        if (!this.liveBadge) {
            return;
        }
        
        // Retirer toutes les classes de statut
        this.liveBadge.classList.remove('connecting', 'online', 'offline', 'error', 'warning', 'paused');
        
        // Ajouter la nouvelle classe
        this.liveBadge.classList.add(status);
        
        // Mettre à jour le texte
        const texts = {
            'connecting': 'CONNEXION...',
            'online': 'SYNC',
            'offline': 'HORS LIGNE',
            'error': 'ERREUR',
            'warning': 'RECONNEXION...',
            'paused': 'PAUSE'
        };
        
        this.liveBadge.textContent = texts[status] || status.toUpperCase();
        
        // Notifier via callback
        if (this.onStatusChange) {
            this.onStatusChange(status);
        }
    }
    
    /**
     * Initialise les états depuis l'interface actuelle
     */
    initializeFromDOM() {
        const switches = document.querySelectorAll('input[data-gpio]');
        switches.forEach(switchEl => {
            const gpio = parseInt(switchEl.dataset.gpio);
            const state = switchEl.checked ? 1 : 0;
            this.lastStates[gpio] = state;
            this.switches.set(gpio, switchEl);
        });
        
        this.log(`Initialized ${switches.length} switches from DOM`);
    }
    
    /**
     * Force une synchronisation immédiate
     */
    forceSync() {
        this.log('Force sync requested');
        if (this.pollTimer) {
            clearTimeout(this.pollTimer);
        }
        this.poll();
    }
    
    /**
     * Log avec préfixe
     */
    log(message, level = 'info') {
        const prefix = '[ControlSync]';
        if (level === 'error') {
            console.error(`${prefix} ${message}`);
        } else if (level === 'warn') {
            console.warn(`${prefix} ${message}`);
        } else {
            console.log(`${prefix} ${message}`);
        }
    }
}

// Export global pour utilisation dans le template
window.ControlSync = ControlSync;

