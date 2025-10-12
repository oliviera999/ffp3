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
        const boardParagraphs = document.querySelectorAll('p');
        
        boardParagraphs.forEach(p => {
            const text = p.textContent;
            const match = text.match(/Board\s+(\d+):\s*Dernière requête le\s+(.+)/);
            
            if (match) {
                const boardId = match[1];
                this.boardElements.set(boardId, p);
                this.log(`Found board element for Board ${boardId}`);
            }
        });
    }
    
    /**
     * Initialise les éléments de paramètres
     * Pour les inputs de formulaire qui affichent les valeurs actuelles
     */
    initParameterElements() {
        // Mapping des GPIOs vers les IDs d'inputs
        const gpioInputMap = {
            100: 'mail',
            102: 'aqThr',
            103: 'taThr',
            104: 'chauff',
            105: 'bouffeMat',
            106: 'bouffeMid',
            107: 'bouffeSoir',
            111: 'tempsGros',
            112: 'tempsPetits',
            113: 'tempsRemplissageSec',
            114: 'limFlood',
            116: 'FreqWakeUp'
        };
        
        for (const [gpio, inputId] of Object.entries(gpioInputMap)) {
            const inputElement = document.getElementById(inputId);
            if (inputElement) {
                this.parameterElements.set(parseInt(gpio), inputElement);
                this.log(`Found parameter element for GPIO ${gpio} (${inputId})`);
            }
        }
    }
    
    /**
     * Met à jour l'état des connexions boards
     * 
     * @param {Array} boards - Liste des boards [{board: '1', last_request: '...'}]
     */
    updateBoardStatus(boards) {
        if (!this.isInitialized || !boards) return;
        
        boards.forEach(board => {
            const boardElement = this.boardElements.get(board.board);
            
            if (boardElement) {
                // Mettre à jour le texte
                boardElement.innerHTML = `<strong>Board ${board.board}:</strong> Dernière requête le ${board.last_request}`;
                
                // Animation flash pour indiquer le changement
                boardElement.classList.add('value-updated');
                setTimeout(() => {
                    boardElement.classList.remove('value-updated');
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

