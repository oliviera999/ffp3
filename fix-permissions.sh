#!/bin/bash

# Script de correction des permissions pour FFP3
# À exécuter sur le serveur iot.olution.info

echo "🔐 CORRECTION DES PERMISSIONS FFP3"
echo "==================================="
echo "Date: $(date)"
echo ""

# Aller dans le répertoire du projet
cd /home4/oliviera/iot.olution.info/ffp3/

echo "📋 1. Vérification des permissions actuelles..."
echo "----------------------------------------------"
ls -la src/
echo ""
ls -la vendor/
echo ""

echo "🔧 2. Correction des permissions..."
echo "--------------------------------"

# Permissions pour les dossiers (lecture, écriture, exécution pour le propriétaire)
echo "📁 Correction permissions des dossiers..."
find . -type d -exec chmod 755 {} \;
echo "   ✅ Dossiers: 755"

# Permissions pour les fichiers (lecture, écriture pour le propriétaire, lecture pour groupe/autres)
echo "📄 Correction permissions des fichiers..."
find . -type f -exec chmod 644 {} \;
echo "   ✅ Fichiers: 644"

# Permissions spéciales pour les scripts
echo "🔧 Correction permissions des scripts..."
find . -name "*.sh" -exec chmod 755 {} \;
find . -name "*.php" -path "./bin/*" -exec chmod 755 {} \;
echo "   ✅ Scripts: 755"

# Permissions spéciales pour public/
echo "🌐 Correction permissions du dossier public..."
chmod -R 755 public/
echo "   ✅ Dossier public: 755"

# Permissions spéciales pour var/
echo "📊 Correction permissions du dossier var..."
mkdir -p var/cache
mkdir -p var/log
chmod -R 755 var/
echo "   ✅ Dossier var: 755"

# Vérification spécifique des fichiers critiques
echo ""
echo "🔍 3. Vérification des fichiers critiques..."
echo "--------------------------------------------"

critical_files=(
    "src/Config/Env.php"
    "src/Config/Database.php"
    "src/Config/TableConfig.php"
    "config/container.php"
    "public/index.php"
    "vendor/autoload.php"
)

for file in "${critical_files[@]}"; do
    if [ -f "$file" ]; then
        perms=$(stat -c "%a" "$file")
        echo "   ✅ $file: $perms"
    else
        echo "   ❌ $file: MANQUANT"
    fi
done

echo ""
echo "🧪 4. Test de chargement de l'autoloader..."
echo "------------------------------------------"

# Test simple de l'autoloader
php -r "
try {
    require_once 'vendor/autoload.php';
    echo '   ✅ Autoloader chargé avec succès\n';
    
    if (class_exists('App\Config\Env')) {
        echo '   ✅ Classe App\Config\Env trouvée\n';
    } else {
        echo '   ❌ Classe App\Config\Env non trouvée\n';
    }
} catch (Exception \$e) {
    echo '   ❌ Erreur: ' . \$e->getMessage() . '\n';
}
"

echo ""
echo "🧪 5. Test des endpoints..."
echo "-------------------------"

# Test rapide des endpoints
endpoints=(
    "https://iot.olution.info/ffp3/"
    "https://iot.olution.info/ffp3/control"
)

for endpoint in "${endpoints[@]}"; do
    echo -n "   Test $endpoint: "
    response=$(curl -s -o /dev/null -w "%{http_code}" "$endpoint" 2>/dev/null)
    if [ "$response" = "200" ]; then
        echo "✅ OK ($response)"
    else
        echo "❌ ERREUR ($response)"
    fi
done

echo ""
echo "🎯 RÉSUMÉ"
echo "=========="
echo "✅ Corrections appliquées:"
echo "   - Permissions des dossiers: 755"
echo "   - Permissions des fichiers: 644"
echo "   - Permissions des scripts: 755"
echo "   - Permissions du dossier public: 755"
echo "   - Permissions du dossier var: 755"
echo ""
echo "📝 Si des erreurs persistent:"
echo "   1. Vérifier le propriétaire: ls -la"
echo "   2. Changer le propriétaire si nécessaire: chown -R oliviera:oliviera ."
echo "   3. Consulter les logs: tail -f /var/log/apache2/error.log"
echo ""
echo "🌐 Testez votre site: https://iot.olution.info/ffp3/"
