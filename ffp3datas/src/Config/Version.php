<?php

namespace App\Config;

/**
 * Gestion de la version de l'application
 */
class Version
{
    private static ?string $version = null;
    private static ?string $buildDate = null;

    /**
     * Récupère le numéro de version depuis le fichier VERSION
     * 
     * @return string Version de l'application (ex: "1.0.0")
     */
    public static function get(): string
    {
        if (self::$version === null) {
            $versionFile = dirname(__DIR__, 2) . '/VERSION';
            
            if (file_exists($versionFile)) {
                self::$version = trim(file_get_contents($versionFile));
            } else {
                // Fallback si le fichier n'existe pas
                self::$version = '0.0.0';
            }
        }
        
        return self::$version;
    }

    /**
     * Récupère la version avec le préfixe "v"
     * 
     * @return string Version formatée (ex: "v1.0.0")
     */
    public static function getFormatted(): string
    {
        return 'v' . self::get();
    }

    /**
     * Récupère la date de build (basée sur la modification du fichier VERSION)
     * 
     * @return string Date de build formatée
     */
    public static function getBuildDate(): string
    {
        if (self::$buildDate === null) {
            $versionFile = dirname(__DIR__, 2) . '/VERSION';
            
            if (file_exists($versionFile)) {
                $timestamp = filemtime($versionFile);
                self::$buildDate = date('Y-m-d H:i', $timestamp);
            } else {
                self::$buildDate = 'unknown';
            }
        }
        
        return self::$buildDate;
    }

    /**
     * Récupère la version complète avec date de build
     * 
     * @return string Version complète (ex: "v1.0.0 (2025-10-08 14:30)")
     */
    public static function getFull(): string
    {
        return self::getFormatted() . ' (' . self::getBuildDate() . ')';
    }

    /**
     * Récupère les informations complètes de version pour l'API
     * 
     * @return array{version: string, build_date: string, environment: string}
     */
    public static function getInfo(): array
    {
        return [
            'version' => self::get(),
            'build_date' => self::getBuildDate(),
            'environment' => TableConfig::getEnvironment()
        ];
    }
}
