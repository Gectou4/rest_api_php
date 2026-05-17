<?php
namespace G4\Api\App;

use G4\Api\Config\DB as Config;

/**
 * Gestionnaire de connexion PDO basé sur le Multiton.
 * Chaque clé nommée (ex. 'master') correspond à une connexion distincte.
 */
class DB extends MultitonAbstract
{
    protected ?\PDO   $db       = null;
    protected ?string $user     = null;
    protected ?string $pwd      = null;
    protected ?string $dsn      = null;
    protected ?string $dbServer = null;

    /**
     * Retourne l'instance Multiton pour le serveur donné.
     * Applique la configuration et établit la connexion PDO si ce n'est pas déjà fait.
     */
    public static function getInstance(string $dbServer = 'default'): static
    {
        $instance = parent::getInstance($dbServer);
        $instance->dbServer = $dbServer;
        Config::load($instance);
        $instance->connect();
        return $instance;
    }

    /** Stocke les paramètres de connexion avant l'appel à connect(). */
    public function setConfig(string $dsn, string $user, string $pwd = ''): static
    {
        $this->dsn  = $dsn;
        $this->user = $user;
        $this->pwd  = $pwd;
        return $this;
    }

    /**
     * Établit la connexion PDO avec ERRMODE_EXCEPTION activé.
     * Idempotente : sans effet si la connexion est déjà ouverte.
     * @throws \RuntimeException si la config est absente ou si la connexion échoue.
     */
    public function connect(): void
    {
        if ($this->db !== null) {
            return;
        }
        if ($this->dsn === null) {
            throw new \RuntimeException('Database config is missing.');
        }
        try {
            $this->db = new \PDO($this->dsn, $this->user, $this->pwd, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (\Exception $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getDBServer(): ?string
    {
        return $this->dbServer;
    }

    public function getDB(): ?\PDO
    {
        return $this->db;
    }
}
