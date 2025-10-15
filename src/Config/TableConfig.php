<?php

declare(strict_types=1);

namespace App\Config;

/**
 * Classe de configuration des noms de tables selon l'environnement
 * 
 * Permet de basculer entre les tables de production (ffp3Data, ffp3Outputs)
 * et les tables de test (ffp3Data2, ffp3Outputs2) via la variable ENV
 */
class TableConfig
{
    /**
     * Détermine si on est en environnement de test
     */
    public static function isTest(): bool
    {
        // Charge les variables d'environnement si pas encore fait
        if (!isset($_ENV['ENV'])) {
            Env::load();
        }
        
        return ($_ENV['ENV'] ?? 'prod') === 'test';
    }
    
    /**
     * Retourne l'environnement actuel (prod ou test)
     */
    public static function getEnvironment(): string
    {
        // Charge les variables d'environnement si pas encore fait
        if (!isset($_ENV['ENV'])) {
            Env::load();
        }
        
        return $_ENV['ENV'] ?? 'prod';
    }

    /**
     * Retourne le nom de la table principale des données capteurs
     * 
     * @return string 'ffp3Data' en prod, 'ffp3Data2' en test
     */
    public static function getDataTable(): string
    {
        return self::isTest() ? 'ffp3Data2' : 'ffp3Data';
    }

    /**
     * Retourne le nom de la table des outputs (GPIO/relais)
     * 
     * @return string 'ffp3Outputs' en prod, 'ffp3Outputs2' en test
     */
    public static function getOutputsTable(): string
    {
        return self::isTest() ? 'ffp3Outputs2' : 'ffp3Outputs';
    }

    /**
     * Retourne le nom de la table heartbeat ESP32
     * 
     * @return string 'ffp3Heartbeat' en prod, 'ffp3Heartbeat2' en test
     */
    public static function getHeartbeatTable(): string
    {
        return self::isTest() ? 'ffp3Heartbeat2' : 'ffp3Heartbeat';
    }

    /**
     * Retourne l'environnement actuel
     * 
     * @return string 'prod' ou 'test'
     */
    public static function getEnvironment(): string
    {
        if (!isset($_ENV['ENV'])) {
            Env::load();
        }
        
        return $_ENV['ENV'] ?? 'prod';
    }

    /**
     * Force un environnement spécifique (utile pour les routes de test)
     * 
     * @param string $env 'prod' ou 'test'
     */
    public static function setEnvironment(string $env): void
    {
        if (!in_array($env, ['prod', 'test'], true)) {
            throw new \InvalidArgumentException("Environment must be 'prod' or 'test', got: {$env}");
        }
        
        $_ENV['ENV'] = $env;
    }
}