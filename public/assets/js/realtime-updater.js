/**
 * Realtime Data Updater
 * Système de polling intelligent pour mise à jour automatique des données
 */
class RealtimeUpdater {
    constructor(options = {}) {
        this.pollInterval = options.pollInterval || 15000; // 15 secondes par défaut
        this.apiBasePath = options.apiBasePath || '/ffp3/api/realtime';
        this.enabled = options.enabled !== false;
        this.lastTimestamp = Math.floor(Date.now() / 1000);
        this.pollTimer = null;
        this.retryCount = 0;
        this.maxRetries = 5;
        this.isPaused = false;
        this.callbacks = {
            onNewData: options.onNewData || null,
            onHealthUpdate: options.onHealthUpdate || null,
            onError: options.onError || null
        };

        // Gérer la visibilité de la page (pause si onglet inactif)
        if (typeof document.hidden !== 'undefined') {
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.pause();
                } else {
                    this.resume();
                }
            });
        }
    }

    /**
     * Démarre le polling
     */
    start() {
        if (!this.enabled) return;
        
        console.log('[RealtimeUpdater] Starting polling...');
        this.updateBadge('connecting');
        
        // Première requête immédiate
        this.poll();
        
        // Puis polling régulier
        this.pollTimer = setInterval(() => this.poll(), this.pollInterval);
    }

    /**
     * Arrête le polling
     */
    stop() {
        console.log('[RealtimeUpdater] Stopping polling...');
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
            this.pollTimer = null;
        }
        this.updateBadge('offline');
    }

    /**
     * Met en pause (ne pas confondre avec stop)
     */
    pause() {
        if (!this.isPaused) {
            console.log('[RealtimeUpdater] Pausing (tab hidden)...');
            this.isPaused = true;
            this.updateBadge('paused');
        }
    }

    /**
     * Reprend après pause
     */
    resume() {
        if (this.isPaused) {
            console.log('[RealtimeUpdater] Resuming (tab visible)...');
            this.isPaused = false;
            this.poll(); // Rafraîchir immédiatement
            this.updateBadge('online');
        }
    }

    /**
     * Effectue une requête de polling
     */
    async poll() {
        if (this.isPaused) return;

        try {
            // Récupérer les dernières données
            const latestResponse = await fetch(`${this.apiBasePath}/sensors/latest`);
            if (!latestResponse.ok) throw new Error(`HTTP ${latestResponse.status}`);
            
            const latestData = await latestResponse.json();
            
            // Récupérer le statut système
            const healthResponse = await fetch(`${this.apiBasePath}/system/health`);
            if (!healthResponse.ok) throw new Error(`HTTP ${healthResponse.status}`);
            
            const healthData = await healthResponse.json();

            // Succès : reset retry counter
            this.retryCount = 0;
            this.updateBadge('online');

            // Vérifier s'il y a de nouvelles données
            if (latestData.timestamp && latestData.timestamp > this.lastTimestamp) {
                console.log('[RealtimeUpdater] New data received!', latestData);
                this.lastTimestamp = latestData.timestamp;
                
                // Callback
                if (this.callbacks.onNewData) {
                    this.callbacks.onNewData(latestData);
                }
                
                // Notification toast
                if (typeof toastManager !== 'undefined') {
                    toastManager.showInfo('Nouvelles données reçues', 3000);
                }
            }

            // Mettre à jour le statut système
            if (this.callbacks.onHealthUpdate) {
                this.callbacks.onHealthUpdate(healthData);
            }

            this.updateSystemStatus(healthData);

        } catch (error) {
            console.error('[RealtimeUpdater] Error during poll:', error);
            this.retryCount++;
            
            if (this.retryCount >= this.maxRetries) {
                this.updateBadge('error');
                if (typeof toastManager !== 'undefined') {
                    toastManager.showError('Erreur de connexion. Vérifiez votre réseau.');
                }
            } else {
                this.updateBadge('warning');
            }

            if (this.callbacks.onError) {
                this.callbacks.onError(error);
            }
        }
    }

    /**
     * Met à jour le badge de statut
     */
    updateBadge(status) {
        const badge = document.getElementById('live-badge');
        if (!badge) return;

        const statusConfig = {
            connecting: { text: 'CONNEXION...', class: 'badge-warning', icon: 'fa-spinner fa-spin' },
            online: { text: 'LIVE', class: 'badge-success', icon: 'fa-circle' },
            offline: { text: 'HORS LIGNE', class: 'badge-danger', icon: 'fa-circle' },
            error: { text: 'ERREUR', class: 'badge-danger', icon: 'fa-exclamation-triangle' },
            warning: { text: 'INSTABLE', class: 'badge-warning', icon: 'fa-exclamation-circle' },
            paused: { text: 'PAUSE', class: 'badge-secondary', icon: 'fa-pause' }
        };

        const config = statusConfig[status] || statusConfig.offline;
        
        badge.className = `badge ${config.class}`;
        badge.innerHTML = `<i class="fas ${config.icon}"></i> ${config.text}`;
    }

    /**
     * Met à jour les informations système dans l'UI
     */
    updateSystemStatus(health) {
        // Dernière réception
        const lastReadingEl = document.getElementById('last-reading-time');
        if (lastReadingEl && health.last_reading_ago_seconds !== null) {
            lastReadingEl.textContent = this.formatTimeSince(health.last_reading_ago_seconds);
        }

        // Uptime
        const uptimeEl = document.getElementById('system-uptime');
        if (uptimeEl && health.uptime_percentage !== undefined) {
            uptimeEl.textContent = `${health.uptime_percentage.toFixed(1)}%`;
        }

        // Lectures aujourd'hui
        const readingsTodayEl = document.getElementById('readings-today');
        if (readingsTodayEl && health.readings_today !== undefined) {
            readingsTodayEl.textContent = health.readings_today;
        }

        // Statut online/offline
        const statusIndicator = document.getElementById('system-status-indicator');
        if (statusIndicator) {
            if (health.online) {
                statusIndicator.className = 'status-indicator status-online';
                statusIndicator.innerHTML = '<i class="fas fa-check-circle"></i> En ligne';
            } else {
                statusIndicator.className = 'status-indicator status-offline';
                statusIndicator.innerHTML = '<i class="fas fa-times-circle"></i> Hors ligne';
            }
        }
    }

    /**
     * Formate un temps en secondes en chaîne lisible
     */
    formatTimeSince(seconds) {
        if (seconds < 60) return `${seconds}s`;
        if (seconds < 3600) return `${Math.floor(seconds / 60)}min`;
        if (seconds < 86400) return `${Math.floor(seconds / 3600)}h`;
        return `${Math.floor(seconds / 86400)}j`;
    }

    /**
     * Change l'intervalle de polling (en millisecondes)
     */
    setInterval(newInterval) {
        this.pollInterval = newInterval;
        if (this.pollTimer) {
            this.stop();
            this.start();
        }
    }
}

// Variable globale pour accès facile
let realtimeUpdater = null;

/**
 * Initialise le système de mise à jour temps réel
 */
function initRealtimeUpdater(options = {}) {
    if (realtimeUpdater) {
        realtimeUpdater.stop();
    }
    
    realtimeUpdater = new RealtimeUpdater(options);
    realtimeUpdater.start();
    
    return realtimeUpdater;
}

