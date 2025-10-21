class RealtimeLogger {
    constructor(options = {}) {
        this.container = document.getElementById(options.containerId || 'logs-container');
        this.counter = document.getElementById(options.counterId || 'logs-counter');
        this.filtersSelector = options.filtersSelector || '.log-filter-badge';
        this.maxEntries = options.maxEntries || 200;
        this.entries = [];
        this.activeFilters = new Set(['info', 'success', 'warning', 'error', 'gpio', 'sync']);

        this.initFilters();
        this.logStatus('READY');
    }

    initFilters() {
        const filterButtons = document.querySelectorAll(this.filtersSelector);
        filterButtons.forEach(button => {
            const filter = button.dataset.filter;
            if (!filter) {
                return;
            }

            button.addEventListener('click', () => {
                if (button.classList.contains('active')) {
                    button.classList.remove('active');
                    this.activeFilters.delete(filter);
                } else {
                    button.classList.add('active');
                    this.activeFilters.add(filter);
                }
                this.render();
            });
        });
    }

    logStatus(message) {
        this.addEntry({
            type: 'status',
            label: 'STATUS',
            message,
            icon: 'fa-info-circle',
        });
    }

    logChanges(changes = []) {
        if (!Array.isArray(changes) || changes.length === 0) {
            return;
        }

        changes.forEach(change => {
            const label = `GPIO ${change.gpio}`;
            const detail = `${change.oldState} → ${change.newState}`;
            this.addEntry({
                type: 'gpio',
                label,
                message: detail,
                icon: 'fa-microchip',
            });
        });
    }

    logSync(source, label = 'SYNC') {
        this.addEntry({
            type: 'sync',
            label,
            message: `Synchronisation ${source}`,
            icon: 'fa-sync-alt',
        });
    }

    logInfo(message) {
        this.addEntry({
            type: 'info',
            label: 'INFO',
            message,
            icon: 'fa-info-circle',
        });
    }

    logWarning(message) {
        this.addEntry({
            type: 'warning',
            label: 'WARN',
            message,
            icon: 'fa-triangle-exclamation',
        });
    }

    logError(message) {
        this.addEntry({
            type: 'error',
            label: 'ERREUR',
            message,
            icon: 'fa-circle-exclamation',
        });
    }

    addEntry(entry) {
        if (!this.container) {
            return;
        }

        const timestamp = new Date();
        const formattedTime = timestamp.toLocaleTimeString('fr-FR', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });

        this.entries.unshift({
            ...entry,
            timestamp,
            formattedTime,
        });

        if (this.entries.length > this.maxEntries) {
            this.entries.length = this.maxEntries;
        }

        this.render();
    }

    render() {
        if (!this.container) {
            return;
        }

        this.container.innerHTML = '';

        const visibleEntries = this.entries.filter(entry => this.isVisible(entry.type));

        visibleEntries.forEach(entry => {
            const row = document.createElement('div');
            row.className = `log-entry log-${entry.type}`;

            row.innerHTML = `
                <span class="log-time">${entry.formattedTime}</span>
                <span class="log-icon"><i class="fas ${entry.icon}"></i></span>
                <span class="log-label">${entry.label}</span>
                <span class="log-message">${entry.message}</span>
            `;

            this.container.appendChild(row);
        });

        if (this.counter) {
            this.counter.textContent = visibleEntries.length.toString();
        }
    }

    isVisible(type) {
        if (type === 'status') {
            return true;
        }
        return this.activeFilters.has(type);
    }
}

window.RealtimeLogger = RealtimeLogger;

