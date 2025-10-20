/**
 * ControlValuesUpdater - Mise à jour dynamique des valeurs affichées sur la page de contrôle
 * 
 * Met à jour les informations affichées sans recharger la page :
 * - État des connexions boards
 * - Valeurs des paramètres
 * 
 * @version 1.0.0
 * @date 2025-10-12
 */

class ControlValuesUpdater {
    constructor(options = {}) {
        // Configuration
        this.enabled = options.enabled !== false;
        
        // Références aux éléments DOM
        this.boardElements = new Map();
        this.parameterElements = new Map();
        
        // État
        this.isInitialized = false;
        this.lastBoardsUpdate = null;
        
        this.log('ControlValuesUpdater initialized');
    }
    
    /**
     * Initialise les références aux éléments DOM
     */
    init() {
        // Scanner les éléments d'état des boards
        this.initBoardElements();
        
        // Scanner les éléments de paramètres
        this.initParameterElements();
        
        this.isInitialized = true;
        this.log(`Initialized with ${this.boardElements.size} board(s) and ${this.parameterElements.size} parameter(s)`);
        
        return this.isInitialized;
    }
    
    /**
     * Initialise les éléments d'état des boards
     */
    initBoardElements() {
        // Rechercher tous les paragraphes contenant "Board" et "Dernière requête"
        const boardCards = document.querySelectorAll('.board-card[data-board]');
        
        boardCards.forEach(card => {
            const boardId = card.dataset.board;
            this.boardElements.set(boardId, card);
            this.log(`Found board element for Board ${boardId}`);
        });
    }
    
    /**
     * Initialise les éléments de paramètres
     * Pour les inputs de formulaire qui affichent les valeurs actuelles
     */
    initParameterElements() {
        // Mapping des GPIOs vers les IDs d'inputs
        const inputs = document.querySelectorAll('[data-parameter-gpio]');
        inputs.forEach(inputElement => {
            const gpio = parseInt(inputElement.dataset.parameterGpio, 10);
            if (!isNaN(gpio)) {
                this.parameterElements.set(gpio, inputElement);
                this.log(`Found parameter element for GPIO ${gpio} (${inputElement.id || 'input'})`);
            }
        });
    }
    
    /**
     * Met à jour l'état des connexions boards
     * 
     * @param {Array} boards - Liste des boards [{board: '1', last_request: '...'}]
     */
    updateBoardStatus(boards) {
        if (!this.isInitialized || !boards) return;
        
        boards.forEach(board => {
            const boardCard = this.boardElements.get(board.board);
            
            if (boardCard) {
                const lastRequestEl = boardCard.querySelector('.last-request-time');
                if (lastRequestEl) {
                    lastRequestEl.textContent = board.last_request;
                }

                // Animation flash pour indiquer le changement
                boardCard.classList.add('value-updated');
                setTimeout(() => {
                    boardCard.classList.remove('value-updated');
                }, 500);
                
                this.log(`Updated Board ${board.board} status: ${board.last_request}`);
            }
        });
        
        this.lastBoardsUpdate = new Date();
    }
    
    /**
     * Met à jour l'affichage d'un paramètre
     * 
     * @param {number} gpio - Numéro GPIO
     * @param {*} value - Nouvelle valeur
     */
    updateParameterDisplay(gpio, value) {
        if (!this.isInitialized) return;
        
        const inputElement = this.parameterElements.get(gpio);
        
        if (inputElement) {
            // Mettre à jour la valeur de l'input
            if (inputElement.type === 'checkbox') {
                inputElement.checked = value == 1;
            } else {
                inputElement.value = value;
            }
            
            // Animation flash
            inputElement.classList.add('value-updated');
            setTimeout(() => {
                inputElement.classList.remove('value-updated');
            }, 500);
            
            this.log(`Updated parameter GPIO ${gpio} to ${value}`);
        }
    }
    
    /**
     * Met à jour plusieurs paramètres à la fois
     * 
     * @param {Object} parameters - Objet {gpio: value, ...}
     */
    updateParameters(parameters) {
        if (!parameters) return;
        
        for (const [gpio, value] of Object.entries(parameters)) {
            this.updateParameterDisplay(parseInt(gpio), value);
        }
    }
    
    /**
     * Obtient des statistiques sur l'état actuel
     * 
     * @returns {Object} Statistiques
     */
    getStats() {
        return {
            initialized: this.isInitialized,
            boardsCount: this.boardElements.size,
            parametersCount: this.parameterElements.size,
            lastUpdate: this.lastBoardsUpdate,
            enabled: this.enabled
        };
    }
    
    /**
     * Active/désactive les mises à jour
     * 
     * @param {boolean} enabled - Activer ou non
     */
    setEnabled(enabled) {
        this.enabled = enabled;
        this.log(`Updates ${enabled ? 'enabled' : 'disabled'}`);
    }
    
    /**
     * Réinitialise les éléments
     */
    reset() {
        this.boardElements.clear();
        this.parameterElements.clear();
        this.isInitialized = false;
        this.log('Reset complete');
    }
    
    /**
     * Log avec préfixe
     */
    log(message, level = 'info') {
        const prefix = '[ControlValuesUpdater]';
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
window.ControlValuesUpdater = ControlValuesUpdater;

