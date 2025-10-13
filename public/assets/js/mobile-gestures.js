/**
 * Mobile Gestures Handler
 * Gestion des gestures tactiles : swipe, pull-to-refresh, tap-and-hold
 */

class MobileGestures {
    constructor(options = {}) {
        this.options = {
            swipeThreshold: options.swipeThreshold || 100,
            pullThreshold: options.pullThreshold || 80,
            tapHoldDuration: options.tapHoldDuration || 500,
            enableSwipe: options.enableSwipe !== false,
            enablePullToRefresh: options.enablePullToRefresh !== false,
            onSwipeLeft: options.onSwipeLeft || null,
            onSwipeRight: options.onSwipeRight || null,
            onPullToRefresh: options.onPullToRefresh || null,
            onTapHold: options.onTapHold || null
        };

        this.touchStartX = 0;
        this.touchStartY = 0;
        this.touchEndX = 0;
        this.touchEndY = 0;
        this.pullDistance = 0;
        this.isPulling = false;
        this.tapHoldTimer = null;

        this.init();
    }

    init() {
        if (!('ontouchstart' in window)) {
            console.log('[MobileGestures] Touch events not supported');
            return;
        }

        // Créer l'indicateur de pull-to-refresh
        if (this.options.enablePullToRefresh) {
            this.createPullIndicator();
        }

        // Créer les indicateurs de swipe
        if (this.options.enableSwipe) {
            this.createSwipeIndicators();
        }

        // Attacher les événements
        document.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: false });
        document.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
        document.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: false });
    }

    createPullIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'pull-to-refresh-indicator';
        indicator.className = 'pull-to-refresh';
        indicator.innerHTML = '<i class="fas fa-sync-alt"></i>';
        document.body.appendChild(indicator);
    }

    createSwipeIndicators() {
        // Indicateur gauche
        const leftIndicator = document.createElement('div');
        leftIndicator.className = 'swipe-indicator left';
        leftIndicator.innerHTML = '<i class="fas fa-chevron-left"></i>';
        document.body.appendChild(leftIndicator);

        // Indicateur droit
        const rightIndicator = document.createElement('div');
        rightIndicator.className = 'swipe-indicator right';
        rightIndicator.innerHTML = '<i class="fas fa-chevron-right"></i>';
        document.body.appendChild(rightIndicator);
    }

    handleTouchStart(e) {
        this.touchStartX = e.touches[0].clientX;
        this.touchStartY = e.touches[0].clientY;

        // Démarrer timer pour tap-and-hold
        if (this.options.onTapHold) {
            this.tapHoldTimer = setTimeout(() => {
                this.handleTapHold(e);
            }, this.options.tapHoldDuration);
        }
    }

    handleTouchMove(e) {
        // Annuler tap-and-hold si mouvement
        if (this.tapHoldTimer) {
            clearTimeout(this.tapHoldTimer);
            this.tapHoldTimer = null;
        }

        const touchCurrentY = e.touches[0].clientY;
        const touchCurrentX = e.touches[0].clientX;

        // Pull to refresh (seulement si scroll en haut de page)
        if (this.options.enablePullToRefresh && window.scrollY === 0) {
            const pullDistance = touchCurrentY - this.touchStartY;
            
            if (pullDistance > 0) {
                e.preventDefault();
                this.pullDistance = pullDistance;
                this.isPulling = true;
                this.updatePullIndicator(pullDistance);
            }
        }

        // Afficher indicateurs de swipe
        if (this.options.enableSwipe) {
            const swipeDistance = touchCurrentX - this.touchStartX;
            this.updateSwipeIndicators(swipeDistance);
        }
    }

    handleTouchEnd(e) {
        // Annuler tap-and-hold
        if (this.tapHoldTimer) {
            clearTimeout(this.tapHoldTimer);
            this.tapHoldTimer = null;
        }

        this.touchEndX = e.changedTouches[0].clientX;
        this.touchEndY = e.changedTouches[0].clientY;

        // Gérer pull to refresh
        if (this.isPulling) {
            this.handlePullToRefresh();
            this.isPulling = false;
            this.pullDistance = 0;
            this.resetPullIndicator();
        }

        // Gérer swipe
        if (this.options.enableSwipe) {
            this.handleSwipe();
            this.resetSwipeIndicators();
        }
    }

    handleSwipe() {
        const diffX = this.touchEndX - this.touchStartX;
        const diffY = this.touchEndY - this.touchStartY;

        // Vérifier que c'est un swipe horizontal (pas vertical)
        if (Math.abs(diffX) > Math.abs(diffY)) {
            if (Math.abs(diffX) > this.options.swipeThreshold) {
                if (diffX > 0) {
                    // Swipe right
                    console.log('[MobileGestures] Swipe right detected');
                    if (this.options.onSwipeRight) {
                        this.options.onSwipeRight();
                    }
                } else {
                    // Swipe left
                    console.log('[MobileGestures] Swipe left detected');
                    if (this.options.onSwipeLeft) {
                        this.options.onSwipeLeft();
                    }
                }
            }
        }
    }

    handlePullToRefresh() {
        if (this.pullDistance > this.options.pullThreshold) {
            console.log('[MobileGestures] Pull to refresh triggered');
            if (this.options.onPullToRefresh) {
                this.options.onPullToRefresh();
            }
        }
    }

    handleTapHold(e) {
        console.log('[MobileGestures] Tap and hold detected');
        
        // Vibration feedback si supporté
        if ('vibrate' in navigator) {
            navigator.vibrate(50);
        }

        if (this.options.onTapHold) {
            this.options.onTapHold(e);
        }
    }

    updatePullIndicator(distance) {
        const indicator = document.getElementById('pull-to-refresh-indicator');
        if (!indicator) return;

        const progress = Math.min(distance / this.options.pullThreshold, 1);
        indicator.style.top = `${distance * 0.5}px`;
        indicator.style.opacity = progress;
        
        const icon = indicator.querySelector('i');
        if (icon) {
            icon.style.transform = `rotate(${progress * 360}deg)`;
        }

        if (progress >= 1) {
            indicator.classList.add('active');
        } else {
            indicator.classList.remove('active');
        }
    }

    resetPullIndicator() {
        const indicator = document.getElementById('pull-to-refresh-indicator');
        if (!indicator) return;

        indicator.style.top = '-60px';
        indicator.style.opacity = '0';
        indicator.classList.remove('active');
    }

    updateSwipeIndicators(distance) {
        const leftIndicator = document.querySelector('.swipe-indicator.left');
        const rightIndicator = document.querySelector('.swipe-indicator.right');
        
        if (!leftIndicator || !rightIndicator) return;

        const progress = Math.min(Math.abs(distance) / this.options.swipeThreshold, 1);

        if (distance > 50) {
            // Swipe right
            rightIndicator.style.opacity = progress;
            leftIndicator.style.opacity = 0;
        } else if (distance < -50) {
            // Swipe left
            leftIndicator.style.opacity = progress;
            rightIndicator.style.opacity = 0;
        } else {
            leftIndicator.style.opacity = 0;
            rightIndicator.style.opacity = 0;
        }
    }

    resetSwipeIndicators() {
        const leftIndicator = document.querySelector('.swipe-indicator.left');
        const rightIndicator = document.querySelector('.swipe-indicator.right');
        
        if (leftIndicator) leftIndicator.style.opacity = '0';
        if (rightIndicator) rightIndicator.style.opacity = '0';
    }
}

