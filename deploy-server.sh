#!/bin/bash
#
# Script de déploiement serveur FFP3
# À exécuter sur le serveur de production
# Usage: bash deploy-server.sh
#

echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║      DÉPLOIEMENT SERVEUR FFP3 - PRODUCTION                  ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Répertoire du projet
PROJECT_ROOT="/home4/oliviera/iot.olution.info/ffp3"
cd "$PROJECT_ROOT"

echo "📁 Répertoire projet: $PROJECT_ROOT"
echo ""

echo "🔍 [1/8] Vérification de l'état Git..."
echo ""

# Vérifier l'état Git
git_status=$(git status --porcelain)
if [ -n "$git_status" ]; then
    echo -e "${YELLOW}⚠ Modifications locales détectées${NC}"
    echo "Modifications:"
    echo "$git_status"
    echo ""
    echo "Voulez-vous continuer ? (y/N)"
    read -r response
    if [ "$response" != "y" ] && [ "$response" != "Y" ]; then
        echo "Déploiement annulé."
        exit 1
    fi
else
    echo -e "${GREEN}✓ Working tree propre${NC}"
fi

echo ""
echo "🔍 [2/8] Récupération des dernières modifications..."
echo ""

# Récupérer les dernières modifications
git fetch origin
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Fetch réussi${NC}"
else
    echo -e "${RED}✗ Erreur lors du fetch${NC}"
    exit 1
fi

echo ""
echo "🔍 [3/8] Synchronisation avec origin/main..."
echo ""

# Forcer la synchronisation avec origin/main
git reset --hard origin/main
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Reset hard réussi${NC}"
else
    echo -e "${RED}✗ Erreur lors du reset${NC}"
    exit 1
fi

# Nettoyer les fichiers non suivis
git clean -fd
echo -e "${GREEN}✓ Nettoyage des fichiers non suivis${NC}"

echo ""
echo "🔍 [4/8] Vérification des dépendances..."
echo ""

# Vérifier Composer
if [ -f "composer.json" ]; then
    echo -n "Installation des dépendances Composer: "
    composer install --no-dev --optimize-autoloader --quiet
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC}"
    else
        echo -e "${RED}✗${NC}"
        exit 1
    fi
else
    echo -e "${YELLOW}⚠ composer.json non trouvé${NC}"
fi

echo ""
echo "🔍 [5/8] Vérification des permissions..."
echo ""

# Vérifier les permissions des dossiers critiques
critical_dirs=("var" "var/cache" "var/log" "templates" "public")
for dir in "${critical_dirs[@]}"; do
    if [ -d "$dir" ]; then
        chmod 755 "$dir"
        echo -e "${GREEN}✓ Permissions $dir: 755${NC}"
    else
        echo -e "${YELLOW}⚠ Dossier $dir non trouvé${NC}"
    fi
done

echo ""
echo "🔍 [6/8] Test des composants critiques..."
echo ""

# Test des composants critiques
echo -n "Test autoloader: "
if php -r "require 'vendor/autoload.php'; echo 'OK';" 2>/dev/null; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    exit 1
fi

echo -n "Test configuration .env: "
if php -r "require 'vendor/autoload.php'; App\Config\Env::load(); echo 'OK';" 2>/dev/null; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    exit 1
fi

echo -n "Test container DI: "
if php -r "require 'vendor/autoload.php'; App\Config\Env::load(); \$container = require 'config/container.php'; echo 'OK';" 2>/dev/null; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
    exit 1
fi

echo ""
echo "🔍 [7/8] Exécution des scripts de diagnostic..."
echo ""

# Exécuter les scripts de diagnostic
if [ -f "tools/diagnostic_500_errors.php" ]; then
    echo "Exécution du diagnostic PHP..."
    php tools/diagnostic_500_errors.php
    echo ""
fi

echo ""
echo "🔍 [8/8] Redémarrage des services..."
echo ""

# Redémarrer Apache si possible
if command -v systemctl >/dev/null 2>&1; then
    echo -n "Redémarrage d'Apache: "
    sudo systemctl restart apache2 2>/dev/null
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC}"
    else
        echo -e "${YELLOW}⚠ Impossible de redémarrer Apache${NC}"
    fi
else
    echo -e "${YELLOW}⚠ systemctl non disponible${NC}"
fi

echo ""
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║                         DÉPLOIEMENT TERMINÉ                  ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

echo "📋 Prochaines étapes:"
echo "1. Tester les endpoints critiques:"
echo "   curl -I https://iot.olution.info/ffp3/control"
echo "   curl -I https://iot.olution.info/ffp3/api/realtime/sensors/latest"
echo ""
echo "2. Vérifier les logs d'erreur:"
echo "   tail -f var/log/php_errors.log"
echo ""
echo "3. Exécuter le script de test complet:"
echo "   bash deploy-and-test.sh"
echo ""

echo "═══════════════════════════════════════════════════════════════"
echo "Déploiement terminé avec succès !"
echo "═══════════════════════════════════════════════════════════════"
