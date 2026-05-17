<?php
namespace G4\Api\Model;

use G4\Api\App\DB as DB;

/**
 * Classe de base pour tous les modèles.
 * Gère la connexion PDO et fournit les mécanismes de chargement et de sérialisation.
 */
abstract class ModelAbstract
{
    protected int    $id       = 0;
    protected string $table    = '';
    protected string $dbserver = 'master';
    protected ?\PDO  $db       = null;
    protected bool   $loaded   = false;

    /**
     * Acquiert la connexion PDO et charge l'objet si un id > 0 est fourni.
     * Le paramètre $force permet de recharger même si $loaded est déjà true.
     */
    public function __construct(int $id = 0, bool $force = false)
    {
        $this->db = DB::getInstance($this->dbserver)->getDB();

        if ($id > 0 && (!$this->loaded || $force)) {
            $this->load($id);
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /** Retourne true uniquement si load() a trouvé et hydraté un enregistrement. */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * Sérialisation générique : itère les propriétés publiques en excluant les propriétés techniques.
     * Les sous-classes doivent surcharger cette méthode pour un rendu précis,
     * car les propriétés protected ne sont pas accessibles via foreach($this).
     */
    public function toArray(): array
    {
        $protected = ['table' => true, 'dbserver' => true, 'db' => true, 'loaded' => true];
        $return    = [];

        foreach ($this as $key => $value) {
            if (!($protected[$key] ?? false)) {
                $return[$key] = $value instanceof self ? $value->toArray() : $value;
            }
        }
        return $return;
    }

    /** Hydrate l'objet depuis la base de données à partir de son identifiant. */
    abstract public function load(int $id): void;
}
