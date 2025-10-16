# Script PowerShell de déploiement pour corriger l'erreur fatale TableConfig
# Version: 4.6.16
# Date: 2025-10-16

Write-Host "🚀 Déploiement correction TableConfig v4.6.16" -ForegroundColor Green
Write-Host "==============================================" -ForegroundColor Green

# Configuration
$SERVER = "oliviera@toaster"
$PROJECT_PATH = "/home4/oliviera/iot.olution.info/ffp3"
$BACKUP_DIR = "/home4/oliviera/backups/ffp3"

# Créer un backup avant modification
Write-Host "📦 Création backup..." -ForegroundColor Yellow
ssh $SERVER "mkdir -p $BACKUP_DIR && cp $PROJECT_PATH/src/Config/TableConfig.php $BACKUP_DIR/TableConfig.php.backup.$(date +%Y%m%d_%H%M%S)"

# Synchroniser les fichiers modifiés
Write-Host "🔄 Synchronisation fichiers..." -ForegroundColor Yellow
scp src/Config/TableConfig.php "${SERVER}:${PROJECT_PATH}/src/Config/TableConfig.php"
scp VERSION "${SERVER}:${PROJECT_PATH}/VERSION"
scp CHANGELOG.md "${SERVER}:${PROJECT_PATH}/CHANGELOG.md"

# Nettoyer les caches sur le serveur
Write-Host "🧹 Nettoyage caches..." -ForegroundColor Yellow
ssh $SERVER "cd $PROJECT_PATH && rm -rf var/cache/* && php bin/clear-cache.php"

# Tester la correction
Write-Host "🧪 Test de la correction..." -ForegroundColor Yellow
ssh $SERVER "cd $PROJECT_PATH && php -l src/Config/TableConfig.php"

# Vérifier que l'application fonctionne
Write-Host "✅ Test endpoint de diagnostic..." -ForegroundColor Yellow
ssh $SERVER "cd $PROJECT_PATH && curl -s 'https://iot.olution.info/ffp3/public/fix-cache.php?token=fix2025ffp3' | grep -E '(✅|❌|Fatal error)'"

Write-Host ""
Write-Host "🎉 Déploiement terminé !" -ForegroundColor Green
Write-Host "📋 Vérifiez manuellement : https://iot.olution.info/ffp3/public/fix-cache.php?token=fix2025ffp3" -ForegroundColor Cyan
Write-Host "⚠️  N'oubliez pas de supprimer le fichier fix-cache.php après vérification" -ForegroundColor Red