/**
 * Initialise les gestures mobiles avec navigation entre pages
 */
function initMobileNavigation() {
    const pages = [
        '/ffp3/dashboard',
        '/ffp3/aquaponie',
        '/ffp3/control',
        '/ffp3/tide-stats'
    ];

    const currentPath = window.location.pathname;
    const currentIndex = pages.findIndex(page => currentPath.includes(page.split('/').pop()));

    if (currentIndex === -1) return null;

    return new MobileGestures({
        onSwipeLeft: () => {
            // Page suivante
            const nextIndex = (currentIndex + 1) % pages.length;
            if (typeof toastManager !== 'undefined') {
                toastManager.showInfo('Navigation vers la page suivante...', 2000);
            }
            setTimeout(() => {
                window.location.href = pages[nextIndex];
            }, 300);
        },
        onSwipeRight: () => {
            // Page précédente
            const prevIndex = (currentIndex - 1 + pages.length) % pages.length;
            if (typeof toastManager !== 'undefined') {
                toastManager.showInfo('Navigation vers la page précédente...', 2000);
            }
            setTimeout(() => {
                window.location.href = pages[prevIndex];
            }, 300);
        },
        onPullToRefresh: () => {
            if (typeof toastManager !== 'undefined') {
                toastManager.showInfo('Actualisation des données...', 2000);
            }
            setTimeout(() => {
                window.location.reload();
            }, 500);
        },
        onTapHold: (e) => {
            // Menu contextuel (à implémenter)
            console.log('Tap and hold at:', e.touches[0].clientX, e.touches[0].clientY);
        }
    });
}

// Auto-initialisation sur mobile
if ('ontouchstart' in window && window.innerWidth <= 768) {
    document.addEventListener('DOMContentLoaded', () => {
        initMobileNavigation();
    });
}

