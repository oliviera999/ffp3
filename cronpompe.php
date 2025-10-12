<?php
/**
 * Fichier proxy pour exécution manuelle du CRON
 * Redirige vers run-cron.php pour compatibilité avec l'ancienne interface
 * 
 * Ce fichier existe pour maintenir la compatibilité avec les liens legacy
 * de l'interface de contrôle.
 */

// Rediriger vers le vrai script CRON
header('Location: run-cron.php');
exit;

