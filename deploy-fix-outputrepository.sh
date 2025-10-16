#!/bin/bash

# Script de déploiement pour corriger l'erreur de syntaxe OutputRepository
# Version: 4.6.17
# Date: 2025-10-16

echo "🚀 Déploiement correction OutputRepository v4.6.17"
echo "=================================================="

# Configuration
SERVER="oliviera@toaster"
PROJECT_PATH="/home4/oliviera/iot.olution.info/ffp3"
BACKUP_DIR="/home4/oliviera/backups/ffp3"

# Créer un backup avant modification
echo "📦 Création backup..."
ssh $SERVER "mkdir -p $BACKUP_DIR && cp $PROJECT_PATH/src/Repository/OutputRepository.php $BACKUP_DIR/OutputRepository.php.backup.$(date +%Y%m%d_%H%M%S)"

# Synchroniser les fichiers modifiés
echo "🔄 Synchronisation fichiers..."
scp src/Repository/OutputRepository.php $SERVER:$PROJECT_PATH/src/Repository/OutputRepository.php
scp VERSION $SERVER:$PROJECT_PATH/VERSION
scp CHANGELOG.md $SERVER:$PROJECT_PATH/CHANGELOG.md

# Nettoyer les caches sur le serveur
echo "🧹 Nettoyage caches..."
ssh $SERVER "cd $PROJECT_PATH && rm -rf var/cache/* && php bin/clear-cache.php"

# Tester la correction
echo "🧪 Test de la correction..."
ssh $SERVER "cd $PROJECT_PATH && php -l src/Repository/OutputRepository.php"

# Vérifier que l'application fonctionne
echo "✅ Test endpoint de diagnostic..."
ssh $SERVER "cd $PROJECT_PATH && curl -s 'https://iot.olution.info/ffp3/public/fix-cache.php?token=fix2025ffp3' | grep -E '(✅|❌|Parse error|Fatal error)'"

echo ""
echo "🎉 Déploiement terminé !"
echo "📋 Vérifiez manuellement : https://iot.olution.info/ffp3/public/fix-cache.php?token=fix2025ffp3"
echo "⚠️  N'oubliez pas de supprimer le fichier fix-cache.php après vérification"
