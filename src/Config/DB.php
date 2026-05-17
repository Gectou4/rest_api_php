<?php
namespace G4\Api\Config;

use G4\Api\App\DB as AppDB;

/** Fournisseur de configuration de connexion à la base de données. */
class DB
{
    /** Injecte la configuration dans une instance DB avant son appel à connect(). */
    public static function load(AppDB $db): void
    {
        $config = self::getConfig($db->getDBServer() ?? 'default');
        $db->setConfig($config['dsn'], $config['user'], $config['pwd']);
    }

    /**
     * Retourne les paramètres de connexion pour le serveur demandé.
     * Les variables d'environnement DB_USER, DB_PWD et DB_DSN ont la priorité sur les valeurs par défaut.
     *
     * @return array{user: string, pwd: string, dsn: string}
     */
    public static function getConfig(string $key = 'default'): array
    {
        $default = [
            'user' => getenv('DB_USER') ?: 'root',
            'pwd'  => getenv('DB_PWD')  ?: '',
            'dsn'  => getenv('DB_DSN')  ?: 'mysql:host=localhost;dbname=rest_api;charset=utf8',
        ];

        $master = $default;

        return match ($key) {
            'master' => $master,
            default  => $default,
        };
    }
}
