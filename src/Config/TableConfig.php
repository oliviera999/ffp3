<?php

declare(strict_types=1);

namespace App\Config;

/**
 * Classe de configuration des noms de tables selon l'environnement
 *
 * Permet de basculer entre les tables de production (ffp3Data, ffp3Outputs)
 * et les tables de test (ffp3Data2, ffp3Outputs2, ffp3Data3, ffp3Outputs3) via la variable ENV
 */
class TableConfig
{
    private const ENVIRONMENTS = ['prod', 'test', 'test3'];

    /**
     * Détermine si on est en environnement de test (test ou test3)
     */
    public static function isTest(): bool
    {
        if (!isset($_ENV['ENV'])) {
            Env::load();
        }
        $env = $_ENV['ENV'] ?? 'prod';
        return in_array($env, ['test', 'test3'], true);
    }

    /**
     * Retourne l'environnement actuel (prod, test ou test3)
     */
    public static function getEnvironment(): string
    {
        if (!isset($_ENV['ENV'])) {
            Env::load();
        }
        return $_ENV['ENV'] ?? 'prod';
    }

    /**
     * Retourne le nom de la table principale des données capteurs
     *
     * @return string 'ffp3Data' en prod, 'ffp3Data2' en test, 'ffp3Data3' en test3
     */
    public static function getDataTable(): string
    {
        return match (self::getEnvironment()) {
            'test' => 'ffp3Data2',
            'test3' => 'ffp3Data3',
            default => 'ffp3Data',
        };
    }

    /**
     * Retourne le nom de la table des outputs (GPIO/relais)
     *
     * @return string 'ffp3Outputs' en prod, 'ffp3Outputs2' en test, 'ffp3Outputs3' en test3
     */
    public static function getOutputsTable(): string
    {
        return match (self::getEnvironment()) {
            'test' => 'ffp3Outputs2',
            'test3' => 'ffp3Outputs3',
            default => 'ffp3Outputs',
        };
    }

    public static function getOutputsTableFor(string $environment): string
    {
        return match ($environment) {
            'test' => 'ffp3Outputs2',
            'test3' => 'ffp3Outputs3',
            default => 'ffp3Outputs',
        };
    }

    /**
     * Retourne le nom de la table heartbeat ESP32
     *
     * @return string 'ffp3Heartbeat' en prod, 'ffp3Heartbeat2' en test, 'ffp3Heartbeat3' en test3
     */
    public static function getHeartbeatTable(): string
    {
        return match (self::getEnvironment()) {
            'test' => 'ffp3Heartbeat2',
            'test3' => 'ffp3Heartbeat3',
            default => 'ffp3Heartbeat',
        };
    }

    /**
     * Force un environnement spécifique (utile pour les routes de test)
     *
     * @param string $env 'prod', 'test' ou 'test3'
     */
    public static function setEnvironment(string $env): void
    {
        if (!in_array($env, self::ENVIRONMENTS, true)) {
            throw new \InvalidArgumentException("Environment must be 'prod', 'test' or 'test3', got: {$env}");
        }
        $_ENV['ENV'] = $env;
    }
}
