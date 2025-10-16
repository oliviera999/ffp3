#!/bin/bash

# Script pour résoudre le problème Composer sur le serveur
# À exécuter sur le serveur iot.olution.info

echo "🔧 Résolution du problème Composer sur le serveur..."

# Aller dans le répertoire du projet
cd /home4/oliviera/iot.olution.info/ffp3/

# Sauvegarder l'ancien composer.lock
echo "💾 Sauvegarde de l'ancien composer.lock..."
cp composer.lock composer.lock.backup

# Supprimer le fichier lock pour forcer la mise à jour
echo "🗑️ Suppression de l'ancien composer.lock..."
rm composer.lock

# Supprimer le dossier vendor pour un nettoyage complet
echo "🧹 Suppression du dossier vendor..."
rm -rf vendor/

# Mettre à jour et installer les dépendances
echo "📦 Installation des dépendances avec mise à jour..."
composer update --no-dev --optimize-autoloader

# Vérifier que php-di est installé
echo "✅ Vérification de l'installation..."
if [ -d "vendor/php-di" ]; then
    echo "   ✅ php-di installé avec succès"
else
    echo "   ❌ php-di non trouvé"
fi

# Vérifier l'autoloader
if [ -f "vendor/autoload.php" ]; then
    echo "   ✅ autoload.php généré"
else
    echo "   ❌ autoload.php manquant"
fi

# Tester le chargement de DI\ContainerBuilder
echo "🧪 Test de chargement des classes..."
php -r "
require_once 'vendor/autoload.php';
if (class_exists('DI\ContainerBuilder')) {
    echo '   ✅ DI\ContainerBuilder disponible\n';
} else {
    echo '   ❌ DI\ContainerBuilder non trouvé\n';
}
"

echo "🎯 Résolution terminée !"
echo "🌐 Votre site devrait maintenant fonctionner sur https://iot.olution.info/ffp3/"
