<?php
namespace G4\Api\App;

/**
 * Implémentation du patron Multiton : une instance unique par clé nommée.
 * Utilisé notamment pour gérer plusieurs connexions DB (master, slave…).
 */
abstract class MultitonAbstract
{
    protected static array $instances = [];

    /** Retourne l'instance associée à la clé donnée, en la créant si elle n'existe pas encore. */
    public static function getInstance(string $key): static
    {
        if (!isset(static::$instances[$key])) {
            static::$instances[$key] = new static();
        }
        return static::$instances[$key];
    }

    protected function __construct() {}

    private function __clone(): void {}

    /**
     * Empêche la désérialisation de l'instance.
     * @throws \Exception dans tous les cas.
     */
    private function __wakeup(): never
    {
        throw new \Exception('Cannot unserialize a multiton.');
    }
}
