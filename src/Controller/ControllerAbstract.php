<?php

declare(strict_types=1);

namespace G4\Api\Controller;

/** Contrôleur de base : gestion des paramètres de route et du code HTTP de réponse. */
abstract class ControllerAbstract implements ControllerInterface
{
    protected array $params = [];

    protected int $code = 200;

    abstract public function getIndexAction(): mixed;

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Retourne la valeur d'un paramètre de route ou de requête.
     * Les paramètres proviennent à la fois des segments d'URI (route) et du corps de la requête (args).
     */
    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function setCode(int $code): void
    {
        $this->code = $code;
    }
}
