#!/bin/bash
# fix-server-cache.sh
# Script de correction des caches serveur pour résoudre les erreurs 500
# À exécuter sur le serveur : bash fix-server-cache.sh

set -e

echo "======================================================================"
echo "🔧 FFP3 - Correction des caches serveur"
echo "======================================================================"
echo ""

# Couleurs pour l'affichage
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. Vérifier qu'on est dans le bon répertoire
echo -e "${YELLOW}[1/8] Vérification du répertoire...${NC}"
if [ ! -f "composer.json" ] || [ ! -d "src" ]; then
    echo -e "${RED}❌ ERREUR: Ce script doit être exécuté depuis le dossier ffp3${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Répertoire correct${NC}"
echo ""

# 2. Vérifier la version Git
echo -e "${YELLOW}[2/8] Vérification de la version Git...${NC}"
CURRENT_COMMIT=$(git log -1 --oneline)
echo "Commit actuel: $CURRENT_COMMIT"
echo -e "${GREEN}✅ Version identifiée${NC}"
echo ""

# 3. Vérifier l'état Git
echo -e "${YELLOW}[3/8] Vérification de l'état Git...${NC}"
GIT_STATUS=$(git status --porcelain)
if [ -n "$GIT_STATUS" ]; then
    echo -e "${YELLOW}⚠️  Modifications locales détectées:${NC}"
    echo "$GIT_STATUS"
else
    echo -e "${GREEN}✅ Arbre de travail propre${NC}"
fi
echo ""

# 4. Nettoyer le cache PHP-DI
echo -e "${YELLOW}[4/8] Nettoyage du cache PHP-DI...${NC}"
if [ -d "var/cache" ]; then
    rm -rf var/cache/*
    echo -e "${GREEN}✅ Cache PHP-DI nettoyé${NC}"
else
    echo -e "${YELLOW}⚠️  Dossier var/cache non trouvé${NC}"
fi
echo ""

# 5. Nettoyer OPCache PHP
echo -e "${YELLOW}[5/8] Nettoyage OPCache PHP...${NC}"
php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo 'OPCache cleared\n'; } else { echo 'OPCache not enabled\n'; }"
echo -e "${GREEN}✅ OPCache traité${NC}"
echo ""

# 6. Vérifier les permissions
echo -e "${YELLOW}[6/8] Vérification des permissions...${NC}"
if [ ! -w "var/cache" ] || [ ! -w "var/log" ]; then
    echo -e "${YELLOW}⚠️  Permissions incorrectes détectées${NC}"
    echo "Tentative de correction..."
    chmod 755 var var/cache var/log 2>/dev/null || echo -e "${RED}❌ Échec (droits insuffisants)${NC}"
else
    echo -e "${GREEN}✅ Permissions correctes${NC}"
fi
echo ""

# 7. Réinstaller les dépendances Composer
echo -e "${YELLOW}[7/8] Réinstallation des dépendances Composer...${NC}"
composer install --no-dev --optimize-autoloader --quiet
echo -e "${GREEN}✅ Dépendances installées${NC}"
echo ""

# 8. Test des endpoints critiques
echo -e "${YELLOW}[8/8] Test des endpoints critiques...${NC}"

BASE_URL="https://iot.olution.info/ffp3"

test_endpoint() {
    local URL=$1
    local EXPECTED=$2
    local DESCRIPTION=$3
    
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL$URL")
    
    if [ "$HTTP_CODE" -eq "$EXPECTED" ]; then
        echo -e "  ${GREEN}✅ $DESCRIPTION: OK (HTTP $HTTP_CODE)${NC}"
        return 0
    else
        echo -e "  ${RED}❌ $DESCRIPTION: ERREUR (HTTP $HTTP_CODE, attendu: $EXPECTED)${NC}"
        return 1
    fi
}

ERRORS=0

test_endpoint "/control" 200 "Control PROD" || ((ERRORS++))
test_endpoint "/api/realtime/sensors/latest" 200 "API Sensors" || ((ERRORS++))
test_endpoint "/api/realtime/outputs/state" 200 "API Outputs" || ((ERRORS++))
test_endpoint "/api/realtime/system/health" 200 "API System Health" || ((ERRORS++))
test_endpoint "/control-test" 200 "Control TEST" || ((ERRORS++))

echo ""
echo "======================================================================"
if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}✅ SUCCÈS: 0 erreur détectée. Tous les endpoints fonctionnent !${NC}"
    echo ""
    echo "🎉 Les caches ont été nettoyés avec succès."
    echo "🎉 L'interface de contrôle devrait maintenant fonctionner."
else
    echo -e "${RED}❌ ÉCHEC: $ERRORS erreur(s) détectée(s)${NC}"
    echo ""
    echo "📋 Actions supplémentaires recommandées:"
    echo "  1. Consulter les logs: tail -100 var/log/php_errors.log"
    echo "  2. Exécuter le diagnostic: php tools/diagnostic_500_errors.php"
    echo "  3. Redémarrer Apache: sudo systemctl restart apache2"
    echo "  4. Consulter les logs Apache: tail -100 /var/log/apache2/error.log"
fi
echo "======================================================================"

exit $ERRORS

