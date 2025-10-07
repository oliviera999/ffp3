<?php

namespace FFP3Control\Config;

use PDO;
use PDOException;
use Dotenv\Dotenv;

/**
 * Classe Database pour ffp3control
 * Gère la connexion unique à la base de données
 */
class Database
{
    private static ?PDO $instance = null;
    private static bool $envLoaded = false;

    /**
     * Charge les variables d'environnement depuis .env
     */
    private static function loadEnv(): void
    {
        if (self::$envLoaded) {
            return;
        }

        $rootDir = dirname(__DIR__);
        $envPath = $rootDir . '/.env';

        if (file_exists($envPath)) {
            $dotenv = Dotenv::createMutable($rootDir);
            $dotenv->safeLoad();
        } elseif (file_exists($rootDir . '/env.dist')) {
            $dotenv = Dotenv::createMutable($rootDir, 'env.dist');
            $dotenv->safeLoad();
        }

        self::$envLoaded = true;
    }

    /**
     * Retourne l'instance unique de PDO
     * 
     * @return PDO
     * @throws \RuntimeException si variables manquantes ou connexion échoue
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            self::loadEnv();

            // Vérification des variables obligatoires
            foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $var) {
                if (!isset($_ENV[$var]) || $_ENV[$var] === '') {
                    throw new \RuntimeException(
                        "Variable d'environnement '{$var}' manquante. " .
                        "Veuillez configurer le fichier .env"
                    );
                }
            }

            $host = $_ENV['DB_HOST'];
            $db   = $_ENV['DB_NAME'];
            $user = $_ENV['DB_USER'];
            $pass = $_ENV['DB_PASS'];

            $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false, // Vraies prepared statements
                ]);
            } catch (PDOException $e) {
                throw new \RuntimeException('Connexion DB échouée: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }

    /**
     * Retourne le nom de la table Outputs selon l'environnement
     * 
     * @return string
     */
    public static function getOutputsTable(): string
    {
        self::loadEnv();
        $env = $_ENV['ENV'] ?? 'prod';
        return $env === 'test' ? 'ffp3Outputs2' : 'ffp3Outputs';
    }

    /**
     * Retourne le nom de la table Boards (pas de suffixe, partagée)
     * 
     * @return string
     */
    public static function getBoardsTable(): string
    {
        return 'Boards';
    }

    /**
     * Définit l'environnement (prod ou test)
     * 
     * @param string $env
     */
    public static function setEnvironment(string $env): void
    {
        if (!in_array($env, ['prod', 'test'])) {
            throw new \InvalidArgumentException("Environnement invalide: {$env}. Utilisez 'prod' ou 'test'.");
        }
        $_ENV['ENV'] = $env;
    }
}
