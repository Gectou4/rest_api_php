<?php

declare(strict_types=1);

namespace G4\Api\App;

/** Implémentation du patron Singleton avec protection contre le clonage et la désérialisation. */
abstract class SingletonAbstract
{
    protected static self $instance;

    /** Retourne l'instance unique de la classe appelante, en la créant si nécessaire. */
    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    protected function __construct() {}

    private function __clone(): void {}

    /**
     * Empêche la désérialisation de l'instance.
     * @throws \Exception dans tous les cas.
     */
    public function __wakeup(): never
    {
        throw new \Exception('Cannot unserialize a singleton.');
    }
}
