<?php

namespace App\Config;

use Dotenv\Dotenv;

/**
 * Classe utilitaire permettant de charger une unique fois les variables d'environnement
 * depuis le fichier .env à la racine du projet.
 * Elle peut être appelée très tôt dans les scripts procéduraux pour garantir la
 * disponibilité des variables avant toute utilisation (ex. connexion BD, API key, etc.).
 */
class Env
{
    /** @var bool Indique si le chargement a déjà eu lieu */
    private static bool $loaded = false;

    /**
     * Charge le fichier .env si présent. Les variables existantes ne sont PAS écrasées
     * (safeLoad) afin de permettre l'override depuis l'environnement système.
     */
    public static function load(): void
    {
        if (self::$loaded) {
            return; // Évite les rechargements multiples
        }

        $rootDir = dirname(__DIR__, 2); // remonte à la racine du projet
        $envPath = $rootDir . '/.env';
        if (file_exists($envPath)) {
            // Charge le fichier .env principal
            Dotenv::createMutable($rootDir)->safeLoad();
        } elseif (file_exists($rootDir . '/env.dist')) {
            // Fallback : charge env.dist si présent (utile en production lorsqu'on oublie .env)
            Dotenv::createMutable($rootDir, 'env.dist')->safeLoad();
        }

        self::$loaded = true;
    }
} 