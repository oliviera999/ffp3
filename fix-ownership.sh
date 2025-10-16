#!/bin/bash

# Script de correction du propriétaire des fichiers pour FFP3
# À exécuter sur le serveur iot.olution.info

echo "👤 CORRECTION DU PROPRIÉTAIRE DES FICHIERS FFP3"
echo "==============================================="
echo "Date: $(date)"
echo ""

# Aller dans le répertoire du projet
cd /home4/oliviera/iot.olution.info/ffp3/

echo "📋 1. Vérification du propriétaire actuel..."
echo "--------------------------------------------"
ls -la | head -10
echo ""

echo "🔧 2. Correction du propriétaire..."
echo "--------------------------------"

# Déterminer l'utilisateur actuel
current_user=$(whoami)
echo "Utilisateur actuel: $current_user"

# Changer le propriétaire de tous les fichiers
echo "🔄 Changement du propriétaire vers $current_user:$current_user..."
chown -R $current_user:$current_user .

echo "✅ Propriétaire changé avec succès"

echo ""
echo "🔐 3. Application des permissions correctes..."
echo "--------------------------------------------"

# Permissions pour les dossiers
find . -type d -exec chmod 755 {} \;
echo "✅ Dossiers: 755"

# Permissions pour les fichiers
find . -type f -exec chmod 644 {} \;
echo "✅ Fichiers: 644"

# Permissions spéciales pour les scripts
find . -name "*.sh" -exec chmod 755 {} \;
find . -name "*.php" -path "./bin/*" -exec chmod 755 {} \;
echo "✅ Scripts: 755"

# Permissions spéciales pour public/
chmod -R 755 public/
echo "✅ Dossier public: 755"

# Permissions spéciales pour var/
mkdir -p var/cache var/log
chmod -R 755 var/
echo "✅ Dossier var: 755"

echo ""
echo "🔍 4. Vérification finale..."
echo "---------------------------"
ls -la | head -5
echo ""
ls -la src/ | head -5
echo ""

echo "🧪 5. Test de l'application..."
echo "-----------------------------"

# Test de l'autoloader
php -r "
try {
    require_once 'vendor/autoload.php';
    echo '✅ Autoloader chargé avec succès\n';
    
    if (class_exists('App\Config\Env')) {
        echo '✅ Classe App\Config\Env disponible\n';
    } else {
        echo '❌ Classe App\Config\Env non trouvée\n';
    }
} catch (Exception \$e) {
    echo '❌ Erreur: ' . \$e->getMessage() . '\n';
}
"

echo ""
echo "🎯 RÉSUMÉ"
echo "=========="
echo "✅ Corrections appliquées:"
echo "   - Propriétaire: $current_user:$current_user"
echo "   - Permissions des dossiers: 755"
echo "   - Permissions des fichiers: 644"
echo "   - Permissions des scripts: 755"
echo ""
echo "🌐 Votre site devrait maintenant fonctionner sur:"
echo "   https://iot.olution.info/ffp3/"
